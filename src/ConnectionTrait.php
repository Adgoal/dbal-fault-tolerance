<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Adgoal\DBALFaultTolerance\Driver\DriverInterface;
use Adgoal\DBALFaultTolerance\Events\Args\ReconnectEventArgs;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Throwable;

/**
 * Trait ConnectionTrait.
 */
trait ConnectionTrait
{
    /**
     * The used DBAL driver.
     *
     *  Overwrite for type hint
     *
     * @var DriverInterface
     */
    protected $_driver;

    /**
     * @var int
     */
    protected $reconnectAttempts = 0;

    /**
     * @var int|bool
     */
    protected $forceIgnoreTransactionLevel;

    /**
     * @var ReflectionProperty|null
     */
    private $selfReflectionNestingLevelProperty;

    /**
     * ConnectionTrait constructor.
     *
     * @param mixed[]            $params
     * @param Driver             $driver
     * @param Configuration|null $config
     * @param EventManager|null  $eventManager
     *
     * @throws DBALException
     */
    public function __construct(
        array $params,
        Driver $driver,
        Configuration $config = null,
        EventManager $eventManager = null
    ) {
        if (! $driver instanceof DriverInterface) {
            throw new InvalidArgumentException(
                sprintf('%s needs a driver that implements DriverInterface', static::class)
            );
        }

        if (isset($params['driverOptions']['x_reconnect_attempts'])) {
            $this->reconnectAttempts = (int) $params['driverOptions']['x_reconnect_attempts'];
        }

        if (isset($params['driverOptions']['force_ignore_transaction_level'])) {
            $this->forceIgnoreTransactionLevel = (int) $params['driverOptions']['force_ignore_transaction_level'];
        }

        parent::__construct($params, $driver, $config, $eventManager);
    }

