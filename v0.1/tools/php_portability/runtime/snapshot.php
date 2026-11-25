<?php
declare(strict_types=1);
namespace scpp;

/** PHP approximation of the target snapshot protocol; no native open-flag guarantee. */
function fs_read_snapshot(string $path, int $expected_mtime, int $expected_size): string {
    if ($path === '' || \str_contains($path, "\0") || $expected_size < 0 || $expected_size >= PHP_INT_MAX) {
        throw new \RuntimeException('Invalid snapshot request');
    }
    \clearstatcache(true);
    $before = @\lstat($path);
    $require_version = static function (array|false $observed) use ($before, $expected_mtime, $expected_size): void {
        if ($before === false || $observed === false || ($observed['mode'] & 0170000) !== 0100000
            || $observed['mtime'] !== $expected_mtime || $observed['size'] !== $expected_size
            || $observed['dev'] !== $before['dev'] || $observed['ino'] !== $before['ino']) {
            throw new \RuntimeException('Source changed or is not a regular file');
        }
    };
    $require_version($before);
    $handle = @\fopen($path, 'rb');
    if ($handle === false) { throw new \RuntimeException('Cannot open source snapshot'); }
    try {
        $require_version(@\fstat($handle));
        $content = @\stream_get_contents($handle, $expected_size + 1);
        if ($content === false) { throw new \RuntimeException('Cannot read source snapshot'); }
        $require_version(@\fstat($handle));
        \clearstatcache(true, $path);
        $require_version(@\lstat($path));
        if (\strlen($content) !== $expected_size) { throw new \RuntimeException('Source changed during reading'); }
        return $content;
    } finally { \fclose($handle); }
}
