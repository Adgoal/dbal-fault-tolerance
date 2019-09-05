<?php

namespace Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Driver;

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
        'Lock wait timeout exceeded' //code 1205
    ];

    /** @var string[] */
    protected $goneAwayInUpdateExceptions = [
        'MySQL server has gone away',
        'The MySQL server is running with the --read-only option so it cannot execute this statement',
        'Connection refused',
        'Deadlock found when trying to get lock', //code 1213
        'Lock wait timeout exceeded' //code 1205
    ];

    /**
     * @param \Exception $exception
     *
     * @return bool
     */
    public function isGoneAwayException(\Exception $exception)
    {
        $message = $exception->getMessage();

        foreach ($this->goneAwayExceptions as $goneAwayException) {
            if (stripos($message, $goneAwayException) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Exception $exception
     *
     * @return bool
     */
    public function isGoneAwayInUpdateException(\Exception $exception)
    {
        $message = $exception->getMessage();

        foreach ($this->goneAwayInUpdateExceptions as $goneAwayException) {
            if (stripos($message, $goneAwayException) !== false) {
                return true;
            }
        }

        return false;
    }
}
