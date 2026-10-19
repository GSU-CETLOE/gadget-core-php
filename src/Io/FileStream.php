<?php

declare(strict_types=1);

namespace Gadget\Io;

use Gadget\Exception\FileException;

class FileStream
{
    /**
     * @param self $stream
     * @return \Generator<int,string>
     */
    public static function foreachLine(self $stream): iterable
    {
        try {
            $stream->open()->rewind();
            for ($line = 1; ($text = $stream->readLineOrNull()) !== null; $line++) {
                yield $line => $text;
            }
        } finally {
            $stream->close();
        }
    }


    /**
     * @param self $stream
     * @return \Generator<int,list<string|null>>
     */
    public static function foreachCsvRow(self $stream): iterable
    {
        try {
            $stream->open()->rewind();
            for ($line = 1; ($row = $stream->readCsvRowOrNull()) !== null; $line++) {
                yield $line => $row;
            }
        } finally {
            $stream->close();
        }
    }


    /**
     * @param self $stream
     * @return \Generator<int,array{begin:int,end:int,length:int}>
     */
    public static function foreachCsvRowPos(self $stream): iterable
    {
        try {
            $stream->open()->rewind();
            for ($line = 1; true; $line++) {
                $rowBegin = $stream->tell();
                if ($stream->readCsvRowOrNull() === null) {
                    break;
                }
                $rowEnd = $stream->tell();
                yield $line => [
                    'begin' => $rowBegin,
                    'end' => $rowEnd,
                    'length' => $rowEnd - $rowBegin
                ];
            }
        } finally {
            $stream->close();
        }
    }


    private File|null $file = null;


    /**
     * @param string|File|null $file
     * @param string|null $mode
     * @param bool $useIncludePath
     * @param resource|null $context
     * @param resource|false $stream
     */
    public function __construct(
        string|File|null $file = null,
        private string|null $mode = null,
        private bool $useIncludePath = false,
        private mixed $context = null,
        private mixed $stream = false
    ) {
        if ($file !== null) {
            $this->setFile($file);
        }
    }


    public function __destruct()
    {
        $this->close();
    }


    /** @return File */
    public function getFile(): File
    {
        return $this->file ?? throw new FileException("file not set");
    }


    /**
     * @param string|File $file
     * @return $this
     */
    public function setFile(string|File $file): static
    {
        $this->file = is_string($file) ? new File($file) : $file;
        return $this;
    }


    /** @return string */
    public function getMode(): string
    {
        return $this->mode ?? throw new FileException("mode not set");
    }


