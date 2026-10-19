<?php

declare(strict_types=1);

namespace Gadget\Tests\Util\MFA;

use Gadget\Io\Base32;
use Gadget\Util\MFA\HOTP;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HOTPTest extends TestCase
{
    /** The 20-byte secret from RFC 4226 Appendix D, Base32-encoded. */
    private const RFC_4226_KEY = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';


    /**
     * Test vectors from RFC 4226 Appendix D.
     *
     * @return iterable<string,array{int,string}>
     */
    public static function rfc4226Vectors(): iterable
    {
        yield 'count 0' => [0, '755224'];
        yield 'count 1' => [1, '287082'];
        yield 'count 2' => [2, '359152'];
        yield 'count 3' => [3, '969429'];
        yield 'count 4' => [4, '338314'];
        yield 'count 5' => [5, '254676'];
        yield 'count 6' => [6, '287922'];
        yield 'count 7' => [7, '162583'];
        yield 'count 8' => [8, '399871'];
        yield 'count 9' => [9, '520489'];
    }


    /**
     * @param int<0,max> $counter
     * @param string $expected
     */
    #[DataProvider('rfc4226Vectors')]
    public function testGenerateMatchesRfc4226Vectors(int $counter, string $expected): void
    {
        $hotp = (new HOTP())
            ->setKey(self::RFC_4226_KEY)
            ->setCounter($counter);

        self::assertSame($expected, $hotp->generate());
    }


    public function testKeyEncodedAsBase32ConstantMatchesRfcSecret(): void
    {
        self::assertSame(self::RFC_4226_KEY, Base32::encode('12345678901234567890'));
    }


    public function testLengthControlsNumberOfDigits(): void
    {
        $hotp = (new HOTP())
            ->setKey(self::RFC_4226_KEY)
            ->setCounter(0)
            ->setLength(8);

        self::assertSame('84755224', $hotp->generate());
    }


    public function testKeyAcceptsLowercaseAndTrailingPadding(): void
    {
        $hotp = (new HOTP())
            ->setKey(strtolower(self::RFC_4226_KEY) . "==\n")
            ->setCounter(0);

        self::assertSame('755224', $hotp->generate());
    }


    public function testShortKeyIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('key must be at least 16 bytes');

        (new HOTP())->setKey('ABCDEFGH');
    }


    public function testNonBase32KeyIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('key must be base32-encoded');

        (new HOTP())->setKey('ABCDEFGH01ABCDEFGH01');
    }


    public function testUnsupportedAlgorithmIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not a supported hmac algorithm: not-a-real-algo');

        (new HOTP())->setAlgorithm('not-a-real-algo');
    }


    public function testSupportedAlgorithmChangesOutput(): void
    {
        $sha1 = (new HOTP())->setKey(self::RFC_4226_KEY)->setCounter(0);
        $sha256 = (new HOTP())->setKey(self::RFC_4226_KEY)->setCounter(0)->setAlgorithm('sha256');

        self::assertSame('755224', $sha1->generate());
        self::assertNotSame($sha1->generate(), $sha256->generate());
        self::assertMatchesRegularExpression('/^\d{6}$/', $sha256->generate());
    }
}
