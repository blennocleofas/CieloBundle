<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\Tests\Handler;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;
use Tritoq\Bundle\CieloBundle\DependencyInjection\TritoqCieloExtension;
use Tritoq\Bundle\CieloBundle\Event\CieloEvent;
use Tritoq\Bundle\CieloBundle\EventListener\AnaliseEventSubscriber;
use Tritoq\Bundle\CieloBundle\Handler\CieloHandler;
use Tritoq\Payment\Cielo\Cartao;
use Tritoq\Payment\Cielo\Transacao;

class CieloHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var CieloHandler
     */
    private $handler;

    /**
     * @var array
     */
    private $postDataInvalid;

    /**
     * @var array
     */
    private $postDataValid;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     *
     */
    public function setUp()
    {
        $fileValid = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'config-example.yml';
        $data = Yaml::parse($fileValid);

        $container = $this->container = new ContainerBuilder();
        $event = new EventDispatcher();

        $container->set('event_dispatcher', $event);

        $extension = new TritoqCieloExtension();
        $extension->load($data, $container);

        $this->handler = $container->get('tritoq.cielo.handler');

        $this->postDataInvalid = array(
            'portador_nome' => 'Cliente',
            'portador_cep' => '90232049',
            'portador_endereco' => 'adsasd',
            'portador_bairro' => 'Centro',
            'cartao_numero' => '2309420492340',
            'cartao_codigoseguranca' => '234',
            'cartao_bandeira' => 'visa',
            'cartao_validade' => '201612',
            'parcelas' => '1',
            'pedido_numero' => '12',
            'pedido_valor' => 12.40,
            'pedido_descricao' => 'Pedido do Sr Fulano',
            'pedido_estado' => 'SC',
            'pedido_cidade' => 'Chapeco',
            'pedido_cep' => '023942324',
            'pedido_endereco' => 'Rua fulano aosidadoi',
            'pedido_data' => new \DateTime('now')
            #'cliente_analise' => new \stdClass(),
        );

        $this->postDataValid = array(
            'portador_nome' => 'Portador 01',
            'portador_cep' => '89802140',
            'portador_endereco' => 'Rua Fulano de tal',
            'portador_bairro' => 'Centro',
            'cartao_numero' => Cartao::TESTE_CARTAO_NUMERO,
            'cartao_codigoseguranca' => Cartao::TESTE_CARTAO_CODIGO_SEGURANCA,
            'cartao_bandeira' => Cartao::TESTE_CARTAO_BANDEIRA,
            'cartao_validade' => Cartao::TESTE_CARTAO_VALIDADE,
            'parcelas' => '1',
            'pedido_numero' => '9021',
            'pedido_valor' => 100.00,
            'pedido_descricao' => 'Pedido do Sr Fulano',
            'pedido_estado' => 'SC',
            'pedido_cidade' => 'Chapeco',
            'pedido_cep' => '89802140',
            'pedido_endereco' => 'Rua Fulano de tal',
            'pedido_data' => new \DateTime('now')
        );
    }

    /**
     *
     */
    public function testCreateTransaction()
    {
        $reflection = new \ReflectionClass($this->handler);

        $method = $reflection->getMethod('createTransaction');
        $method->setAccessible(true);

        $object = $method->invoke($this->handler);

        $this->assertInstanceOf('Tritoq\\Payment\\Cielo\\Transacao', $object);
    }

    /**
     *
     */
    public function testHandleDataWithEmpty()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $postData = array();

        $this->handler->handleData($postData);
    }


    public function testHandleDataWithInvalidData()
    {


        $this->handler
            ->handleData($this->postDataInvalid);

    }

    /**
     *
     */
    public function testDoTransacaoNoAutorized()
    {
        /** @var EventDispatcher $event */
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::TRANSACAO,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(Transacao::STATUS_NAO_AUTORIZADA, (integer)$e->getTransacao()->getStatus());
            }
        );

        $this->handler
            ->handleData($this->postDataInvalid)
            ->doTransacao();
    }

    /**
     *
     */
    public function testDoConsulta()
    {
        /** @var EventDispatcher $event */
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::CONSULTA,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(Transacao::STATUS_AUTORIZADA, (integer)$e->getTransacao()->getStatus());
            }
        );

        $this->handler
            ->handleData($this->postDataValid)
            ->doTransacao();

        $transacao = $this->handler->getService()->getTransacao();
        $tid = $transacao->getTid();
        $this->handler->doConsulta($tid);
    }


    public function testDoConsultaWithInvalidTid()
    {
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::CONSULTA,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(0, $e->getStatusCode());
            }
        );

        $this->handler
            ->handleData($this->postDataValid);

        $tid = '0239420349234';

        $this->handler->doConsulta($tid);
    }

    public function testDoCaptura()
    {
        /** @var EventDispatcher $event */
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::CAPTURA,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(Transacao::STATUS_CAPTURADA, (integer)$e->getTransacao()->getStatus());
            }
        );

        $this->handler
            ->handleData($this->postDataValid)
            ->doTransacao();

        $transacao = $this->handler->getService()->getTransacao();
        $tid = $transacao->getTid();
        $this->handler->doCaptura($tid);
    }

    public function testDoCapturaWithInvalidTid()
    {
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::CAPTURA,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(0, $e->getStatusCode());
            }
        );

        $this->handler
            ->handleData($this->postDataValid);

        $tid = '0239420349234';

        $this->handler->doCaptura($tid);
    }

    public function testDoCancelaWithInvalidTid()
    {
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::CANCELA,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(0, $e->getStatusCode());
            }
        );

        $this->handler
            ->handleData($this->postDataValid);

        $tid = '0239420349234';

        $this->handler->doCancela($tid);
    }

    /**
     *
     */
    public function testDoCancela()
    {
        /** @var EventDispatcher $event */
        $event = $this->container->get('event_dispatcher');

        $testObject = $this;

        $event->addListener(
            CieloEvent::CANCELA,
            function (CieloEvent $e) use ($testObject) {
                $testObject->assertSame(Transacao::STATUS_CANCELADA, (integer)$e->getTransacao()->getStatus());
            }
        );


        $this->handler
            ->handleData($this->postDataValid)
            ->doTransacao();

        $transacao = $this->handler->getService()->getTransacao();
        $tid = $transacao->getTid();
        $this->handler->doCancela($tid);
    }


    public function testAnaliseSubscriber()
    {
        /** @var EventDispatcher $event */
        $event = $this->container->get('event_dispatcher');

        /** @var AnaliseEventSubscriber $subscriber */
        $subscriber = $this->container->get('tritoq.analise.event_subscriber');

        // adds the subscriber
        $event->addSubscriber($subscriber);

        $event->addListener(
            CieloEvent::ANALISE,
            function (CieloEvent $e) {
                echo 'analise OK';
            }
        );

        $this->handler
            ->handleData($this->postDataValid)
            ->doTransacao();

        $transacao = $this->handler->getService()->getTransacao();
        $tid = $transacao->getTid();
        $this->handler->doConsulta($tid);

    }
}
