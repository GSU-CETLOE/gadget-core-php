<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use PHPUnit\Framework\TestCase;

/**
 * Shared scaffolding for tests that touch the filesystem: a per-test scratch
 * directory under working/ and a way to assert on PHP warnings that the code
 * under test is expected to trigger.
 */
abstract class FileTestCase extends TestCase
{
    private string $tempDir = '';


    /** Creates a fresh scratch directory for each test. */
    protected function setUp(): void
    {
        $this->tempDir = dirname(__DIR__, 2) . '/working/tests/' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }


    /** Removes the scratch directory and everything in it. */
    protected function tearDown(): void
    {
        $entries = glob($this->tempDir . '/*');
        foreach (is_array($entries) ? $entries : [] as $entry) {
            unlink($entry);
        }
        rmdir($this->tempDir);
    }


    /**
     * @param string $name
     * @return string absolute path inside the scratch directory
     */
    protected function tempPath(string $name): string
    {
        return $this->tempDir . '/' . $name;
    }


    /**
     * @param string $name
     * @param string $contents
     * @return string absolute path of the written file
     */
    protected function writeTempFile(string $name, string $contents): string
    {
        $path = $this->tempPath($name);
        file_put_contents($path, $contents);
        return $path;
    }


    /**
     * Runs $action while capturing PHP warnings, then asserts exactly one
     * warning containing $expectedWarning was raised. Any exception thrown by
     * $action propagates after the assertion so callers can expect it too.
     *
     * @param string $expectedWarning
     * @param callable():mixed $action
     */
    protected function runExpectingWarning(string $expectedWarning, callable $action): void
    {
        /** @var list<string> $warnings */
        $warnings = [];
        set_error_handler(function (int $errno, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        });

        try {
            $action();
        } finally {
            restore_error_handler();
            self::assertCount(1, $warnings);
            self::assertStringContainsString($expectedWarning, $warnings[0]);
        }
    }
}
