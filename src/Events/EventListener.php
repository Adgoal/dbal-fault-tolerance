<?php

namespace Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Events;

use Doctrine\Common\EventSubscriber;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Events\Args\ReconnectEventArgs;
use Psr\Log\LoggerInterface;

/**
 * Class EventListener
 * @package Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Events
 */
class EventListener implements EventSubscriber
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::RECONNECT_TO_DATABASE
        ];
    }

    /**
     * @param ReconnectEventArgs $args
     */
    public function reconnectToDatabase(ReconnectEventArgs $args) {
        $this->logger->debug(
            '[DOCTRINE][{function}] Retrying query (attempt {attempt}): {query}',
            ['function' => $args->getFunction(), 'attempt' => $args->getAttempt(), 'query' => $args->getQuery()]
        );
    }
}