<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Throwable;

/**
 * Interface ConnectionInterface.
 */
interface ConnectionInterface extends DriverConnection
{
    /**
     * @param string $sql
     *
     * @return Statement
     */
    public function prepareUnwrapped(string $sql): \Doctrine\DBAL\Driver\Statement;

    /**
     * @param int  $attempt
     * @param bool $ignoreTransactionLevel
     *
     * @return bool
     */
    public function canTryAgain(int $attempt, bool $ignoreTransactionLevel = false): bool;

    /**
     * @param Throwable   $e
     * @param string|null $query
     *
     * @return bool
     */
    public function isRetryableException(Throwable $e, string $query = null): bool;

    /**
     * @return mixed
     */
    public function close();

    /**
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager();
}
