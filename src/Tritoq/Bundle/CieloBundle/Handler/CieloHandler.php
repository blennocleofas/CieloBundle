<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\Handler;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Validation;
use Tritoq\Bundle\CieloBundle\Event\CieloEvent;
use Tritoq\Bundle\CieloBundle\Validation\ValidationFactory;
use Tritoq\Payment\Cielo\Cartao;
use Tritoq\Payment\Cielo\CieloService;
use Tritoq\Payment\Cielo\Loja;
use Tritoq\Payment\Cielo\Pedido;
use Tritoq\Payment\Cielo\Portador;
use Tritoq\Payment\Cielo\Transacao;

/**
 *
 * Serviço onde manipula as transações e organiza os objetos da Cielo
 *
 *
 * Class CieloHandler
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\Service
 * @license   GPL-3.0+
 */
class CieloHandler
{

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Loja
     */
    private $loja;

    /**
     * @var Portador
     */
    private $portador;

    /**
     * @var Cartao
     */
    private $cartao;

    /**
     * @var CieloService
     */
    private $service;


    public function __construct(array $options)
    {
        $this->options = $options;

        $this->loja = new Loja();
        $this->loja
            ->setAmbiente($options['loja']['ambiente'])
            ->setChave($options['loja']['chave'])
            ->setNumeroLoja($options['loja']['numero_loja'])
            ->setUrlRetorno($options['loja']['url_retorno'])
            ->setNomeLoja($options['loja']['nome']);


        $this->service = new CieloService();
        $this->service
            ->setTransacao($this->createTransaction())
            ->setSsl($options['loja']['ssl'])
            ->setLoja($this->loja);
    }

    /**
     * @param \Symfony\Component\EventDispatcher\Event $eventDispatcher
     *
     * @return $this
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }


    /**
     *
     * Retorna valores padrões para os dados de requisição
     *
     * @return array
     */
    private function getDefaultData()
    {
        return array(
            'transacao_tipo' => 'credito',
            'pedido_pais' => 'BR'
        );
    }

    /**
     * @return Transacao
     */
    private function createTransaction()
    {
        $transacao = new Transacao();
        $transacao->setAutorizar($this->options['transacao']['autorizar']);
        $transacao->setCapturar($this->options['transacao']['capturar'] ? 'true' : 'false');
        return $transacao;
    }

    /**
     * @param array $data
     *
     * @throws \UnexpectedValueException
     */
    private function validateInput(array $data)
    {
        $validator = Validation::createValidator();


        $fielsValidation = array(
            'transacao_tipo' => array(
                'type' => 'choice',
                'options' => array('credito', 'debito')
            ),
            'cliente_analise' => array(
                'type' => 'instance',
                'options' => array(
                    'class' => 'Tritoq\\Payment\\Cielo\\AnaliseRisco\\ClienteAnaliseRiscoInterface'
                )
            ),
            'pedido_data' => array(
                'type' => 'date'
            ),
            'pedido_valor' => array(
                'type' => 'number'
            )
        );

        foreach ($fielsValidation as $key => $item) {
            if (!empty($data[$key])) {
                $constraint = ValidationFactory::createConstraint(
                    $item['type'],
                    $data[$key],
                    (isset($item['options']) ? $item['options'] : null)
                );
                $violentions = $validator->validateValue($constraint->value, $constraint->constraint);

                if ($violentions->count() > 0) {

                    throw new \UnexpectedValueException("Valor inválido para \"$key\"\n$violentions");

                }
            }
        }


    }

    /**
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    private function requireEntries(array $data)
    {
        /*
         * Validação de dados
         */
        $requiresEntries = array(
            'portador_nome',
            'portador_cep',
            'portador_endereco',
            'portador_bairro',
            'cartao_numero',
            'cartao_codigoseguranca',
            'cartao_bandeira',
            'cartao_validade',
            'parcelas',
            'pedido_numero',
            'pedido_valor',
            'pedido_descricao',
            'pedido_estado',
            'pedido_cidade',
            'pedido_cep',
            'pedido_endereco',
        );

        $optionalEntries = array(
            'portador_complemento',
            'pedido_complemento',
            'pedido_unitario',
            'pedido_pais',
            'transacao_tipo',
            'cliente_analise',
            'pedido_data'
        );


        $options_out = array();

        foreach ($requiresEntries as $item) {
            if (!array_key_exists($item, $data)) {
                $options_out[] = $item;
            }
        }

        if (sizeof($options_out) > 0) {
            throw new \InvalidArgumentException(
                implode(
                    ',',
                    $options_out
                ) . 'É necessário enviar as informações de dados: '
            );
        }


        $totalEntries = array_merge($requiresEntries, $optionalEntries);

