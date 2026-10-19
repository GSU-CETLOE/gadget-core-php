<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use Gadget\Io\JSON;
use PHPUnit\Framework\TestCase;

final class JSONTest extends TestCase
{
    public function testDecodeReturnsAssociativeArrays(): void
    {
        self::assertSame(['a' => 1, 'b' => ['c' => null]], JSON::decode('{"a":1,"b":{"c":null}}'));
        self::assertSame('text', JSON::decode('"text"'));
    }


    public function testDecodeThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        JSON::decode('{not json');
    }


    public function testDecodeHonorsDepth(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Maximum stack depth exceeded');

        JSON::decode('[[1]]', 0, 1);
    }


    public function testDecodePassesFlagsThrough(): void
    {
        self::assertSame(
            ['big' => '12345678901234567890'],
            JSON::decode('{"big":12345678901234567890}', JSON_BIGINT_AS_STRING)
        );
    }


    public function testEncodeProducesJson(): void
    {
        self::assertSame('{"a":1,"b":[true,null]}', JSON::encode(['a' => 1, 'b' => [true, null]]));
    }


    public function testEncodePassesFlagsThrough(): void
    {
        self::assertSame("{\n    \"a\": 1\n}", JSON::encode(['a' => 1], JSON_PRETTY_PRINT));
    }


    public function testEncodeThrowsOnUnencodableValue(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Inf and NaN cannot be JSON encoded');

        JSON::encode(NAN);
    }


    public function testEncodeHonorsDepth(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Maximum stack depth exceeded');

        JSON::encode([[1]], 0, 1);
    }
}
