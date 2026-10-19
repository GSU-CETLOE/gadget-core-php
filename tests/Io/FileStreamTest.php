<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use Gadget\Exception\FileException;
use Gadget\Io\File;
use Gadget\Io\FileStream;

final class FileStreamTest extends FileTestCase
{
    public function testFileAccessors(): void
    {
        $stream = new FileStream('one.txt');
        self::assertSame('one.txt', $stream->getFile()->getFileName());

        $file = new File('two.txt');
        self::assertSame($stream, $stream->setFile($file));
        self::assertSame($file, $stream->getFile());
    }


    public function testGetFileThrowsWhenUnset(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('file not set');

        (new FileStream())->getFile();
    }


    public function testModeAccessors(): void
    {
        $stream = new FileStream('one.txt', 'r');
        self::assertSame('r', $stream->getMode());

        self::assertSame($stream, $stream->setMode('w'));
        self::assertSame('w', $stream->getMode());
    }


    public function testGetModeThrowsWhenUnset(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('mode not set');

        (new FileStream('one.txt'))->getMode();
    }


    public function testIncludePathAndContextAccessors(): void
    {
        $stream = new FileStream();
        self::assertFalse($stream->getUseIncludePath());
        self::assertSame($stream, $stream->setUseIncludePath(true));
        self::assertTrue($stream->getUseIncludePath());

        self::assertNull($stream->getContext());
        $context = stream_context_create();
        self::assertSame($stream, $stream->setContext($context));
        self::assertSame($context, $stream->getContext());
    }


    public function testOpenAndCloseLifecycle(): void
    {
        $stream = new FileStream($this->tempPath('life.txt'), 'w+');

        self::assertFalse($stream->isOpen());
        self::assertSame($stream, $stream->open());
        self::assertTrue($stream->isOpen());
        self::assertSame($stream, $stream->open());
        self::assertTrue($stream->isOpen());

        self::assertSame($stream, $stream->close());
        self::assertFalse($stream->isOpen());
        self::assertSame($stream, $stream->close());
    }


    public function testOpenAcceptsAnExistingResource(): void
    {
        $memory = fopen('php://memory', 'w+');
        self::assertIsResource($memory);
        fwrite($memory, "from memory\n");

        $stream = (new FileStream())->open($memory);

        self::assertTrue($stream->isOpen());
        self::assertSame("from memory\n", $stream->rewind()->readLine());
    }


    public function testOpenThrowsWhenFileCannotBeOpened(): void
    {
        $path = $this->tempPath('missing.txt');
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Unable to open file: ' . $path);

        $this->runExpectingWarning('Failed to open stream', fn() => (new FileStream($path, 'r'))->open());
    }


    public function testOperationsOnClosedStreamThrow(): void
    {
        $path = $this->tempPath('closed.txt');
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Unable to get file stream: ' . $path);

        (new FileStream($path, 'r'))->tell();
    }


    public function testWriteThenReadBack(): void
    {
        $stream = (new FileStream($this->tempPath('rw.txt'), 'w+'))->open();

        self::assertSame(5, $stream->write('hello'));
        self::assertSame(7, $stream->writeLine(' world', null, "\n"));
        self::assertSame(2, $stream->write(42));
        self::assertSame(14, $stream->tell());

        self::assertSame($stream, $stream->rewind());
        self::assertSame(0, $stream->tell());
        self::assertSame('hel', $stream->read(3));
        self::assertSame("lo world\n", $stream->readLine());
        self::assertFalse($stream->eof());
        self::assertSame('42', $stream->read(10));
        self::assertTrue($stream->eof());

        self::assertSame($stream, $stream->seek(6));
        self::assertSame("world\n", $stream->readLine());
    }


    public function testSeekThrowsOnInvalidOffset(): void
    {
        $path = $this->tempPath('seek.txt');
        $stream = (new FileStream($path, 'w+'))->open();

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Unable to seek to position -5: ' . $path);

        $stream->seek(-5);
    }


    public function testReadLineThrowsAtEndOfFile(): void
    {
        $path = $this->writeTempFile('eof.txt', "only\n");
        $stream = (new FileStream($path, 'r'))->open();
        $stream->readLine();

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Unable to read from file: ' . $path);

        $stream->readLine();
    }


    public function testCsvRoundTrip(): void
    {
        $stream = (new FileStream($this->tempPath('rows.csv'), 'w+'))->open();

        self::assertSame(10, $stream->writeCsvRow(['a', 'b c', '1']));
        self::assertSame(8, $stream->writeCsvRow(['x', 'y,z'], ',', '"', '\\', "\n"));

        $stream->rewind();
        self::assertSame(['a', 'b c', '1'], $stream->readCsvRow());
        self::assertSame(['x', 'y,z'], $stream->readCsvRow());
    }


    public function testTruncateFlushSyncAndLock(): void
    {
        $stream = (new FileStream($this->tempPath('misc.txt'), 'w+'))->open();
        $stream->write('some data');

        self::assertSame($stream, $stream->flush());
        self::assertSame($stream, $stream->sync());
        self::assertSame($stream, $stream->sync(true));
        self::assertSame(9, $stream->stat()['size']);

        self::assertSame($stream, $stream->truncate(4));
        self::assertSame(4, $stream->stat()['size']);

        self::assertSame($stream, $stream->lock(LOCK_EX));
        self::assertSame($stream, $stream->lock(LOCK_UN));
    }


    public function testForeachLineYieldsNumberedLines(): void
    {
        $stream = new FileStream($this->writeTempFile('lines.txt', "a\nb\n"), 'r');

        self::assertSame([1 => "a\n", 2 => "b\n"], iterator_to_array(FileStream::foreachLine($stream)));
        self::assertFalse($stream->isOpen());
    }


    public function testForeachLineHandlesMissingTrailingNewline(): void
    {
        $stream = new FileStream($this->writeTempFile('lines.txt', "a\nb"), 'r');

        self::assertSame([1 => "a\n", 2 => 'b'], iterator_to_array(FileStream::foreachLine($stream)));
    }


    public function testForeachLineOnEmptyFileYieldsNothing(): void
    {
        $stream = new FileStream($this->writeTempFile('empty.txt', ''), 'r');

        self::assertSame([], iterator_to_array(FileStream::foreachLine($stream)));
    }


    public function testForeachCsvRowYieldsNumberedRows(): void
    {
        $stream = new FileStream($this->writeTempFile('rows.csv', "x,y\n\n1,\"2,3\"\n"), 'r');

        self::assertSame(
            [1 => ['x', 'y'], 2 => [null], 3 => ['1', '2,3']],
            iterator_to_array(FileStream::foreachCsvRow($stream))
        );
        self::assertFalse($stream->isOpen());
    }


    public function testForeachCsvRowPosYieldsByteRanges(): void
    {
        $stream = new FileStream($this->writeTempFile('rows.csv', "x,y\n1,2\n"), 'r');

        self::assertSame(
            [
                1 => ['begin' => 0, 'end' => 4, 'length' => 4],
                2 => ['begin' => 4, 'end' => 8, 'length' => 4]
            ],
            iterator_to_array(FileStream::foreachCsvRowPos($stream))
        );
        self::assertFalse($stream->isOpen());
    }
}