        foreach ($data as $key => $value) {
            if (!in_array($key, $totalEntries)) {
                throw new \InvalidArgumentException('A opção ' . $key . ' não é valida');
            }
        }

    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function handleData(array $data)
    {
        $data = array_merge($this->getDefaultData(), $data);

        /*
         * Validate Entries
         */

        $this->requireEntries($data);


        /*
         * Validate Input Data
         */
        $this->validateInput($data);

        /*
         * Prepara o Objeto Portador
         */

        $portador = new Portador();
        $portador
            ->setBairro($data['portador_bairro'])
            ->setCep($data['portador_cep'])
            ->setEndereco($data['portador_endereco']);

        if (isset($data['portador_complemento'])) {
            $portador->setComplemento($data['portador_complemento']);
        }

        $this->portador = $portador;

        /*
         * Prepara o Cartão
         */

        $cartao = new Cartao();
        $cartao
            ->setNumero($data['cartao_numero'])
            ->setBandeira($data['cartao_bandeira'])
            ->setCodigoSegurancaCartao($data['cartao_codigoseguranca'])
            ->setNomePortador($data['portador_nome'])
            ->setValidade($data['cartao_validade']);

        $this->cartao = $cartao;

        /*
         * Prepara a transação
         */
        $transacao = $this->createTransaction();
        $transacao->setParcelas((int)$data['parcelas']);


        if ((int)$data['parcelas'] == 1 && $data['transacao_tipo'] == 'credito') {
            $transacao->setProduto(Transacao::PRODUTO_CREDITO_AVISTA);
        } else {
            if ($data['transacao_tipo'] == 'debito') {
                $transacao->setProduto(Transacao::PRODUTO_DEBITO);
            } else {
                if ((int)$data['parcelas'] > 1) {
                    $transacao->setProduto(Transacao::PRODUTO_PARCELADO_LOJA);
                }
            }
        }

        /*
        * Evento de Start do Handler
        */
        $event = new CieloEvent(CieloEvent::AFTER, $transacao);
        $this->eventDispatcher->dispatch(CieloEvent::AFTER, $event);


        $pedido = new Pedido();
        $pedido
            ->setDataHora((isset($data['pedido_data']) ? $data['pedido_data'] : new \DateTime('now')))
            ->setDescricao($data['pedido_descricao'])
            ->setIdioma($this->options['pedido']['idioma'])
            ->setNumero($data['pedido_numero'])
            ->setValor(number_format($data['pedido_valor'], 2, '', ''));

        /*
         * Preparando o serviço
         */
        $service = new CieloService(
            array(
                'portador' => $portador,
                'loja' => $this->loja,
                'cartao' => $cartao,
                'transacao' => $transacao,
                'pedido' => $pedido,
            )
        );

        $service->setHabilitarAnaliseRisco(false);
        $this->service = $service;
        return $this;
    }

    /**
     * @throws \Exception
     * @return $this
     */
    public function doTransacao()
    {
        if (!$this->service) {
            throw new \Exception('É necessário executar o handleData para realizar a transação');
        }

        $this->service->doTransacao(false, false);

        $event = new CieloEvent(CieloEvent::TRANSACAO, $this->service->getTransacao());
        $this->eventDispatcher->dispatch(CieloEvent::TRANSACAO, $event);

        return $this;
    }

    /**
     * @param $tid
     *
     * @throws \Exception
     * @return $this
     */
    public function doConsulta($tid)
    {
        if (!$this->service) {
            throw new \Exception('É necessário executar o handleData para realizar a consulta');
        }

        $this->service->getTransacao()->setTid($tid);
        $this->service->doConsulta();

        $event = new CieloEvent(CieloEvent::CONSULTA, $this->service->getTransacao());
        $this->eventDispatcher->dispatch(CieloEvent::CONSULTA, $event);

        return $this;
    }

    /**
     * @param $tid
     *
     * @throws \Exception
     */
    public function doCaptura($tid)
    {
        if (!$this->service) {
            throw new \Exception('É necessário executar o handleData para realizar a consulta');
        }

        $this->service->getTransacao()->setTid($tid);
        $this->service->doCaptura();

        $event = new CieloEvent(CieloEvent::CAPTURA, $this->service->getTransacao());
        $this->eventDispatcher->dispatch(CieloEvent::CAPTURA, $event);
    }

    /**
     * @param $tid
     *
     * @throws \Exception
     */
    public function doCancela($tid)
    {
        if (!$this->service) {
            throw new \Exception('É necessário executar o handleData para realizar a consulta');
        }

        $this->service->getTransacao()->setTid($tid);
        $this->service->doCancela();

        $event = new CieloEvent(CieloEvent::CANCELA, $this->service->getTransacao());
        $this->eventDispatcher->dispatch(CieloEvent::CANCELA, $event);
    }

    /**
     * @return \Tritoq\Payment\Cielo\CieloService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param \Tritoq\Payment\Cielo\Loja $loja
     *
     * @return $this
     */
    public function setLoja($loja)
    {
        $this->loja = $loja;
        return $this;
    }

    /**
     * @return \Tritoq\Payment\Cielo\Loja
     */
    public function getLoja()
    {
        return $this->loja;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
