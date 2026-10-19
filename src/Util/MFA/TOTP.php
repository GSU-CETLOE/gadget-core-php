<?php

declare(strict_types=1);

namespace Gadget\Util\MFA;

class TOTP extends HOTP
{
    private int $timePeriod = 30;
    private int $startTime = 0;
    private int $currentTime = 0;


    /**
     * @param int<0,max> $currentTime
     * @return static
     */
    public function setCurrentTime(int $currentTime): static
    {
        $this->currentTime = $currentTime;
        return $this->setCounter(0);
    }


    /**
     * @param int<0,max> $startTime
     * @return static
     */
    public function setStartTime(int $startTime): static
    {
        $this->startTime = $startTime;
        return $this->setCounter(0);
    }


    /**
     * @param int<1,max> $timePeriod
     * @return static
     */
    public function setTimePeriod(int $timePeriod): static
    {
        $this->timePeriod = $timePeriod;
        return $this->setCounter(0);
    }


    /** @inheritdoc */
    public function setCounter(int $counter): static
    {
        $counter = intval(floor(($this->currentTime - $this->startTime) / $this->timePeriod));
        return parent::setCounter($counter >= 0 ? $counter : 0);
    }
}
