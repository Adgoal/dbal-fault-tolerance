<?php

declare(strict_types=1);

namespace Adgoal\DBALFaultTolerance;

use Adgoal\DBALFaultTolerance\Driver\DriverInterface;
use Adgoal\DBALFaultTolerance\Driver\PDOMySql\Driver;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @throws DBALException
     */
    protected function setUp()
    {
        $driver = $this->prophesize(Driver::class)->willImplement(DriverInterface::class);
        $configuration = $this->prophesize(Configuration::class);
        $eventManager = $this->prophesize(EventManager::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $params = [
            'driverOptions' => [
                'x_reconnect_attempts' => 3,
            ],
            'platform' => $platform->reveal(),
        ];

        /** @var Driver $driverReveal */
        $driverReveal = $driver->reveal();
        $this->connection = new Connection(
            $params,
            $driverReveal,
            $configuration->reveal(),
            $eventManager->reveal()
        );
    }

    /**
     * @throws DBALException
     */
    public function testConstructor()
    {
        $driver = $this->prophesize(Driver::class)->willImplement(DriverInterface::class);
        $configuration = $this->prophesize(Configuration::class);
        $eventManager = $this->prophesize(EventManager::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $params = [
            'driverOptions' => [
                'x_reconnect_attempts' => 999,
            ],
            'platform' => $platform->reveal(),
        ];

        /** @var Driver $driverReveal */
        $driverReveal = $driver->reveal();
        $connection = new Connection(
            $params,
            $driverReveal,
            $configuration->reveal(),
            $eventManager->reveal()
        );

        static::assertInstanceOf(Connection::class, $connection);
    }

    /**.
     * @throws DBALException
     */
    public function testConstructorWithInvalidDriver()
    {
        $driver = $this->prophesize(Driver::class);
        $configuration = $this->prophesize(Configuration::class);
        $eventManager = $this->prophesize(EventManager::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $params = [
            'driverOptions' => [
                'x_reconnect_attempts' => 999,
            ],
            'platform' => $platform->reveal(),
        ];

        $connection = new Connection(
            $params,
            $driver->reveal(),
            $configuration->reveal(),
            $eventManager->reveal()
        );

        static::assertInstanceOf(Connection::class, $connection);
    }

    /**
     * @dataProvider isUpdateQueryDataProvider
     *
     * @param string $query
     * @param bool   $expected
     */
    public function testIsUpdateQuery($query, $expected)
    {
        static::assertEquals($expected, $this->connection->isUpdateQuery($query));
    }

    public function isUpdateQueryDataProvider()
    {
        return [
            ['UPDATE ', true],
            ['DELETE ', true],
            ['DELETE ', true],
            ['SELECT ', false],
            ["\n\n\tSELECT\n", false],
            ['select ', false],
            ["\n\tSELECT\n", false],
            ['(select ', false],
            [' (select ', false],
            [' 
            (select ', false],
            [' UPDATE WHERE (SELECT ', true],
            [' UPDATE WHERE 
            (select ', true],
        ];
    }
}
