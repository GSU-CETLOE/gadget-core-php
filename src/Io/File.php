<?php

declare(strict_types=1);

namespace Gadget\Io;

use Gadget\Exception\FileException;

class File implements \Stringable
{
    /**
     * @param string|string[] $pattern
     * @param int $flags
     * @return File[]
     */
    public static function glob(
        string|array $pattern,
        int $flags = 0
    ): array {
        if (is_array($pattern)) {
            return array_unique(array_merge(...array_map(
                fn(string $p): array => self::glob($p, $flags),
                $pattern
            )));
        }

        $glob = glob($pattern, $flags);
        return is_array($glob)
            ? array_map(
                fn(string $fileName) => new File($fileName),
                $glob
            )
            : []
        ;
    }


    private string $fileName = '';


    /**
     * @param string $fileName
     */
    public function __construct(string $fileName = '')
    {
        $this->setFileName($fileName);
    }


    /** @return string */
    public function getFileName(): string
    {
        return $this->fileName;
    }


    /**
     * @param string $fileName
     * @return $this
     */
    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }


    /**
     * @param int<1,max> $levels
     * @return string
     */
    public function getDir(int $levels = 1): string
    {
        return dirname($this->fileName, $levels);
    }


    /**
     * @param string $suffix
     * @return string
     */
    public function getBaseName(string $suffix = ""): string
    {
        return basename($this->fileName, $suffix);
    }


    /** @return string */
    public function getPath(): string
    {
        $path = realpath($this->fileName);
        return is_string($path)
            ? $path
            : throw new FileException(["File not found: %s", [$this->fileName]]);
    }


    /**
     * @return array{dirname:string,basename:string,extension:string,filename:string}
     */
    public function getPathInfo(): array
    {
        return [
            'dirname' => '',
            'extension' => '',
            ...pathinfo($this->fileName)
        ];
    }


    /**
     * @return array{
     *   dev:int,ino:int,mode:int,nlink:int,uid:int,gid:int,rdev:int,
     *   size:int,atime:int,mtime:int,ctime:int,blksize:int,blocks:int
     * }
     */
    public function stat(): array
    {
        $stat = stat($this->fileName);
        return is_array($stat)
            ? array_slice($stat, 13)
            : throw new FileException(["Error fetching stat: %s", [$this->fileName]]);
    }


    /** @return bool */
    public function exists(): bool
    {
        return file_exists($this->fileName);
    }


    /** @return bool */
    public function isDir(): bool
    {
        return is_dir($this->fileName);
    }


    /** @return bool */
    public function isFile(): bool
    {
        return is_file($this->fileName);
    }


    /** @return bool */
    public function isLink(): bool
    {
        return is_link($this->fileName);
    }


    /** @return bool */
    public function isReadable(): bool
    {
        return is_readable($this->fileName);
    }


    /** @return bool */
    public function isWriteable(): bool
    {
        return is_writeable($this->fileName);
    }


    /** @return bool */
    public function isExecutible(): bool
    {
        return is_executable($this->fileName);
    }


    /**
     * @param bool $useIncludePath
     * @param resource|null $context
     * @param int $offset
     * @param int<0,max>|null $length
     * @return string
     */
    public function getContents(
        bool $useIncludePath = false,
        mixed $context = null,
        int $offset = 0,
        int|null $length = null
    ): string {
        $contents = file_get_contents(
            $this->fileName,
            $useIncludePath,
            $context,
            $offset,
            $length
        );

        return is_string($contents)
            ? $contents
            : throw new FileException(["Error reading contents: %s", [$this->fileName]]);
    }


    /**
     * @param string|string[]|resource $data
     * @param int $flags
     * @param resource|null $context
     * @return int
     */
    public function putContents(
        mixed $data,
        int $flags = 0,
        mixed $context = null
    ): int {
        $bytes = file_put_contents($this->fileName, $data, $flags, $context);
        return is_int($bytes)
            ? $bytes
            : throw new FileException(["Error writing contents: %s", [$this->fileName]]);
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFileName();
    }
}
