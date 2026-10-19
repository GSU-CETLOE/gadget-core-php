<?php

declare(strict_types=1);

namespace Gadget\Exception;

/**
 * @phpstan-type ThrowableParts array{
 *   file:string,
 *   line:int,
 *   trace:list<TraceDetails>,
 *   previous:\Throwable|null
 * }
 *
 * @phpstan-type TraceDetails array{
 *   file:string,
 *   line:int|null,
 *   class:class-string|null,
 *   function:string
 * }
 *
 * @implements \IteratorAggregate<string>
 */
class StackTraceIterator implements \IteratorAggregate
{
    /** @var string[]|null $seen */
    private array|null $seen = null;


    /**
     * @param \Throwable $root
     */
    public function __construct(private \Throwable $root)
    {
    }


    /** @inheritdoc */
    public function getIterator(): \Traversable
    {
        $this->seen = null;
        yield from $this->getStackTraceDetail($this->root);
    }


    /**
     * @param \Throwable $t
     * @return iterable<string>
     */
    private function getStackTraceDetail(\Throwable $t): iterable
    {
        yield sprintf(
            '%s%s: %s',
            is_array($this->seen) ? 'Caused by: ' :  '',
            get_class($t),
            $t->getMessage()
        );

        if ($this->seen === null) {
            $this->seen = [];
        }

        list(
            'file' => $file,
            'line' => $line,
            'trace' => $trace,
            'previous' => $prev
        ) = $this->getThrowableParts($t);

        $last = false;
        do {
            $current = "{$file}:{$line}";
            if (in_array($current, $this->seen, true)) {
                yield sprintf(' ... %d more', count($trace) + 1);
                break;
            }

            $this->seen[] = $current;
            list(
                'file' => $traceFile,
                'line' => $traceLine,
                'class' => $traceClass,
                'function' => $traceFunction
            ) = $this->getTraceDetails(array_shift($trace));

            yield sprintf(
                ' at %s%s%s(%s)',
                str_replace('\\', '.', ($traceClass ?? '')),
                is_string($traceClass) ? '.' : '',
                str_replace('\\', '.', $traceFunction),
                $line === null ? $file : basename($file) . ':' . $line
            );

            if ($last) {
                break;
            }
            $file = $traceFile;
            $line = $traceLine;
            $last = count($trace) === 0;
        } while (true);

        if ($prev !== null) {
            yield from self::getStackTraceDetail($prev);
        }
    }


    /**
     * @param \Throwable $t
     * @return ThrowableParts
     */
    private function getThrowableParts(\Throwable $t): array
    {
        return [
            'file' => $t->getFile(),
            'line' => $t->getLine(),
            'trace' => array_map(
                fn(array $d) => [
                    'file' => $d['file'] ?? 'Unknown Source',
                    'line' => ($d['line'] ?? 0) > 0 ? $d['line'] : null,
                    'class' => $d['class'] ?? null,
                    'function' => $d['function']
                ],
                $t->getTrace()
            ),
            'previous' => $t->getPrevious()
        ];
    }


    /**
     * @param TraceDetails|null $details
     * @return TraceDetails
     */
    private function getTraceDetails(array|null $details): array
    {
        return $details ?? [
            'file' => 'Unknown Source',
            'line' => null,
            'class' => null,
            'function' => '(main)'
        ];
    }
}
