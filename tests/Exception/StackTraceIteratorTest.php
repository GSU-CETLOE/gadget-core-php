<?php

declare(strict_types=1);

namespace Gadget\Tests\Exception;

use Gadget\Exception\StackTraceIterator;
use PHPUnit\Framework\TestCase;

final class StackTraceIteratorTest extends TestCase
{
    /** @return list<string> */
    private static function lines(\Throwable $throwable): array
    {
        return iterator_to_array(new StackTraceIterator($throwable), false);
    }


    public function testFirstLineNamesTheExceptionAndMessage(): void
    {
        $lines = self::lines(new \RuntimeException('outer'));

        self::assertSame('RuntimeException: outer', $lines[0]);
        self::assertStringNotContainsString('Caused by', implode("\n", $lines));
    }


    public function testFramesUseDottedClassNamesAndFileLocations(): void
    {
        $lines = self::lines(new \RuntimeException('outer'));

        self::assertGreaterThan(1, count($lines));
        self::assertStringStartsWith(
            ' at Gadget.Tests.Exception.StackTraceIteratorTest.testFramesUseDottedClassNamesAndFileLocations('
            . 'StackTraceIteratorTest.php:',
            $lines[1]
        );
        foreach (array_slice($lines, 1) as $line) {
            self::assertMatchesRegularExpression('/^( at .+\(.+\)| \.\.\. \d+ more)$/', $line);
        }
    }


    public function testPreviousExceptionsAreListedAsCauses(): void
    {
        $lines = self::lines(new \RuntimeException('outer', 0, new \LogicException('inner')));
        $causedBy = array_search('Caused by: LogicException: inner', $lines, true);

        self::assertIsInt($causedBy);
        self::assertGreaterThan(1, $causedBy);
        self::assertMatchesRegularExpression('/^( at .+\(.+\)| \.\.\. \d+ more)$/', $lines[$causedBy + 1]);
    }


    public function testIteratingTwiceProducesTheSameOutput(): void
    {
        $iterator = new StackTraceIterator(new \RuntimeException('outer', 0, new \LogicException('inner')));

        self::assertSame(iterator_to_array($iterator, false), iterator_to_array($iterator, false));
    }
}
