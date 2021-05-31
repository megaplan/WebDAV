<?php

namespace Grale\WebDav\Header\TimeoutValue;

/**
 *
 * @author samizdam
 *
 */
class NativeIntValue implements TimeoutValueInterface
{

    /**
     *
     * @var int
     */
    private $value;

    public function __construct($value)
    {
        $this->value = (int)$value;
    }

    /**
     *
     * (non-PHPdoc)
     *
     * @return string
     * @see \Grale\WebDav\Header\TimeoutValue\TimeoutValueInterface::__toString()
     *
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     *
     * (non-PHPdoc)
     *
     * @return boolean
     * @see \Grale\WebDav\Header\TimeoutValue\TimeoutValueInterface::isInfinite()
     *
     */
    public function isInfinite()
    {
        return $this->value < 0;
    }

    /**
     *
     * (non-PHPdoc)
     *
     * @param unknown $time
     * @return number
     * @see \Grale\WebDav\Header\TimeoutValue\TimeoutValueInterface::getValidity()
     *
     */
    public function getValidity($time)
    {
        return $this->value + $time;
    }
}