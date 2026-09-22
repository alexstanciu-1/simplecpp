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
