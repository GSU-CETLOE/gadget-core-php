<?php

declare(strict_types=1);

namespace Gadget\Io;

final class Base32
{
    public const BITS_5_RIGHT = 31;
    public const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789';


    /**
     * @param string $data
     * @param bool $padRight
     * @return string
     */
    public static function encode(
        string $data,
        bool $padRight = false
    ): string {
        $dataSize = strlen($data);
        $res = '';
        $remainder = 0;
        $remainderSize = 0;

        for ($i = 0; $i < $dataSize; ++$i) {
            $b = ord($data[$i]);
            $remainder = ($remainder << 8) | $b;
            $remainderSize += 8;

            while ($remainderSize > 4) {
                $remainderSize -= 5;
                $c = $remainder & (self::BITS_5_RIGHT << $remainderSize);
                $c >>= $remainderSize;
                $res .= self::CHARS[$c];
            }
        }

        if ($remainderSize > 0) {
            $remainder <<= (5 - $remainderSize);
            $c = $remainder & self::BITS_5_RIGHT;
            $res .= self::CHARS[$c];
        }

        if ($padRight) {
            $padSize = (8 - ceil(($dataSize % 5) * 8 / 5)) % 8;
            $res .= str_repeat('=', $padSize);
        }

        return $res;
    }


    /**
     * @param string $data
     * @return string
     */
    public static function decode(string $data): string
    {
        $charMap = array_flip(str_split(self::CHARS));
        $charMap += array_flip(str_split(strtolower(self::CHARS)));

        $data = rtrim($data, "=\x20\t\n\r\0\x0B");
        $dataSize = strlen($data);
        $buf = 0;
        $bufSize = 0;
        $res = '';

        for ($i = 0; $i < $dataSize; ++$i) {
            $c = $data[$i];
            $b = $charMap[$c];
            $buf = ($buf << 5) | $b;
            $bufSize += 5;
            if ($bufSize > 7) {
                $bufSize -= 8;
                $b = ($buf & (0xff << $bufSize)) >> $bufSize;
                $res .= chr($b);
            }
        }

        return $res;
    }
}