    /**
     * @param string $mode
     * @return $this
     */
    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }


    /** @return bool */
    public function getUseIncludePath(): bool
    {
        return $this->useIncludePath;
    }


    /**
     * @param bool $useIncludePath
     * @return $this
     */
    public function setUseIncludePath(bool $useIncludePath): static
    {
        $this->useIncludePath = $useIncludePath;
        return $this;
    }


    /** @return resource|null */
    public function getContext(): mixed
    {
        return $this->context;
    }


    /**
     * @param resource|null $context
     * @return $this
     */
    public function setContext(mixed $context): mixed
    {
        $this->context = $context;
        return $this;
    }


    /**
     * @return resource
     */
    protected function getStream(): mixed
    {
        return is_resource($this->stream)
            ? $this->stream
            : throw new FileException(["Unable to get file stream: %s", [$this->getFile()]]);
    }


    /** @return bool */
    public function isOpen(): bool
    {
        return is_resource($this->stream);
    }


    /**
     * @param resource|false $stream
     * @return $this
     */
    public function open(mixed $stream = false): static
    {
        if ($this->isOpen()) {
            if ($stream === false) {
                return $this;
            }

            $this->close();
        }

        $this->stream = $stream !== false
            ? $stream
            : fopen(
                $this->getFile()->getFileName(),
                $this->getMode(),
                $this->getUseIncludePath(),
                $this->getContext()
            );

        return is_resource($this->stream)
            ? $this
            : throw new FileException(["Unable to open file: %s", [$this->getFile()]]);
    }


    /** @return $this */
    public function close(): static
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = false;
        }
        return $this;
    }


    /**
     * @return array{
     *   dev:int,ino:int,mode:int,nlink:int,uid:int,gid:int,rdev:int,
     *   size:int,atime:int,mtime:int,ctime:int,blksize:int,blocks:int
     * }
     */
    public function stat(): array
    {
        $stat = fstat($this->getStream());
        return is_array($stat)
            ? array_slice($stat, 13)
            : throw new FileException(["Unable to get stat: %s", [$this->getFile()]]);
    }


    /** @return int */
    public function tell(): int
    {
        $pos = ftell($this->getStream());
        return is_int($pos)
            ? $pos
            : throw new FileException(["Unable to get position: %s", [$this->getFile()]]);
    }


    /** @return bool */
    public function eof(): bool
    {
        return feof($this->getStream());
    }


    /**
     * @param int $offset
     * @param int $whence
     * @return $this
     */
    public function seek(
        int $offset,
        int $whence = SEEK_SET
    ): static {
        return fseek($this->getStream(), $offset, $whence) === 0
            ? $this
            : throw new FileException(["Unable to seek to position %d: %s", [$offset, $this->getFile()]]);
    }


    /** @return $this */
    public function rewind(): static
    {
        return rewind($this->getStream()) === true
            ? $this
            : throw new FileException(["Unable to rewind: %s", [$this->getFile()]]);
    }


    /**
     * @param int<1,max> $length
     * @return string
     */
    public function read(int $length): string
    {
        $str = fread($this->getStream(), $length);
        return is_string($str)
            ? $str
            : throw new FileException(["Unable to read from file: %s", [$this->getFile()]]);
    }


    /**
     * @param int<0,max>|null $length
     * @return string
     */
    public function readLine(int|null $length = null): string
    {
        return $this->readLineOrNull($length)
            ?? throw new FileException(["Unable to read from file: %s", [$this->getFile()]]);
    }


    /**
     * Reads the next line, or returns null once the end of the stream has been reached.
     * PHP only reports end-of-file after a read comes up short, so callers that loop
     * over a file use this instead of checking eof() ahead of each read.
     *
     * @param int<0,max>|null $length
     * @return string|null
     */
    private function readLineOrNull(int|null $length = null): string|null
    {
        $line = fgets($this->getStream(), $length);
        return match (true) {
            is_string($line) => $line,
            $this->eof() => null,
            default => throw new FileException(["Unable to read from file: %s", [$this->getFile()]])
        };
    }


    /**
     * @param int<0,max>|null $length
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @return list<string|null>
     */
    public function readCsvRow(
        int|null $length = null,
        string $separator = ",",
        string $enclosure = "\"",
        string $escape = "\\"
    ): array {
        return $this->readCsvRowOrNull($length, $separator, $enclosure, $escape)
            ?? throw new FileException(["Unable to read from file: %s", [$this->getFile()]]);
    }


    /**
     * Reads the next CSV row, or returns null once the end of the stream has been reached.
     *
     * @param int<0,max>|null $length
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @return list<string|null>|null
     */
    private function readCsvRowOrNull(
        int|null $length = null,
        string $separator = ",",
        string $enclosure = "\"",
        string $escape = "\\"
    ): array|null {
        $row = fgetcsv(
            $this->getStream(),
            $length,
            $separator,
            $enclosure,
            $escape
        );

        return match (true) {
            is_array($row) => $row,
            $this->eof() => null,
            default => throw new FileException(["Unable to read from file: %s", [$this->getFile()]])
        };
    }


    /**
     * @param int|float|bool|string|\Stringable $data
     * @param int<0,max>|null $length
     * @return int<0,max>
     */
    public function write(
        int|float|bool|string|\Stringable $data,
        int|null $length = null
    ): int {
        $bytes = fwrite(
            $this->getStream(),
            is_string($data) ? $data : strval($data),
            $length
        );
        return is_int($bytes)
            ? $bytes
            : throw new FileException(["Unable to write to file: %s", [$this->getFile()]]);
    }


    /**
     * @param int|float|bool|string|\Stringable $data
     * @param int<0,max>|null $length
     * @param string $eol
     * @return int<0,max>
     */
    public function writeLine(
        int|float|bool|string|\Stringable $data,
        int|null $length = null,
        string $eol = PHP_EOL
    ): int {
        return $this->write(
            (is_string($data) ? $data : strval($data)) . $eol,
            $length
        );
    }


    /**
     * @param string[] $fields
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @param string $eol
     * @return int
     */
    public function writeCsvRow(
        array $fields,
        string $separator = ",",
        string $enclosure = "\"",
        string $escape = "\\",
        string $eol = "\n"
    ): int {
        $bytes = fputcsv(
            $this->getStream(),
            $fields,
            $separator,
            $enclosure,
            $escape,
            $eol
        );
        return is_int($bytes)
            ? $bytes
            : throw new FileException(["Unable to write to file: %s", [$this->getFile()]]);
    }


    /**
     * @param int<0,max> $size
     * @return $this
     */
    public function truncate(int $size): static
    {
        return ftruncate($this->getStream(), $size) === true
            ? $this
            : throw new FileException(["Unable to truncate file: %s", [$this->getFile()]]);
    }


    /** @return $this */
    public function flush(): static
    {
        return fflush($this->getStream()) === true
            ? $this
            : throw new FileException(["Unable to flush file: %s", [$this->getFile()]]);
    }


    /**
     * @param bool $justData
     * @return $this
     */
    public function sync(bool $justData = false): static
    {
        return ($justData ? fdatasync(...) : fsync(...))($this->getStream()) === true
            ? $this
            : throw new FileException(["Unable to sync file: %s", [$this->getFile()]]);
    }


    /**
     * @param int<0,7> $operation
     * @param int|null $wouldBlock
     * @return $this
     */
    public function lock(
        int $operation,
        int|null &$wouldBlock = null
    ): static {
        return flock($this->getStream(), $operation, $wouldBlock) === true
            ? $this
            : throw new FileException(["Unable to lock file: %s", [$this->getFile()]]);
    }
}
