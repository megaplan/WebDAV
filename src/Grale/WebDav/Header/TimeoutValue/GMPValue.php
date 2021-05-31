<?php

namespace Grale\WebDav\Header\TimeoutValue;

use GMP;

/**
 *
 * @author samizdam
 *
 */
class GMPValue implements TimeoutValueInterface
{

    /**
     *
     * @var GMP
     */
    private $value;

    /**
     *
     * @param unknown $value
     */
    public function __construct($value)
    {
        $this->value = gmp_init((string)$value);
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
        return gmp_cmp($this->value, gmp_init(0)) < 0;
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
        return gmp_strval($this->value);
    }

    /**
     *
     * (non-PHPdoc)
     *
     * @param unknown $time
     * @return string
     * @see \Grale\WebDav\Header\TimeoutValue\TimeoutValueInterface::getValidity()
     *
     */
    public function getValidity($time)
    {
        return gmp_strval(gmp_add($this->value, gmp_init($time)));
    }
}