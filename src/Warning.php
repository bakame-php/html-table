<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

use ErrorException;
use Throwable;

use function in_array;
use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;
use const E_WARNING;

/**
 * @internal Utility class to wrap callbacks to control emitted warnings during their execution.
 *
 * @template TReturn
 */
final class Warning
{
    /**
     * Converts PHP Warning into ErrorException.
     *
     * @param mixed ...$arguments the callback arguments if needed
     *
     * @throws ErrorException If the callback internally emits a Warning
     * @throws Throwable on callback execution if the callback throws
     *
     * @return TReturn The result returned by the callback.
     */
    public static function trap(callable $callback, mixed ...$arguments): mixed
    {
        set_error_handler(
            fn (int $errno, string $errstr, string $errfile, int $errline): bool =>
            in_array($errno, [E_WARNING, E_USER_WARNING], true)
                ? throw new ErrorException($errstr, 0, $errno, $errfile, $errline)
                : false
        );

        try {
            return $callback(...$arguments);
        } finally {
            restore_error_handler();
        }
    }
}
