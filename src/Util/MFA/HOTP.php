<?php

declare(strict_types=1);

namespace Gadget\Util\MFA;

use Gadget\Io\Base32;

class HOTP
{
    private string $key = '';
    private string $algo = 'sha1';
    private int $length = 6;
    private int $counter = 0;


    /**
     * @param string $key
     * @return static
     */
    public function setKey(string $key): static
    {
        $key = rtrim($key, "=\x20\t\n\r\0\x0B");
        if (strlen($key) < 16) {
            throw new \LogicException('key must be at least 16 bytes');
        }
        if (preg_match('/[^a-z2-7]/i', $key) === 1) {
            throw new \LogicException('key must be base32-encoded');
        }
        $this->key = Base32::decode($key);
        return $this;
    }


    /**
     * @param string $algo
     * @return static
     */
    public function setAlgorithm(string $algo): static
    {
        if (!in_array($algo, hash_hmac_algos(), true)) {
            throw new \LogicException('Not a supported hmac algorithm: ' . $algo);
        }
        $this->algo = $algo;
        return $this;
    }


    /**
     * @param int<6,10> $length
     * @return static
     */
    public function setLength(int $length): static
    {
        $this->length = $length;
        return $this;
    }


    /**
     * @param int<0,max> $counter
     * @return static
     */
    public function setCounter(int $counter): static
    {
        $this->counter = $counter;
        return $this;
    }


    /**
     * @return string
     */
    public function generate(): string
    {
        // Step 1: Generate an HMAC value
        $hash = $this->generateHMAC($this->counter);

        // Step 2: Generate a 4-byte string (Dynamic Truncation)
        $truncate = $this->truncateHash($hash);

        // Step 3: Compute an HOTP value
        $code = sprintf(
            "%1\$0{$this->length}d",
            $truncate % pow(10, $this->length)
        );

        return $code;
    }


    /**
     * @param int $counter
     * @return string
     */
    private function generateHMAC(int $counter): string
    {
        // unsigned long (always 32 bit, big endian byte order)
        $counter = str_pad(pack('N', $counter), 8, "\x00", STR_PAD_LEFT);
        // compute HMAC
        return hash_hmac(
            $this->algo,
            $counter,
            $this->key
        );
    }


    /**
     * @param string $hash
     * @return int
     */
    private function truncateHash(string $hash): int
    {
        // low-order 4 bits of the last byte in $hash
        $offset = intval(2 * hexdec(substr($hash, -1, 1)));
        // first 32 bits (4 bytes) from offset
        $extract = hexdec(substr($hash, $offset, 8));
        // apply 0x7f mask
        return $extract & 0x7fffffff;
    }
}
