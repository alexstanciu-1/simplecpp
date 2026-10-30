<?php
declare(strict_types=1);
namespace Scpp\Api;

// Bump when generated output layout or contracts require a fresh publication.
const OUTPUT_VERSION = 1;
const OUTPUT_EXTENSIONS = ['hpp', 'cpp', 'json'];

/** Enumerate disposable output without following directory or file symlinks. */
function generatedPaths(string $output): array {
    requireThat(!is_link($output), 'Generated output directory must not be a symlink');
    if (!is_dir($output)) {
        requireThat(!file_exists($output), 'Generated output path is not a directory');
        return [];
    }
    $paths = [];
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($output, \FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $entry) {
        if (in_array($entry->getExtension(), OUTPUT_EXTENSIONS, true) && !$entry->isDir()) {
            $paths[] = substr($entry->getPathname(), strlen($output) + 1);
        }
    }
    sort($paths, SORT_STRING);
    return $paths;
}

/** Validate the complete new output before touching the dedicated output directory. */
function publish(array $files, string $output, bool $check): void {
    $output = rtrim($output, '/');
    requireThat($output !== '' && $output !== '.', 'Expected a dedicated generated output directory');
    foreach ($files as $path => $contents) {
        requireThat(is_string($contents) && preg_match('~^[a-zA-Z0-9_./-]+\.(hpp|cpp|json)$~D', $path) === 1
            && !str_starts_with($path, '/') && !str_contains($path, '//') && !in_array('..', explode('/', $path), true), 'Invalid generated artifact path');
        $parent = $output;
        foreach (explode('/', $path) as $part) {
            $parent .= '/' . $part;
            requireThat(!is_link($parent), "Generated artifact path must not be a symlink: $parent");
        }
    }
    // Existing header contracts allow harmless './' components. Compare filesystem
    // paths canonically without changing the spelling recorded in the manifest.
    $canonical = [];
    foreach ($files as $path => $contents) {
        $path = implode('/', array_filter(explode('/', $path), fn(string $part): bool => $part !== '.'));
        requireThat(!array_key_exists($path, $canonical), 'Conflicting generated artifact paths');
        $canonical[$path] = $contents;
    }
    $files = $canonical;
    $old = generatedPaths($output);
    if ($check) {
        foreach ($old as $path) {
            requireThat(array_key_exists($path, $files), "Obsolete generated file: $path");
        }
        foreach ($files as $path => $contents) {
            requireThat(readFile($output . '/' . $path) === $contents, "Stale generated file: $output/$path");
        }
        return;
    }
    // The directory is dedicated: no per-file migration or old-manifest interpretation.
    // Missing, old-version or damaged manifests all take the same regeneration path.
    foreach ($old as $path) {
        requireThat(@unlink($output . '/' . $path), "Cannot remove generated file: $output/$path");
    }
    if (is_dir($output)) {
        $directories = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($output, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($directories as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                @rmdir($entry->getPathname()); // Non-generated files can keep a directory alive.
            }
        }
    }
    // Write the manifest last; it marks completion of the full regenerated publication.
    $manifest = $files['inventory.json'];
    unset($files['inventory.json']);
    $files['inventory.json'] = $manifest;
    foreach ($files as $path => $contents) {
        $target = $output . '/' . $path;
        $parent = dirname($target);
        requireThat(is_dir($parent) || @mkdir($parent, 0777, true), "Cannot create $parent");
        requireThat(@file_put_contents($target, $contents) === strlen($contents), "Cannot write $target");
    }
}
