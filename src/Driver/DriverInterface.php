<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance\Driver;

use Doctrine\DBAL\Driver;
use Throwable;

interface DriverInterface extends Driver
{
    /**
     * @param Throwable $e
     *
     * @return bool
     */
    public function isGoneAwayException(Throwable $e): bool;

    /**
     * @param Throwable $e
     *
     * @return bool
     */
    public function isGoneAwayInUpdateException(Throwable $e): bool;
}
