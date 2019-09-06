<?php

namespace Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Events\Args;

use Doctrine\Common\EventArgs;

/**
 * Class ReconnectEventArgs
 * @package Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Events\Args
 */
class ReconnectEventArgs extends EventArgs
{
    /**
     * @var string
     */
    private $function;

    /**
     * @var int
     */
    private $attempt;

    /**
     * @var string
     */
    private $query;

    /**
     * @var mixed|null
     */
    private $args;

    /**
     * ReconnectEventArgs constructor.
     *
     * @param string $function
     * @param int $attempt
     * @param string $query
     * @param mixed $args
     */
    public function __construct($function, $attempt, $query, $args = null)
    {
        $this->function = $function;
        $this->attempt = $attempt;
        $this->query = $query;
        $this->args = $args;
    }

    /**
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return int
     */
    public function getAttempt()
    {
        return $this->attempt;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return mixed|null
     */
    public function getArgs()
    {
        return $this->args;
    }


}