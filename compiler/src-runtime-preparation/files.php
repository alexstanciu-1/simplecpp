<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Files belonging to tool inputs, private builds and published packages. */
final class Files
{
    /** @return array<string, mixed> */
    public static function json(string $path): array
    {
        return self::object(self::read($path), $path);
    }

    /** @return array<string, mixed> */
    public static function object(string $contents, string $context): array
    {
        $value = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if ((!is_array($value)) || array_is_list($value)) {
            throw new \RuntimeException('Expected JSON object: ' . $context);
        }
        return $value;
    }

    public static function read(string $path): string
    {
        $value = @file_get_contents($path);
        if ($value === false) {
            throw new \RuntimeException('Cannot read ' . $path);
        }
        return $value;
    }

    public static function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) !== strlen($contents)) {
            throw new \RuntimeException('Cannot write ' . $path);
        }
    }

    /** @param array<string, mixed>|list<mixed> $value */
    public static function write_json(string $path, array $value): void
    {
        self::write($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    }

    public static function directory(string $path): void
    {
        if ((!is_dir($path)) && (!mkdir($path, 0777, true))) {
            throw new \RuntimeException('Cannot create directory ' . $path);
        }
    }

    public static function path(string $base, string $path): string
    {
        if (($path === '') || str_contains($path, "\0")) {
            throw new \RuntimeException('Expected nonempty path');
        }
        return str_starts_with($path, '/') ? $path : $base . '/' . $path;
    }

    /** Resolve a configured path or PATH entry while preserving the executable's driver-selecting basename. */
    public static function executable(string $base, string $name): string
    {
        $candidates = str_contains($name, '/') ? [self::path($base, $name)]
            : array_map(static fn(string $dir): string => $dir . '/' . $name, explode(PATH_SEPARATOR, getenv('PATH') ?: ''));
        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                // Driver programs such as clang++ and ld.lld select behavior by
                // argv[0]. Resolve the directory but preserve the executable name.
                return realpath(dirname($candidate)) . '/' . basename($candidate);
            }
        }
        throw new \RuntimeException('Missing executable: ' . $name);
    }

    /** Remove a caller-owned build/package path; never follows symlinks. */
    public static function remove_tree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!unlink($path)) {
                throw new \RuntimeException('Cannot remove ' . $path);
            }
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (new \FilesystemIterator($path) as $item) {
            self::remove_tree($item->getPathname());
        }
        if (!rmdir($path)) {
            throw new \RuntimeException('Cannot remove directory ' . $path);
        }
    }

    /**
     * Fingerprint file contents under sorted canonical paths for change detection, never entity identity.
     * @param list<string> $paths
     * @return array<string, string>
     */
    public static function hashes(array $paths): array
    {
        $hashes = [];
        foreach ($paths as $path) {
            $resolved = realpath($path);
            if (($resolved === false) || (!is_file($resolved))) {
                throw new \RuntimeException('Missing dependency: ' . $path);
            }
            $hashes[$resolved] = hash('sha256', self::read($resolved));
        }
        ksort($hashes);
        return $hashes;
    }
}
