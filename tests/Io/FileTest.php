<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use Gadget\Exception\FileException;
use Gadget\Io\File;

final class FileTest extends FileTestCase
{
    public function testGlobReturnsFileObjectsForMatchingPattern(): void
    {
        $this->writeTempFile('a.txt', 'a');
        $this->writeTempFile('b.txt', 'b');
        $this->writeTempFile('c.log', 'c');

        $files = File::glob($this->tempPath('*.txt'));

        self::assertContainsOnlyInstancesOf(File::class, $files);
        self::assertSame(
            [$this->tempPath('a.txt'), $this->tempPath('b.txt')],
            array_map('strval', $files)
        );
    }


    public function testGlobWithPatternListMergesAndDeduplicates(): void
    {
        $this->writeTempFile('a.txt', 'a');
        $this->writeTempFile('b.txt', 'b');
        $this->writeTempFile('c.log', 'c');

        $files = File::glob([
            $this->tempPath('*.txt'),
            $this->tempPath('*.log'),
            $this->tempPath('a.*')
        ]);

        self::assertCount(3, $files);
    }


    public function testGlobWithNoMatchesReturnsEmptyArray(): void
    {
        self::assertSame([], File::glob($this->tempPath('*.nope')));
    }


    public function testFileNameAccessors(): void
    {
        $file = new File('one.txt');
        self::assertSame('one.txt', $file->getFileName());
        self::assertSame('one.txt', (string) $file);

        self::assertSame($file, $file->setFileName('two.txt'));
        self::assertSame('two.txt', $file->getFileName());
    }


    public function testGetDirAndBaseName(): void
    {
        $file = new File('/var/log/app/error.log');

        self::assertSame('/var/log/app', $file->getDir());
        self::assertSame('/var/log', $file->getDir(2));
        self::assertSame('error.log', $file->getBaseName());
        self::assertSame('error', $file->getBaseName('.log'));
    }


    public function testGetPathResolvesRealPath(): void
    {
        $path = $this->writeTempFile('real.txt', 'x');

        self::assertSame(realpath($path), (new File($path))->getPath());
    }


    public function testGetPathThrowsWhenFileIsMissing(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('File not found: ' . $this->tempPath('missing.txt'));

        (new File($this->tempPath('missing.txt')))->getPath();
    }


    public function testGetPathInfoFillsInMissingParts(): void
    {
        self::assertSame(
            ['dirname' => '.', 'extension' => '', 'basename' => 'foo', 'filename' => 'foo'],
            (new File('foo'))->getPathInfo()
        );
        self::assertSame(
            ['dirname' => '/a/b', 'extension' => 'gz', 'basename' => 'c.tar.gz', 'filename' => 'c.tar'],
            (new File('/a/b/c.tar.gz'))->getPathInfo()
        );
    }


    public function testStatReturnsNamedKeysOnly(): void
    {
        $path = $this->writeTempFile('stat.txt', 'hello');

        $stat = (new File($path))->stat();

        self::assertSame(5, $stat['size']);
        self::assertSame(
            [
                'dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev',
                'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'
            ],
            array_keys($stat)
        );
    }


    public function testStatThrowsWhenFileIsMissing(): void
    {
        $path = $this->tempPath('missing.txt');
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Error fetching stat: ' . $path);

        $this->runExpectingWarning('stat failed', fn() => (new File($path))->stat());
    }


    public function testExistenceAndTypeChecks(): void
    {
        $path = $this->writeTempFile('plain.txt', 'x');
        $link = $this->tempPath('link.txt');
        symlink($path, $link);
        $dir = new File(dirname($path));
        $file = new File($path);
        $missing = new File($this->tempPath('missing.txt'));

        self::assertTrue($file->exists());
        self::assertFalse($missing->exists());
        self::assertTrue($dir->isDir());
        self::assertFalse($file->isDir());
        self::assertTrue($file->isFile());
        self::assertFalse($dir->isFile());
        self::assertTrue((new File($link))->isLink());
        self::assertFalse($file->isLink());
        self::assertTrue($file->isReadable());
        self::assertTrue($file->isWriteable());
        self::assertFalse($missing->isReadable());
    }


    public function testIsExecutibleReflectsFileMode(): void
    {
        $path = $this->writeTempFile('script.sh', '#!/bin/sh');
        $file = new File($path);

        self::assertFalse($file->isExecutible());
        chmod($path, 0755);
        self::assertTrue($file->isExecutible());
    }


    public function testGetContentsReadsWholeFileOrSlice(): void
    {
        $file = new File($this->writeTempFile('read.txt', 'hello world'));

        self::assertSame('hello world', $file->getContents());
        self::assertSame('world', $file->getContents(false, null, 6));
        self::assertSame('wor', $file->getContents(false, null, 6, 3));
    }


    public function testGetContentsThrowsWhenFileIsMissing(): void
    {
        $path = $this->tempPath('missing.txt');
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Error reading contents: ' . $path);

        $this->runExpectingWarning('Failed to open stream', fn() => (new File($path))->getContents());
    }


    public function testPutContentsWritesAndAppends(): void
    {
        $file = new File($this->tempPath('write.txt'));

        self::assertSame(5, $file->putContents('hello'));
        self::assertSame(6, $file->putContents(' world', FILE_APPEND));
        self::assertSame('hello world', $file->getContents());
    }


    public function testPutContentsThrowsWhenDirectoryIsMissing(): void
    {
        $path = $this->tempPath('nope/write.txt');
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Error writing contents: ' . $path);

        $this->runExpectingWarning('Failed to open stream', fn() => (new File($path))->putContents('x'));
    }
}
