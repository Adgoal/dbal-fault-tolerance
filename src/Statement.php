<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Adgoal\DBALFaultTolerance\Events\Args\ReconnectEventArgs;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use IteratorAggregate;
use PDO;
use Throwable;
use Traversable;

/**
 * Class Statement.
 */
class Statement implements IteratorAggregate, DriverStatement
{
    /**
     * @var string
     */
    protected $sql;

    /**
     * @var \Doctrine\DBAL\Statement
     */
    protected $stmt;

    /**
     * @var ConnectionInterface
     */
    protected $conn;

    /**
     * @var array
     */
    private $boundValues = [];

    /**
     * @var array
     */
    private $boundParams = [];

    /**
     * @var array|null
     */
    private $fetchMode;

    /**
     * Statement constructor.
     *
     * @param string              $sql
     * @param ConnectionInterface $conn
     */
    public function __construct(string $sql, ConnectionInterface $conn)
    {
        $this->sql = $sql;
        $this->conn = $conn;
        $this->createStatement();
    }

    /**
     * @param array|null $params
     *
     * @return bool
     *
     * @throws Throwable
     */
    public function execute($params = null)
    {
        $stmt = false;
        $attempt = 0;
        $retry = true;
        while ($retry) {
            $retry = false;

            try {
                $stmt = $this->stmt->execute($params);
            } catch (Throwable $e) {
                if (
                    $this->conn->canTryAgain($attempt)
                    &&
                    $this->conn->isRetryableException($e, $this->sql)
                ) {
                    $this->conn->close();
                    $this->recreateStatement();
                    ++$attempt;
                    $retry = true;

                    $this->conn->getEventManager()->dispatchEvent(
                        Events\Events::RECONNECT_TO_DATABASE,
                        new ReconnectEventArgs(__FUNCTION__, $attempt, $this->sql)
                    );
                } else {
                    throw $e;
                }
            }
        }

        return $stmt;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param mixed  $type
     *
     * @return bool
     */
    public function bindValue($name, $value, $type = PDO::PARAM_STR)
    {
        if ($this->stmt->bindValue($name, $value, $type)) {
            $this->boundValues[$name] = [$name, $value, $type];

            return true;
        }

        return false;
    }

    /**
     * @param string   $name
     * @param mixed    $var
     * @param int      $type
     * @param int|null $length
     *
     * @return bool
     */
    public function bindParam($name, &$var, $type = PDO::PARAM_STR, $length = null)
    {
        if ($this->stmt->bindParam($name, $var, $type, $length)) {
            $this->boundParams[$name] = [$name, &$var, $type, $length];

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function closeCursor()
    {
        return $this->stmt->closeCursor();
    }

    /**
     * @return int
     */
    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    /**
     * @return string|int|bool
     */
    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    /**
     * @return array
     */
    public function errorInfo()
    {
        return $this->stmt->errorInfo();
    }

    /**
     * @param int   $fetchMode
     * @param mixed $arg2
     * @param mixed $arg3
     *
     * @return bool
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        if ($this->stmt->setFetchMode($fetchMode, $arg2, $arg3)) {
            $this->fetchMode = [$fetchMode, $arg2, $arg3];

            return true;
        }

        return false;
    }

    /**
     * @return Traversable
     */
    public function getIterator()
    {
        return $this->stmt;
    }

    /**
     * @param int|null $fetchMode
     * @param int      $cursorOrientation Only for doctrine/DBAL >= 2.6
     * @param int      $cursorOffset      Only for doctrine/DBAL >= 2.6
     *
     * @return mixed
     */
    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->stmt->fetch($fetchMode, $cursorOrientation, $cursorOffset);
    }

    /**
     * @param int|null $fetchMode
     * @param int      $fetchArgument Only for doctrine/DBAL >= 2.6
     * @param null     $ctorArgs      Only for doctrine/DBAL >= 2.6
     *
     * @return mixed
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        return $this->stmt->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
    }

    /**
     * @param int $columnIndex
     *
     * @return mixed
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->stmt->fetchColumn($columnIndex);
    }

    /**
     * @return int
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Create Statement.
     */
    private function createStatement()
    {
        $this->stmt = $this->conn->prepareUnwrapped($this->sql);
    }

    /**
     * Recreate statement for retry.
     */
    private function recreateStatement()
    {
        $this->createStatement();
        if ($this->fetchMode !== null) {
            call_user_func_array([$this->stmt, 'setFetchMode'], $this->fetchMode);
        }
        foreach ($this->boundValues as $boundValue) {
            call_user_func_array([$this->stmt, 'bindValue'], $boundValue);
        }
        foreach ($this->boundParams as $boundParam) {
            call_user_func_array([$this->stmt, 'bindParam'], $boundParam);
        }
    }
}
