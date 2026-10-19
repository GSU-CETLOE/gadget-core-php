<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use Gadget\Exception\CastException;
use Gadget\Io\Cast;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CastTest extends TestCase
{
    /**
     * @param string $text
     * @return \Stringable
     */
    private static function stringable(string $text): \Stringable
    {
        return new class ($text) implements \Stringable {
            public function __construct(private string $text)
            {
            }

            public function __toString(): string
            {
                return $this->text;
            }
        };
    }


    public function testToArrayReturnsArraysUnchanged(): void
    {
        self::assertSame(['a' => 1], Cast::toArray(['a' => 1]));
    }


    public function testToArrayUsesObjectProperties(): void
    {
        $object = new \stdClass();
        $object->a = 1;
        $object->b = 'two';

        self::assertSame(['a' => 1, 'b' => 'two'], Cast::toArray($object));
    }


    public function testToArrayDecodesJsonStrings(): void
    {
        self::assertSame(['a' => 1, 'b' => [true]], Cast::toArray('{"a":1,"b":[true]}'));
    }


    public function testToArrayRejectsOtherValues(): void
    {
        $this->expectException(CastException::class);
        $this->expectExceptionMessage("Unable to cast 'integer' to 'array'");

        Cast::toArray(5);
    }


    /** @return iterable<string,array{mixed,bool}> */
    public static function boolValues(): iterable
    {
        yield 'true' => [true, true];
        yield 'false' => [false, false];
        yield 'yes' => ['yes', true];
        yield 'No' => ['No', false];
        yield 'string 1' => ['1', true];
        yield 'string 0' => ['0', false];
        yield 'int 1' => [1, true];
        yield 'int 0' => [0, false];
        yield 'T' => ['T', true];
        yield 'x' => ['x', true];
        yield 'empty string' => ['', false];
        yield 'stringable on' => [self::stringable('on'), true];
        yield 'stringable off' => [self::stringable('off'), true];
        yield 'stringable no' => [self::stringable('no'), false];
    }


    #[DataProvider('boolValues')]
    public function testToBool(mixed $value, bool $expected): void
    {
        self::assertSame($expected, Cast::toBool($value));
    }


    public function testToBoolRejectsNull(): void
    {
        $this->expectException(CastException::class);
        $this->expectExceptionMessage("Unable to cast 'NULL' to 'boolean'");

        Cast::toBool(null);
    }


    /** @return iterable<string,array{mixed,float}> */
    public static function floatValues(): iterable
    {
        yield 'float' => [1.5, 1.5];
        yield 'numeric string' => ['2.25', 2.25];
        yield 'int' => [3, 3.0];
        yield 'bool' => [true, 1.0];
        yield 'stringable' => [self::stringable('4.5'), 4.5];
    }


    #[DataProvider('floatValues')]
    public function testToFloat(mixed $value, float $expected): void
    {
        self::assertSame($expected, Cast::toFloat($value));
    }


    public function testToFloatRejectsArrays(): void
    {
        $this->expectException(CastException::class);
        $this->expectExceptionMessage("Unable to cast 'array' to 'double'");

        Cast::toFloat([]);
    }


    /** @return iterable<string,array{mixed,int}> */
    public static function intValues(): iterable
    {
        yield 'int' => [7, 7];
        yield 'numeric string' => ['42', 42];
        yield 'float truncates' => [3.9, 3];
        yield 'bool' => [true, 1];
        yield 'stringable' => [self::stringable('12'), 12];
    }


    #[DataProvider('intValues')]
    public function testToInt(mixed $value, int $expected): void
    {
        self::assertSame($expected, Cast::toInt($value));
    }


    public function testToIntRejectsNull(): void
    {
        $this->expectException(CastException::class);
        $this->expectExceptionMessage("Unable to cast 'NULL' to 'integer'");

        Cast::toInt(null);
    }


    /** @return iterable<string,array{mixed,string}> */
    public static function stringValues(): iterable
    {
        yield 'string' => ['a', 'a'];
        yield 'int' => [5, '5'];
        yield 'float' => [1.5, '1.5'];
        yield 'bool' => [true, '1'];
        yield 'stringable' => [self::stringable('text'), 'text'];
    }


    #[DataProvider('stringValues')]
    public function testToString(mixed $value, string $expected): void
    {
        self::assertSame($expected, Cast::toString($value));
    }


    public function testToStringRejectsArrays(): void
    {
        $this->expectException(CastException::class);
        $this->expectExceptionMessage("Unable to cast 'array' to 'string'");

        Cast::toString([]);
    }


    public function testToTypedArrayCastsEachValueAndReindexes(): void
    {
        self::assertSame([1, 2, 3], Cast::toTypedArray('[1,"2",3.0]', Cast::toInt(...)));
        self::assertSame(['1', '2'], Cast::toTypedArray(['x' => 1, 'y' => 2], Cast::toString(...)));
    }


    public function testToTypedMapKeysValuesByCallback(): void
    {
        $rows = [['id' => 'a', 'n' => 1], ['id' => 'b', 'n' => 2]];

        $map = Cast::toTypedMap(
            $rows,
            Cast::toArray(...),
            fn(array $row): string => Cast::toString($row['id'])
        );

        self::assertSame(['a' => ['id' => 'a', 'n' => 1], 'b' => ['id' => 'b', 'n' => 2]], $map);
    }


    public function testToValueOrNull(): void
    {
        self::assertNull(Cast::toValueOrNull(null, Cast::toString(...)));
        self::assertSame('5', Cast::toValueOrNull(5, Cast::toString(...)));
    }
}
