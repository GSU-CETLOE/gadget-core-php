<?php

declare(strict_types=1);

namespace Gadget\Tests\Exception;

use Gadget\Exception\CastException;
use Gadget\Exception\DOMException;
use Gadget\Exception\Exception;
use Gadget\Exception\FileException;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    public function testStringMessage(): void
    {
        $exception = new Exception('boom');

        self::assertSame('boom', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }


    public function testStringableMessage(): void
    {
        $exception = new Exception(new \SplFileInfo('/tmp/x'));

        self::assertSame('/tmp/x', $exception->getMessage());
    }


    public function testFormattedMessageWithArguments(): void
    {
        $exception = new Exception(['%s has %d items at %s', ['cart', 3, new \SplFileInfo('/tmp/x')]]);

        self::assertSame('cart has 3 items at /tmp/x', $exception->getMessage());
    }


    public function testPreviousIsPreserved(): void
    {
        $previous = new \RuntimeException('inner');

        self::assertSame($previous, (new Exception('outer', $previous))->getPrevious());
    }


    public function testSetCodeIsFluent(): void
    {
        $exception = new Exception('boom');

        self::assertSame($exception, $exception->setCode(42));
        self::assertSame(42, $exception->getCode());
    }


    public function testCastExceptionDescribesSourceAndTargetTypes(): void
    {
        $value = 1.5;
        $previous = new \RuntimeException('inner');
        $exception = new CastException($value, 'integer', $previous);

        self::assertSame("Unable to cast 'double' to 'integer'", $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }


    public function testDomainExceptionsExtendBaseException(): void
    {
        self::assertInstanceOf(Exception::class, new DOMException('x'));
        self::assertInstanceOf(Exception::class, new FileException('x'));
    }
}
