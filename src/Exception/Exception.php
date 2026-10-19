<?php

declare(strict_types=1);

namespace Gadget\Exception;

class Exception extends \Exception
{
    /**
     * @param string|\Stringable|array{string,(string|\Stringable|int|float)[]} $message
     * @param \Throwable|null $previous
     */
    public function __construct(
        string|\Stringable|array $message = "",
        \Throwable|null $previous = null
    ) {
        parent::__construct(
            is_array($message)
                ? sprintf(
                    $message[0],
                    ...array_map(
                        fn($v) => $v instanceof \Stringable ? strval($v) : $v,
                        $message[1]
                    )
                )
                : strval($message),
            0,
            $previous
        );
    }


    /**
     * @param int $code
     * @return $this
     */
    public function setCode(int $code): static
    {
        $this->code = $code;
        return $this;
    }
}
