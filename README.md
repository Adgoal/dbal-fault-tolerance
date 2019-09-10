[![Latest Stable Version](https://poser.pugx.org/adgoal/doctrine-mysql-come-back/v/stable.svg)](https://packagist.org/packages/adgoal/doctrine-mysql-come-back) 
[![Latest Unstable Version](https://poser.pugx.org/adgoal/doctrine-mysql-come-back/v/unstable.svg)](https://packagist.org/packages/adgoal/doctrine-mysql-come-back) 
[![Total Downloads](https://poser.pugx.org/adgoal/doctrine-mysql-come-back/downloads.svg)](https://packagist.org/packages/adgoal/doctrine-mysql-come-back) 

[![Build status](https://travis-ci.org/adgoal/doctrine-mysql-come-back.svg)]( https://travis-ci.org/adgoal/doctrine-mysql-come-back)
[![Scrutinizer score](https://scrutinizer-ci.com/g/adgoal/doctrine-mysql-come-back/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/adgoal/doctrine-mysql-come-back/?branch=master)
[![Test coverage](https://scrutinizer-ci.com/g/adgoal/doctrine-mysql-come-back/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/adgoal/doctrine-mysql-come-back/?branch=master)

[![License](https://poser.pugx.org/adgoal/doctrine-mysql-come-back/license.svg)](https://packagist.org/packages/adgoal/doctrine-mysql-come-back)
# DBALFaultTolerance

Auto reconnect on Doctrine MySql has gone away exceptions on doctrine/dbal >=2.3, <3.0.

# Installation

```console
$ composer require adgoal/doctrine-mysql-come-back ^1.7
```

# Configuration

In order to use DBALFaultTolerance you have to set `wrapperClass` and `driverClass` connection params.
You can choose how many times Doctrine should be able to reconnect, setting `x_reconnect_attempts` driver option. Its value should be an int.

You can force ignore the transaction level using the parameters : `force_ignore_transaction_level`.

An example of configuration at connection instantiation time:

```php
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$config = new Configuration();

//..

$connectionParams = array(
    'dbname' => 'mydb',
    'user' => 'user',
    'password' => 'secret',
    'host' => 'localhost',
    // [doctrine-mysql-come-back] settings
    'wrapperClass' => Adgoal\DBALFaultTolerance\Connection::class,
    'driverClass' => Adgoal\DBALFaultTolerance\Driver\PDOMySql\Driver:class,
    'driverOptions' => [
        'x_reconnect_attempts' => 3,
        'force_ignore_transaction_level' => true
    ]
);

$conn = DriverManager::getConnection($connectionParams, $config);

//..
```

An example of yaml configuration on Symfony 2 projects:

```yaml
# Doctrine example Configuration
doctrine:
    dbal:
        default_connection: %connection_name%
        connections:
            %connection_name%:
                host:     %database_host%
                port:     %database_port%
                dbname:   %database_name%
                user:     %database_user%
                password: %database_password%
                charset:  UTF8
                wrapper_class: 'Adgoal\DBALFaultTolerance\Connection'
                driver_class: 'Adgoal\DBALFaultTolerance\Driver\PDOMySql\Driver'
                options:
                    x_reconnect_attempts: 3
```

An example of configuration on Zend Framework 2/3 projects:

```php
return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => \Adgoal\DBALFaultTolerance\Driver\PDOMySql\Driver::class,
                'wrapperClass' => \Adgoal\DBALFaultTolerance\Connection::class,
                'params' => [
                    'host' => 'localhost',
                    'port' => '3307',
                    'user' => '##user##',
                    'password' => '##password##',
                    'dbname' => '##database##',
                    'charset' => 'UTF8',
                    'driverOptions' => [
                        'x_reconnect_attempts' => 9,
                        'force_ignore_transaction_level' => true
                    ]
                ],
            ],
        ],
    ],
];
```

You can use wrapper class `Adgoal\DBALFaultTolerance\Connections\MasterSlaveConnection` if you are 
using master / slave Doctrine configuration.

# Usage

To force a reconnection try after a long running task you can call
```php
$em->getConnection()->refresh();
```
before performing any other operation different from SELECT.

Instead, in case your next query will be a SELECT, reconnection will be automagically done.

From `v1.6` automagically reconnection is enabled also during `$em->getConnection()->beginTransaction()` calls,
and this works also during simple `$em->flush()`, if out of a previous transaction.

# Thanks

Thanks to Dieter Peeters and his proposal on [DBAL-275](https://github.com/doctrine/dbal/issues/1454).
Check it out if you are using doctrine/dbal <2.3.
