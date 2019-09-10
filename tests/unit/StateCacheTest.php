<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class StateCacheTest.
 */
class StateCacheTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @covers \Adgoal\DBALFaultTolerance\Statement
     *
     * @throws DBALException
     */
    public function testStateCacheOnlyChangedOnSuccess()
    {
        $sql = 'SELECT :value, :param';

        $driverStatementNormal = $this->getDriverStatementMock(false, true);
        $driverStatementError = $this->getDriverStatementMock(true, true, DBALException::class);

        $connection = $this->getConnectionMock([$sql], $driverStatementError, $driverStatementNormal);

        $statement = new Statement($sql, $connection);

        $this->assertTrue($statement->execute());
    }

    /**
     * @param string          $arg
     * @param DriverStatement $driverError
     * @param DriverStatement $driverNormal
     *
     * @return Connection|Mockery\LegacyMockInterface|MockInterface
     */
    private function getConnectionMock($arg, $driverError, $driverNormal)
    {
        $mock = Mockery::mock(Connection::class);
        $mock->shouldReceive('prepareUnwrapped')
            ->withArgs($arg)
            ->times(2)
            ->andReturn($driverError, $driverNormal);

        $mock->shouldReceive('canTryAgain')
            ->times(1)
            ->andReturnTrue();

        $mock->shouldReceive('isRetryableException')
            ->times(1)
            ->andReturnTrue();

        $mock->shouldReceive('close')
            ->times(1);

        $mock->shouldReceive('getEventManager')
            ->andReturn(new EventManager())
            ->times(1);

        return $mock;
    }

    /**
     * @param $isError
     * @param $stmt
     * @param string $exception
     *
     * @return DriverStatement|Mockery\LegacyMockInterface|MockInterface
     */
    private function getDriverStatementMock($isError, $stmt, $exception = null)
    {
        $mock = Mockery::mock(DriverStatement::class);
        $isn = $mock
            ->shouldReceive('execute')
            ->times(1);
        if ($isError) {
            $isn->andThrow($exception, 'Test', 1);
        } else {
            $isn->andReturn($stmt);
        }

        return $mock;
    }
}
