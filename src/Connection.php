<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Doctrine\DBAL\Connection as DBALConnection;

/**
 * Class Connection.
 */
class Connection extends DBALConnection implements ConnectionInterface
{
    use ConnectionTrait;
}
