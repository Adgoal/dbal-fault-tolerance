<?php

namespace Adgoal\DBALFaultTolerance\Driver\PDOMySql;

use Adgoal\DBALFaultTolerance\Driver\DriverInterface;
use Adgoal\DBALFaultTolerance\Driver\ServerGoneAwayExceptionsAwareTrait;

/**
 * Class Driver.
 */
class Driver extends \Doctrine\DBAL\Driver\PDOMySql\Driver implements DriverInterface
{
    use ServerGoneAwayExceptionsAwareTrait;
}
