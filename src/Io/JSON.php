<?php

declare(strict_types=1);

namespace Gadget\Io;

final class JSON
{
    /**
     * @param string $json
     * @param int $flags
     * @param int<1,2147483647> $depth
     * @return mixed
     */
    public static function decode(
        string $json,
        int $flags = 0,
        int $depth = 512
    ): mixed {
        return json_decode(
            $json,
            true,
            $depth,
            $flags | JSON_THROW_ON_ERROR
        );
    }


    /**
     * @param mixed $value
     * @param int $flags
     * @param int<1,2147483647> $depth
     * @return string
     */
    public static function encode(
        mixed $value,
        int $flags = 0,
        int $depth = 512
    ): string {
        return json_encode(
            $value,
            $flags | JSON_THROW_ON_ERROR,
            $depth
        );
    }
}
