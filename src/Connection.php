<?php

namespace Facile\DoctrineMySQLComeBack\Doctrine\DBAL;

use Doctrine\DBAL\Connection as DBALConnection;
use Psr\Log\LoggerAwareInterface;

/**
 * Class Connection.
 */
class Connection extends DBALConnection implements ConnectionInterface, LoggerAwareInterface
{
    use ConnectionTrait;
}
