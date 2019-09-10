<?php

namespace Adgoal\DBALFaultTolerance\Driver\Mysqli;

use Adgoal\DBALFaultTolerance\Driver\DriverInterface;
use Adgoal\DBALFaultTolerance\Driver\ServerGoneAwayExceptionsAwareTrait;
use Doctrine\DBAL\DBALException;

/**
 * Class Driver.
 */
class Driver extends \Doctrine\DBAL\Driver\Mysqli\Driver implements DriverInterface
{
    use ServerGoneAwayExceptionsAwareTrait;

    /**
     * @var array
     */
    private $extendedDriverOptions = [
        'x_reconnect_attempts',
    ];

    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        $driverOptions = array_diff_key($driverOptions, array_flip($this->extendedDriverOptions));

        return parent::connect($params, $username, $password, $driverOptions);
    }
}
