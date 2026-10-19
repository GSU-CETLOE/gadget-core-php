<?php

declare(strict_types=1);

namespace Gadget\Tests\Util\MFA;

use Gadget\Util\MFA\TOTP;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TOTPTest extends TestCase
{
    /** The 20-byte SHA-1 secret from RFC 6238 Appendix B, Base32-encoded. */
    private const RFC_6238_KEY = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';


    /**
     * SHA-1 test vectors from RFC 6238 Appendix B (8 digits, 30 second period).
     *
     * @return iterable<string,array{int,string}>
     */
    public static function rfc6238Vectors(): iterable
    {
        yield '1970-01-01 00:00:59' => [59, '94287082'];
        yield '2005-03-18 01:58:29' => [1111111109, '07081804'];
        yield '2005-03-18 01:58:31' => [1111111111, '14050471'];
        yield '2009-02-13 23:31:30' => [1234567890, '89005924'];
        yield '2033-05-18 03:33:20' => [2000000000, '69279037'];
        yield '2603-10-11 11:33:20' => [20000000000, '65353130'];
    }


    /**
     * @param int<0,max> $time
     * @param string $expected
     */
    #[DataProvider('rfc6238Vectors')]
    public function testGenerateMatchesRfc6238Vectors(int $time, string $expected): void
    {
        $totp = (new TOTP())
            ->setKey(self::RFC_6238_KEY)
            ->setLength(8)
            ->setCurrentTime($time);

        self::assertSame($expected, $totp->generate());
    }


    public function testDefaultsToSixDigits(): void
    {
        $totp = (new TOTP())->setKey(self::RFC_6238_KEY)->setCurrentTime(59);

        self::assertSame('287082', $totp->generate());
    }


    public function testTimePeriodChangesTheCounter(): void
    {
        $totp = (new TOTP())
            ->setKey(self::RFC_6238_KEY)
            ->setLength(8)
            ->setTimePeriod(60)
            ->setCurrentTime(119);

        self::assertSame('94287082', $totp->generate());
    }


    public function testStartTimeOffsetsTheCounter(): void
    {
        $totp = (new TOTP())
            ->setKey(self::RFC_6238_KEY)
            ->setLength(8)
            ->setCurrentTime(59)
            ->setStartTime(30);

        self::assertSame('84755224', $totp->generate());
    }


    public function testCurrentTimeBeforeStartTimeUsesCounterZero(): void
    {
        $totp = (new TOTP())
            ->setKey(self::RFC_6238_KEY)
            ->setLength(8)
            ->setStartTime(1000)
            ->setCurrentTime(59);

        self::assertSame('84755224', $totp->generate());
    }


    public function testSetCounterAlwaysDerivesFromTime(): void
    {
        $totp = (new TOTP())
            ->setKey(self::RFC_6238_KEY)
            ->setLength(8)
            ->setCurrentTime(59)
            ->setCounter(500);

        self::assertSame('94287082', $totp->generate());
    }
}
