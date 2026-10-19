<?php

declare(strict_types=1);

namespace Gadget\Util;

final class Timer implements \Stringable
{
    private \DateTime|null $start = null;
    private \DateTime|null $stop = null;


    /**
     * @return self
     */
    public function start(): self
    {
        $this->start ??= new \DateTime();
        $this->stop = null;
        return $this;
    }


    /**
     * @return self
     */
    public function stop(): self
    {
        $this->stop ??= new \DateTime();
        return $this;
    }


    /**
     * @return self
     */
    public function reset(): self
    {
        $this->start = $this->stop = null;
        return $this;
    }


    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return ($this->start !== null)
            ? ($this->stop ?? new \DateTime())->format('Y-m-d\TH:i:s.vP')
            : '0000-00-00T00:00:00.000000+00:00';
    }


    /**
     * @return string
     */
    public function getElapsed(): string
    {
        return ($this->start !== null)
            ? substr($this->start->diff($this->stop ?? new \DateTime())->format('%D:%H:%I:%S.%F'), 0, 15)
            : '00:00:00:00.000';
    }


    /** @inheritdoc */
    public function __toString(): string
    {
        return $this->getElapsed();
    }
}
