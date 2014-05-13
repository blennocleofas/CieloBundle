<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\Event;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Tritoq\Payment\Cielo\Transacao;

/**
 *
 * Classe de Eventos ocorridos na Cielo
 *
 *
 * Class CieloEvent
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\Event
 * @license   GPL-3.0+
 */
class CieloEvent extends Event
{

    /**
     * Tipo de evento quando for iniciado Handler Request
     *
     * @const string
     */
    const BEFORE = 'tritoq.cielo.before';

    /**
     * Tipo de evento quando for terminado o Handler Request
     *
     * @const string
     */
    const AFTER = 'tritoq.cielo.after';

    /**
     * Tipo de evento quando for realizado alguma transação
     *
     * @const string
     */
    const TRANSACAO = 'tritoq.cielo.transacao';

    /**
     *  Tipo de evento quando for realizado uma consulta
     *
     * @const string
     */
    const CONSULTA = 'tritoq.cielo.consulta';

    /**
     * Tipo de evento quando for realizado um cancelamento
     *
     * @const string
     */
    const CANCELA = 'tritoq.cielo.cancela';

    /**
     * Tipo de evento quando for capturada uma transação
     *
     * @const string
     */
    const CAPTURA = 'tritoq.cielo.captura';


    /**
     * Tipos de evento quando houver resultado da análise
     *
     * @const string
     */
    const ANALISE = 'tritoq.cielo.analise';


    /**
     * @var Transacao
     */
    private $transacao;


    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $statusCode;

    /**
     * @var string
     */
    public $errorMessage;

    /**
     *
     * Construtor do evento, recebe o tipo de requisição e a transação
     *
     * @param           $type
     * @param Transacao $transacao
     *
     * @throws \Symfony\Component\Security\Core\Exception\InvalidArgumentException
     */
    public function __construct($type, Transacao $transacao)
    {
        switch ($type) {
            case self::AFTER:
            case self::BEFORE:
            case self::ANALISE:
            case self::TRANSACAO:
            case self::CONSULTA:
            case self::CANCELA:
            case self::CAPTURA:
                $this->transacao = $transacao;
                $this->type = $type;
                $this->setStatusCode((int)$transacao->getStatus());
                break;
            default:
                throw new InvalidArgumentException('Argument type (' . $type . ') is invalid');
        }
    }

    /**
     *
     * Retorna a transação do Evento
     *
     * @return \Tritoq\Payment\Cielo\Transacao
     */
    public function getTransacao()
    {
        return $this->transacao;
    }

    /**
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        if ($this->statusCode === 0) {
            $this->errorMessage = 'TID para transação inválido / ou transação nula';
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
