<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance\Events;

use Adgoal\DBALFaultTolerance\Events\Args\ReconnectEventArgs;
use Doctrine\Common\EventSubscriber;
use Psr\Log\LoggerInterface;

/**
 * Class EventListener.
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
    public function getSubscribedEvents(): array
    {
        return [
            Events::RECONNECT_TO_DATABASE,
        ];
    }

    /**
     * @param ReconnectEventArgs $args
     */
    public function reconnectToDatabase(ReconnectEventArgs $args): void
    {
        $this->logger->debug(
            '[DOCTRINE][{function}] Retrying query (attempt {attempt}): {query}',
            ['function' => $args->getFunction(), 'attempt' => $args->getAttempt(), 'query' => $args->getQuery()]
        );
    }
}
