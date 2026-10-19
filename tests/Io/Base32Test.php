<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use Gadget\Io\Base32;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Base32Test extends TestCase
{
    /**
     * Test vectors from RFC 4648 section 10.
     *
     * @return iterable<string,array{string,string}>
     */
    public static function rfc4648Vectors(): iterable
    {
        yield 'empty' => ['', ''];
        yield 'f' => ['f', 'MY======'];
        yield 'fo' => ['fo', 'MZXQ===='];
        yield 'foo' => ['foo', 'MZXW6==='];
        yield 'foob' => ['foob', 'MZXW6YQ='];
        yield 'fooba' => ['fooba', 'MZXW6YTB'];
        yield 'foobar' => ['foobar', 'MZXW6YTBOI======'];
    }


    #[DataProvider('rfc4648Vectors')]
    public function testEncodeWithPadding(string $plain, string $encoded): void
    {
        self::assertSame($encoded, Base32::encode($plain, true));
    }


    #[DataProvider('rfc4648Vectors')]
    public function testEncodeWithoutPadding(string $plain, string $encoded): void
    {
        self::assertSame(rtrim($encoded, '='), Base32::encode($plain));
    }


    #[DataProvider('rfc4648Vectors')]
    public function testDecode(string $plain, string $encoded): void
    {
        self::assertSame($plain, Base32::decode($encoded));
        self::assertSame($plain, Base32::decode(rtrim($encoded, '=')));
    }


    public function testDecodeAcceptsLowercaseAndTrailingWhitespace(): void
    {
        self::assertSame('foobar', Base32::decode("mzxw6ytboi======\n "));
    }


    public function testRoundTripOfAllByteValues(): void
    {
        $bytes = implode('', array_map('chr', range(0, 255)));

        self::assertSame($bytes, Base32::decode(Base32::encode($bytes)));
        self::assertSame($bytes, Base32::decode(Base32::encode($bytes, true)));
    }
}
