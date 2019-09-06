<?php

namespace Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Driver;

use Exception;

/**
 * Class ServerGoneAwayExceptionsAwareInterface.
 */
interface ServerGoneAwayExceptionsAwareInterface
{
    /**
     * @param Exception $e
     *
     * @return bool
     */
    public function isGoneAwayException(Exception $e);

    /**
     * @param Exception $e
     *
     * @return bool
     */
    public function isGoneAwayInUpdateException(Exception $e);
}
