<?php

declare(strict_types=1);

namespace Gadget\Io;

use Gadget\Exception\CastException;

final class Cast
{
    private const BOOL_VALUES = ['1', 'O', 'T', 'X', 'Y'];


    /**
     * @param mixed $value
     * @return mixed[]
     */
    public static function toArray(mixed $value): array
    {
        return match (true) {
            is_array($value) => $value,
            is_object($value) => get_object_vars($value),
            is_string($value) => self::toArray(JSON::decode($value)),
            default => throw new CastException($value, gettype([]))
        };
    }


    /**
     * @param mixed $value
     * @return bool
     */
    public static function toBool(mixed $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_scalar($value) || $value instanceof \Stringable => in_array(
                strtoupper(substr(strval($value), 0, 1)),
                self::BOOL_VALUES,
                true
            ),
            default => throw new CastException($value, gettype(false))
        };
    }


    /**
     * @param mixed $value
     * @return float
     */
    public static function toFloat(mixed $value): float
    {
        return match (true) {
            is_float($value) => $value,
            is_scalar($value) => floatval($value),
            $value instanceof \Stringable => floatval($value->__toString()),
            default => throw new CastException($value, gettype(0.0))
        };
    }


    /**
     * @param mixed $value
     * @return int
     */
    public static function toInt(mixed $value): int
    {
        return match (true) {
            is_int($value) => $value,
            is_scalar($value) => intval($value),
            $value instanceof \Stringable => intval($value->__toString()),
            default => throw new CastException($value, gettype(0))
        };
    }


    /**
     * @param mixed $value
     * @return string
     */
    public static function toString(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_scalar($value) || $value instanceof \Stringable => strval($value),
            default => throw new CastException($value, gettype(''))
        };
    }


    /**
     * @template TCastValue
     * @param mixed $values
     * @param (callable(mixed $value):TCastValue) $toValue
     * @return TCastValue[]
     */
    public static function toTypedArray(
        mixed $values,
        callable $toValue
    ): array {
        return array_map(
            $toValue,
            array_values(self::toArray($values))
        );
    }


    /**
     * @template TCastValue
     * @param mixed $values
     * @param (callable(mixed $value):TCastValue) $toValue
     * @param (callable(TCastValue $value):string) $key
     * @return array<string,TCastValue>
     */
    public static function toTypedMap(
        mixed $values,
        callable $toValue,
        callable $key
    ): array {
        return array_column(
            array_map(
                /**
                 * @param TCastValue $v
                 * @return array{TCastValue,string}
                 */
                fn(mixed $v): array => [$v, $key($v)],
                self::toTypedArray($values, $toValue)
            ),
            0,
            1
        );
    }


    /**
     * @template TCastValue
     * @param mixed $value
     * @param (callable(mixed $value):TCastValue) $toValue
     * @return TCastValue|null
     */
    public static function toValueOrNull(
        mixed $value,
        callable $toValue
    ): mixed {
        return $value !== null ? $toValue($value) : $value;
    }
}
