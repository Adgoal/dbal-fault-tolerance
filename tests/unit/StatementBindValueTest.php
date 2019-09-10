<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Doctrine\DBAL\Statement as DBALStatement;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class StatementBindValueTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider dataProvider
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function test_bind_value_to_statement($arg1, $arg2, $arg3, $return)
    {
        $statmentFake = $this->getDBALStatementMock($return);
        $statment = new Statement('SELECT 1', $this->getConnectionMock($statmentFake));

        $this->assertEquals($return, $statment->bindValue($arg1, $arg2, $arg3));
        $this->assertEquals($return, $statment->bindParam($arg1, $arg2, $arg3));
        $this->assertEquals($return, $statment->setFetchMode($arg1, $arg2, $arg3));
    }

    /**
     * @param $return
     *
     * @return DBALStatement|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    public function getDBALStatementMock($return)
    {
        $mock = Mockery::mock(DBALStatement::class);
        $mock
            ->shouldReceive('bindValue')
            ->times(1)
            ->andReturn($return);
        $mock
            ->shouldReceive('bindParam')
            ->times(1)
            ->andReturn($return);
        $mock
            ->shouldReceive('setFetchMode')
            ->times(1)
            ->andReturn($return);

        return $mock;
    }

    private function getConnectionMock($return)
    {
        $mock = Mockery::mock(Connection::class);
        $mock
            ->shouldReceive('prepareUnwrapped')
            ->times(1)
            ->andReturn($return);

        return $mock;
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                'arg1',
                'arg2',
                'arg3',
                true,
            ],
            [
                'arg1',
                'arg2',
                'arg3',
                false,
            ],
        ];
    }
}
