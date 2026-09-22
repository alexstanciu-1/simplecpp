<?php
declare(strict_types=1);
namespace scpp;

// Fresh observations: PHP's stat cache must not survive a worker refresh.
function fs_is_link(string $path): bool { \clearstatcache(true, $path); return \is_link($path); }
function fs_is_dir(string $path): bool { \clearstatcache(true, $path); return \is_dir($path); }
function fs_is_file(string $path): bool { \clearstatcache(true, $path); return \is_file($path); }
function fs_size(string $path): int|false { \clearstatcache(true, $path); return @\filesize($path); }
function fs_mtime(string $path): int|false { \clearstatcache(true, $path); return @\filemtime($path); }
/** Sorted actual entries, excluding dot entries; false for ordinary filesystem failure. */
function fs_scan(string $path): array|false {
    \clearstatcache(true, $path);
    $entries = @\scandir($path, SCANDIR_SORT_ASCENDING);
    if ($entries === false) { return false; }
    $out = [];
    foreach ($entries as $name) { if ($name !== '.' && $name !== '..') { $out[] = $name; } }
    return $out;
}

/** Required path/read operations throw; source-stage diagnostics own user context. */
function fs_require_realpath(string $path): string {
    if (\str_contains($path, "\0")) { throw new \RuntimeException('Invalid input path'); }
    \clearstatcache(true);
    $resolved = \realpath($path);
    if ($resolved === false) { throw new \RuntimeException('Cannot locate project input: ' . $path); }
    return $resolved;
}
function fs_read_text(string $path): string {
    if (\str_contains($path, "\0")) { throw new \RuntimeException('Invalid input path'); }
    $content = @\file_get_contents($path);
    if ($content === false) { throw new \RuntimeException('Cannot read file: ' . $path); }
    return $content;
}
function fs_dirname(string $path): string { return \dirname($path); }
function fs_basename(string $path): string { return \basename($path); }

/** Target-host fact; never a converter-host decision. */
function fs_is_windows(): bool { return DIRECTORY_SEPARATOR === '\\'; }