    /**
     * @param string            $query
     * @param array             $params
     * @param array             $types
     * @param QueryCacheProfile $qcp
     *
     * @return ResultStatement the executed statement
     *
     * @throws Throwable
     */
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null)
    {
        $stmt = null;
        $attempt = 0;
        $retry = true;
        while ($retry) {
            $retry = false;

            try {
                $stmt = parent::executeQuery($query, $params, $types, $qcp);
            } catch (Throwable $e) {
                if ($this->canTryAgain($attempt) && $this->isRetryableException($e, $query)) {
                    $this->close();
                    ++$attempt;
                    $retry = true;

                    $this->getEventManager()->dispatchEvent(
                        Events\Events::RECONNECT_TO_DATABASE,
                        new ReconnectEventArgs(__FUNCTION__, $attempt, $query)
                    );
                }

                throw $e;
            }
        }

        return $stmt;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement
     *
     * @throws Throwable
     */
    public function query()
    {
        $stmt = null;
        $args = func_get_args();
        $attempt = 0;
        $retry = true;
        while ($retry) {
            $retry = false;

            try {
                switch (count($args)) {
                    case 1:
                        $stmt = parent::query($args[0]);

                        break;
                    case 2:
                        $stmt = parent::query($args[0], $args[1]);

                        break;
                    case 3:
                        $stmt = parent::query($args[0], $args[1], $args[2]);

                        break;
                    case 4:
                        $stmt = parent::query($args[0], $args[1], $args[2], $args[3]);

                        break;
                    default:
                        $stmt = parent::query();
                }
            } catch (Throwable $e) {
                if ($this->canTryAgain($attempt) && $this->isRetryableException($e, $args[0])) {
                    $this->close();
                    ++$attempt;
                    $retry = true;

                    $this->getEventManager()->dispatchEvent(
                        Events\Events::RECONNECT_TO_DATABASE,
                        new ReconnectEventArgs(__FUNCTION__, $attempt, count($args) ? $args[0] : '', $args)
                    );
                } else {
                    throw $e;
                }
            }
        }

        return $stmt;
    }

    /**
     * @param string $query
     * @param array  $params
     * @param array  $types
     *
     * @return int the number of affected rows
     *
     * @throws Throwable
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        $stmt = null;
        $attempt = 0;
        $retry = true;
        while ($retry) {
            $retry = false;

            try {
                $stmt = parent::executeUpdate($query, $params, $types);
            } catch (Throwable $e) {
                if ($this->canTryAgain($attempt) && $this->isRetryableException($e)) {
                    $this->close();
                    ++$attempt;
                    $retry = true;

                    $this->getEventManager()->dispatchEvent(
                        Events\Events::RECONNECT_TO_DATABASE,
                        new ReconnectEventArgs(__FUNCTION__, $attempt, $query)
                    );
                } else {
                    throw $e;
                }
            }
        }

        return $stmt;
    }

    /**
     * @return bool|void
     *
     * @throws ReflectionException
     * @throws Throwable
     */
    public function beginTransaction()
    {
        if ($this->getTransactionNestingLevel() !== 0) {
            return parent::beginTransaction();
        }

        $attempt = 0;
        $retry = true;
        while ($retry) {
            $retry = false;

            try {
                parent::beginTransaction();
            } catch (Throwable $e) {
                if (
                    $this->canTryAgain($attempt, true)
                    &&
                    $this->_driver->isGoneAwayException($e)
                ) {
                    $this->close();
                    if ($this->getTransactionNestingLevel() > 0) {
                        $this->resetTransactionNestingLevel();
                    }
                    ++$attempt;
                    $retry = true;

                    $this->getEventManager()->dispatchEvent(
                        Events\Events::RECONNECT_TO_DATABASE,
                        new ReconnectEventArgs(__FUNCTION__, $attempt, 'BEGIN TRANSACTION')
                    );
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $sql
     *
     * @return Statement the prepared statement
     */
    public function prepare($sql)
    {
        return $this->prepareWrapped($sql);
    }

    /**
     * do not use, only used by Statement-class
     * needs to be public for access from the Statement-class.
     *
     * @param $sql
     *
     * @return Driver\Statement
     *
     * @throws DBALException
     *
     * @internal
     */
    public function prepareUnwrapped(string $sql): \Doctrine\DBAL\Driver\Statement
    {
        // returns the actual statement
        return parent::prepare($sql);
    }

    /**
     * Forces reconnection by doing a dummy query.
     *
     * @throws Throwable
     */
    public function refresh(): void
    {
        $this->query('SELECT 1')->execute();
    }

    /**
     * @param $attempt
     * @param bool $ignoreTransactionLevel
     *
     * @return bool
     */
    public function canTryAgain(int $attempt, bool $ignoreTransactionLevel = false): bool
    {
        $canByAttempt = ($attempt < $this->reconnectAttempts);
        $ignoreTransactionLevel = $this->forceIgnoreTransactionLevel ? true : $ignoreTransactionLevel;

        $canByTransactionNestingLevel = $ignoreTransactionLevel ? true : $this->getTransactionNestingLevel() === 0;

        return $canByAttempt && $canByTransactionNestingLevel;
    }

    /**
     * @param Throwable   $e
     * @param string|null $query
     *
     * @return bool
     */
    public function isRetryableException(Throwable $e, string $query = null): bool
    {
        if ($query === null || $this->isUpdateQuery($query)) {
            return $this->_driver->isGoneAwayInUpdateException($e);
        }

        return $this->_driver->isGoneAwayException($e);
    }

    /**
     * @param string $query
     *
     * @return bool
     */
    public function isUpdateQuery($query): bool
    {
        return ! preg_match('/^[\s\n\r\t(]*(select|show|describe)[\s\n\r\t(]+/i', $query);
    }

    /**
     * returns a reconnect-wrapper for Statements.
     *
     * @param $sql
     *
     * @return Statement
     */
    protected function prepareWrapped(string $sql): Driver\Statement
    {
        return new Statement($sql, $this);
    }

    /**
     * This is required because beginTransaction increment transactionNestingLevel
     * before the real query is executed, and results incremented also on gone away error.
     * This should be safe for a new established connection.
     *
     * @throws ReflectionException
     * @throws ReflectionException
     */
    private function resetTransactionNestingLevel(): void
    {
        if (! $this->selfReflectionNestingLevelProperty instanceof ReflectionProperty) {
            $reflection = new ReflectionClass(\Doctrine\DBAL\Connection::class);

            // Private property has been renamed in DBAL 2.9.0+
            if ($reflection->hasProperty('transactionNestingLevel')) {
                $this->selfReflectionNestingLevelProperty = $reflection->getProperty('transactionNestingLevel');
            } else {
                $this->selfReflectionNestingLevelProperty = $reflection->getProperty('_transactionNestingLevel');
            }

            $this->selfReflectionNestingLevelProperty->setAccessible(true);
        }

        $this->selfReflectionNestingLevelProperty->setValue($this, 0);
    }
}
