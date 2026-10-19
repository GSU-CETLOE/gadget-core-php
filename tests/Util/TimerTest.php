<?php

declare(strict_types=1);

namespace Gadget\Tests\Util;

use Gadget\Util\Timer;
use PHPUnit\Framework\TestCase;

final class TimerTest extends TestCase
{
    private const ELAPSED_PATTERN = '/^\d{2}:\d{2}:\d{2}:\d{2}\.\d{3}$/';
    private const TIMESTAMP_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}[+-]\d{2}:\d{2}$/';


    /**
     * @param Timer $timer
     * @return int milliseconds portion of the elapsed string
     */
    private static function elapsedMilliseconds(Timer $timer): int
    {
        return (int) substr($timer->getElapsed(), -3);
    }


    public function testUnstartedTimerReportsZeroValues(): void
    {
        $timer = new Timer();

        self::assertSame('00:00:00:00.000', $timer->getElapsed());
        self::assertSame('00:00:00:00.000', (string) $timer);
        self::assertSame('0000-00-00T00:00:00.000000+00:00', $timer->getTimestamp());
    }


    public function testStartAndStopMeasureElapsedTime(): void
    {
        $timer = new Timer();

        self::assertSame($timer, $timer->start());
        usleep(20000);
        self::assertSame($timer, $timer->stop());

        self::assertMatchesRegularExpression(self::ELAPSED_PATTERN, $timer->getElapsed());
        self::assertStringStartsWith('00:00:00:00.', $timer->getElapsed());
        self::assertGreaterThanOrEqual(15, self::elapsedMilliseconds($timer));
        self::assertMatchesRegularExpression(self::TIMESTAMP_PATTERN, $timer->getTimestamp());
    }


    public function testStoppedTimerIsFrozen(): void
    {
        $timer = (new Timer())->start()->stop();
        $elapsed = $timer->getElapsed();
        $timestamp = $timer->getTimestamp();

        usleep(20000);
        $timer->stop();

        self::assertSame($elapsed, $timer->getElapsed());
        self::assertSame($timestamp, $timer->getTimestamp());
    }


    public function testRunningTimerKeepsCounting(): void
    {
        $timer = (new Timer())->start();
        $before = self::elapsedMilliseconds($timer);

        usleep(20000);

        self::assertGreaterThanOrEqual($before + 15, self::elapsedMilliseconds($timer));
    }


    public function testStartAfterStopResumesFromOriginalStart(): void
    {
        $timer = (new Timer())->start()->stop();
        $stopped = self::elapsedMilliseconds($timer);

        usleep(20000);
        $timer->start();

        self::assertGreaterThanOrEqual($stopped + 15, self::elapsedMilliseconds($timer));
    }


    public function testResetClearsEverything(): void
    {
        $timer = (new Timer())->start()->stop();

        self::assertSame($timer, $timer->reset());
        self::assertSame('00:00:00:00.000', $timer->getElapsed());
        self::assertSame('0000-00-00T00:00:00.000000+00:00', $timer->getTimestamp());
    }
}
