<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\Statement as DcStatement;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Class StatementTest.
 */
class StatementTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @param $arg
     * @param $result
     * @param $times
     *
     * @return Connection|\Mockery\LegacyMockInterface|MockInterface| Connection
     */
    private function getConnectionMock($arg, $result, $times)
    {
        $mock = Mockery::mock(Connection::class);
        $mock->shouldReceive('prepareUnwrapped')
            ->withArgs($arg)
            ->times($times)
            ->andReturn($result);

        return $mock;
    }

    /**
     * @throws DBALException
     */
    public function test_retry()
    {
        $sql = 'SELECT :param';
        /** @var DriverStatement|ObjectProphecy $driverStatement1 */
        $driverStatement1 = $this->prophesize(DriverStatement::class);
        /** @var DriverStatement|ObjectProphecy $driverStatement2 */
        $driverStatement2 = $this->prophesize(DriverStatement::class);
        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection
            ->prepareUnwrapped($sql)
            ->willReturn($driverStatement1->reveal(), $driverStatement2->reveal())
            ->shouldBeCalledTimes(2);

        $connection
            ->getEventManager()
            ->willReturn(new EventManager())
            ->shouldBeCalledTimes(1);

        $statement = new Statement($sql, $connection->reveal());

        $exception = new DBALException('Test');
        $driverStatement1->execute(['param' => 'value'])->willThrow($exception)->shouldBeCalledTimes(1);

        $connection->canTryAgain(0)->willReturn(true)->shouldBeCalledTimes(1);
        $connection->isRetryableException($exception, $sql)->willReturn(true)->shouldBeCalledTimes(1);

        // retry
        $connection->close()->shouldBeCalledTimes(1);
        $driverStatement2->execute(['param' => 'value'])->willReturn(true)->shouldBeCalledTimes(1);

        $this->assertTrue($statement->execute(['param' => 'value']));
    }

    /**
     * @throws DBALException
     */
    public function test_retry_with_state()
    {
        $sql = 'SELECT :value, :param';
        /** @var DriverStatement|ObjectProphecy $driverStatement1 */
        $driverStatement1 = $this->prophesize(DriverStatement::class);
        /** @var DriverStatement|ObjectProphecy $driverStatement2 */
        $driverStatement2 = $this->prophesize(DriverStatement::class);
        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection
            ->prepareUnwrapped($sql)
            ->willReturn($driverStatement1->reveal(), $driverStatement2)
            ->shouldBeCalledTimes(2);

        $connection
            ->getEventManager()
            ->willReturn(new EventManager())
            ->shouldBeCalledTimes(1);

        $statement = new Statement($sql, $connection->reveal());

        $param = 1;
        $driverStatement1->bindParam('param', $param, PDO::PARAM_INT, null)->willReturn(true)->shouldBeCalledTimes(1);
        $driverStatement1->bindValue('value', 'foo', PDO::PARAM_STR)->willReturn(true)->shouldBeCalledTimes(1);
        $driverStatement1->setFetchMode(PDO::FETCH_COLUMN, 1, null)->willReturn(true)->shouldBeCalledTimes(1);

        $this->assertTrue($statement->bindParam('param', $param, PDO::PARAM_INT));
        $this->assertTrue($statement->bindValue('value', 'foo'));
        $this->assertTrue($statement->setFetchMode(PDO::FETCH_COLUMN, 1));

        $exception = new DBALException('Test');
        $driverStatement1->execute(null)->willThrow($exception)->shouldBeCalledTimes(1);

        $connection->canTryAgain(0)->willReturn(true)->shouldBeCalledTimes(1);
        $connection->isRetryableException($exception, $sql)->willReturn(true)->shouldBeCalledTimes(1);

        // retry
        $connection->close()->shouldBeCalledTimes(1);
        $driverStatement2->bindParam('param', $param, PDO::PARAM_INT, null)->willReturn(true)->shouldBeCalledTimes(1);
        $driverStatement2->bindValue('value', 'foo', PDO::PARAM_STR)->willReturn(true)->shouldBeCalledTimes(1);
        $driverStatement2->setFetchMode(PDO::FETCH_COLUMN, 1, null)->willReturn(true)->shouldBeCalledTimes(1);
        $driverStatement2->execute(null)->willReturn(true)->shouldBeCalledTimes(1);

        $this->assertTrue($statement->execute());
    }

    /**
     * @throws DBALException
     */
    public function test_retry_fails()
    {
        $sql = 'SELECT 1';
        /** @var DriverStatement|ObjectProphecy $driverStatement1 */
        $driverStatement1 = $this->prophesize(DriverStatement::class);
        /** @var DriverStatement|ObjectProphecy $driverStatement2 */
        $driverStatement2 = $this->prophesize(DriverStatement::class);
        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection
            ->prepareUnwrapped($sql)
            ->willReturn($driverStatement1->reveal(), $driverStatement2->reveal())
            ->shouldBeCalledTimes(2);

        $connection
            ->getEventManager()
            ->willReturn(new EventManager())
            ->shouldBeCalledTimes(1);

        $statement = new Statement($sql, $connection->reveal());

        $exception1 = new DBALException('Test1');
        $driverStatement1->execute(null)->willThrow($exception1)->shouldBeCalledTimes(1);

        $connection->canTryAgain(0)->willReturn(true)->shouldBeCalledTimes(1);
        $connection->isRetryableException($exception1, $sql)->willReturn(true)->shouldBeCalledTimes(1);

        // retry
        $connection->close()->shouldBeCalledTimes(1);
        $exception2 = new DBALException('Test2');
        $driverStatement2->execute(null)->willThrow($exception2)->shouldBeCalledTimes(1);

        $connection->canTryAgain(1)->willReturn(true)->shouldBeCalledTimes(1);
        $connection->isRetryableException($exception2, $sql)->willReturn(false)->shouldBeCalledTimes(1);

        $this->expectException(get_class($exception2));
        $this->expectExceptionMessage($exception2->getMessage());

        $this->assertTrue($statement->execute());
    }

    /**
     * @throws DBALException
     */
    public function test_execute()
    {
        $sql = 'SELECT 1';

        $dcStatement = $this->getDcStatementMock([['test' => 1]], true, 1);

        $connection = $this->getConnectionMock([$sql], $dcStatement, 1);

        $statement = new Statement($sql, $connection);
        $this->assertTrue($statement->execute(['test' => 1]));
    }

    /**
     * @throws DBALException
     */
    public function test_execute_gone_away_not_retrayable()
    {
        $sql = 'SELECT 1';
        /** @var DcStatement|ObjectProphecy $dcStatement */
        $dcStatement = $this->prophesize(DcStatement::class);
        $dcStatement->execute(['test' => 1])->willThrow(new \Exception('test'));

        $connection = $this->mockBaseConnection($sql, $dcStatement->reveal());
        $connection->canTryAgain(0)->willReturn(false);
        $connection->close()->shouldNotBeCalled();
        $connection->isRetryableException(Argument::type(\Throwable::class), $sql)->willReturn(false);

        $statement = new Statement($sql, $connection->reveal());

        $this->expectException(\Throwable::class);
        $statement->execute(['test' => 1]);
    }

    /**
     * @param             $sql
     * @param DcStatement $statement
     *
     * @return Connection|\Prophecy\Prophecy\ObjectProphecy
     */
    private function mockBaseConnection($sql, $statement = null)
    {
        $connection = $this
            ->prophesize(Connection::class);
        $connection
            ->prepareUnwrapped($sql)
            ->shouldBeCalled()
            ->willReturn($statement);

        return $connection;
    }

    /**
     * @param $arg
     * @param $result
     * @param $times
     *
     * @return DcStatement|\Mockery\LegacyMockInterface|MockInterface|DcStatement
     */
    private function getDcStatementMock($arg, $result, $times)
    {
        $mock = Mockery::mock(DcStatement::class);
        $mock->shouldReceive('execute')
            ->withArgs($arg)
            ->times($times)
            ->andReturn($result);

        return $mock;
    }
}
