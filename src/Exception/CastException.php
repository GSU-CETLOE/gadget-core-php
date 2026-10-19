<?php

declare(strict_types=1);

namespace Gadget\Exception;

class CastException extends Exception
{
    /**
     * @param mixed $value
     * @param string $toValue
     */
    public function __construct(
        mixed &$value,
        string $toValue,
        \Throwable|null $previous = null
    ) {
        parent::__construct(
            ["Unable to cast '%s' to '%s'", [gettype($value), $toValue]],
            $previous
        );
    }
}
