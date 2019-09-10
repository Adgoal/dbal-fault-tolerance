<?php

namespace Adgoal\DBALFaultTolerance\Connections;

use Adgoal\DBALFaultTolerance\ConnectionInterface;
use Adgoal\DBALFaultTolerance\ConnectionTrait;
use Doctrine\DBAL\Connections\MasterSlaveConnection as DBALMasterSlaveConnection;

/**
 * Class MasterSlaveConnection.
 */
class MasterSlaveConnection extends DBALMasterSlaveConnection implements ConnectionInterface
{
    use ConnectionTrait;
}
