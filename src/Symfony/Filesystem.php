<?php

declare(strict_types=1);

namespace TwigStan\Symfony;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

final class Filesystem extends BaseFilesystem
{
    private static ?string $lastError = null;

    /**
     * Returns the content of a file as a string.
     *
     * @throws IOException If the file cannot be read
     */
    public function readFile(string $filename): string
    {
        if (is_dir($filename)) {
            throw new IOException(\sprintf('Failed to read file "%s": File is a directory.', $filename));
        }

        $content = self::box('file_get_contents', $filename);

        if (false === $content) {
            throw new IOException(\sprintf('Failed to read file "%s": ', $filename) . self::$lastError, 0, null, $filename);
        }

        return $content;
    }

    private static function box(string $func, mixed ...$args): mixed
    {
        if ( ! \function_exists($func)) {
            throw new IOException(\sprintf('Unable to perform filesystem operation because the "%s()" function has been disabled.', $func));
        }

        self::$lastError = null;

        // @phpstan-ignore argument.type
        set_error_handler(static function (int $type, string $msg): void {
            self::$lastError = $msg;
        });

        try {
            // @phpstan-ignore callable.nonCallable
            return $func(...$args);
        } finally {
            restore_error_handler();
        }
    }
}
