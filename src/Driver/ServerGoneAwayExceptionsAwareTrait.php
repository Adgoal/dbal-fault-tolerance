<?php

namespace Adgoal\DBALFaultTolerance\Driver;

use Throwable;

/**
 * Trait ServerGoneAwayExceptionsAwareTrait.
 */
trait ServerGoneAwayExceptionsAwareTrait
{
    /** @var string[] */
    protected $goneAwayExceptions = [
        'MySQL server has gone away', //code 2006
        'Lost connection to MySQL server during query', //code 2013
        'The MySQL server is running with the --read-only option so it cannot execute this statement',
        'Connection refused',
        'Lost connection to MySQL server during query', //code 2013
        'Deadlock found when trying to get lock', //code 1213
        'Lock wait timeout exceeded', //code 1205
    ];

    /** @var string[] */
    protected $goneAwayInUpdateExceptions = [
        'MySQL server has gone away',
        'The MySQL server is running with the --read-only option so it cannot execute this statement',
        'Connection refused',
        'Deadlock found when trying to get lock', //code 1213
        'Lock wait timeout exceeded', //code 1205
    ];

    /**
     * @param Throwable $exception
     *
     * @return bool
     */
    public function isGoneAwayException(Throwable $exception): bool
    {
        $message = $exception->getMessage();

        foreach ($this->goneAwayExceptions as $goneAwayException) {
            if (false !== stripos($message, $goneAwayException)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Throwable $exception
     *
     * @return bool
     */
    public function isGoneAwayInUpdateException(Throwable $exception): bool
    {
        $message = $exception->getMessage();

        foreach ($this->goneAwayInUpdateExceptions as $goneAwayException) {
            if (false !== stripos($message, $goneAwayException)) {
                return true;
            }
        }

        return false;
    }
}
