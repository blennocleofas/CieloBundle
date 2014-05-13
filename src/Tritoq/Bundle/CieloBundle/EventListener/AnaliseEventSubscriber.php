<?php
/**
 * @author Artur Magalhães <nezkal@gmail.com>
 */

namespace Tritoq\Bundle\CieloBundle\EventListener;


use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tritoq\Bundle\CieloBundle\Event\CieloEvent;
use Tritoq\Payment\Cielo\AnaliseRisco\AnaliseResultado;

/**
 *
 * Subscriber que escuta os eventos de transação para executar ações de análise de risco
 *
 *
 * Class AnaliseEventSubscriber
 *
 * @category  Library
 * @copyright Artur Magalhães <nezkal@gmail.com>
 * @package   Tritoq\Bundle\CieloBundle\EventListener
 * @license   GPL-3.0+
 */
class AnaliseEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher
     *
     * @return $this
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        // TODO: Implement getSubscribedEvents() method.
        return array(
            CieloEvent::CONSULTA => 'handlerEvent',
            CieloEvent::CAPTURA => 'handlerEvent',
            CieloEvent::TRANSACAO => 'handlerEvent'
        );
    }

    /**
     * Handler para que controla o disparo de eventos quando houver um retorno de analise
     *
     * @param CieloEvent $e
     */
    public function handlerEvent(CieloEvent $e)
    {
        $transacao = $e->getTransacao();

        $statusAnalise = $transacao->getStatusAnalise();

        if ($transacao->getAnaliseResultado() || isset($statusAnalise)) {
            //
            $resultado = $transacao->getAnaliseResultado();

            $status = $resultado->getStatus();

            switch ($status) {
                case AnaliseResultado::STATUS_ALTO_RISCO:
                case AnaliseResultado::STATUS_MEDIO_RISCO:
                case AnaliseResultado::STATUS_BAIXO_RISCO:
                    $event = new CieloEvent(CieloEvent::ANALISE, $transacao);
                    $this->eventDispatcher->dispatch(CieloEvent::ANALISE, $event);
                    break;
            }
        }
    }
}
