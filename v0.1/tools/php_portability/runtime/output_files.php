<?php
declare(strict_types=1);
namespace scpp;

/** Filesystem mechanism only; callers serialize and establish ownership before mutation. */
final class Output_Files
{
    public static function root(string $path): string
    {
        if (PHP_OS_FAMILY !== 'Linux' || $path === '' || $path[0] !== '/' || $path === '/' || str_contains($path, "\0")) {
            throw new \RuntimeException('Output root requires an absolute non-root Linux path');
        }
        $path = rtrim($path, '/');
        self::directories($path, false, true);
        if (!is_dir($path) && !@mkdir($path, 0700)) { throw new \RuntimeException('Cannot create output root'); }
        $real = realpath($path);
        if ($real === false) { throw new \RuntimeException('Cannot resolve output root'); }
        return $real;
    }

    /** Reject symlink/special components; creation is limited to this selected path. */
    private static function directories(string $path, bool $create, bool $last_may_be_missing = false): void
    {
        $parts = explode('/', substr($path, 1));
        $current = '';
        foreach ($parts as $index => $part) {
            if ($part === '' || $part === '.' || $part === '..') { throw new \RuntimeException('Invalid output directory component'); }
            $current .= '/' . $part;
            clearstatcache(true, $current);
            $stat = @lstat($current);
            if ($stat === false) {
                if ($create) {
                    if (!@mkdir($current, 0700)) { throw new \RuntimeException('Cannot create output directory'); }
                } elseif ($last_may_be_missing && $index === count($parts) - 1) { return; }
                else { throw new \RuntimeException('Missing output directory'); }
            } elseif (($stat['mode'] & 0170000) !== 0040000) {
                throw new \RuntimeException('Output directory is not a real directory');
            }
        }
    }

    public static function path(string $root, string $relative, bool $create): string
    {
        if ($root === '' || $root[0] !== '/' || $root === '/' || str_contains($root, "\0")) { throw new \RuntimeException('Invalid output root'); }
        self::directories($root, false);
        if (!preg_match('/^[A-Za-z0-9_.\/-]+$/D', $relative)) { throw new \RuntimeException('Invalid output relative path'); }
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.' || $part === '..') { throw new \RuntimeException('Invalid output relative component'); }
        }
        $path = $root . '/' . $relative;
        $parent = dirname($path);
        if ($create) { self::directories($parent, true); }
        else {
            // Missing parents mean a missing file, but existing prefixes are still checked.
            $current = $root;
            $parts = explode('/', $relative);
            array_pop($parts);
            foreach ($parts as $part) {
                $current .= '/' . $part;
                clearstatcache(true, $current);
                $stat = @lstat($current);
                if ($stat === false) { return $path; }
                if (($stat['mode'] & 0170000) !== 0040000) { throw new \RuntimeException('Unsafe output parent'); }
            }
        }
        self::regular_or_missing($path);
        return $path;
    }

    private static function regular_or_missing(string $path): bool
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if ($stat === false) { return false; }
        if (($stat['mode'] & 0170000) !== 0100000) { throw new \RuntimeException('Output target is not a regular file'); }
        return true;
    }

    public static function read(string $root, string $relative): ?string
    {
        $path = self::path($root, $relative, false);
        if (!self::regular_or_missing($path)) { return null; }
        $bytes = @file_get_contents($path);
        if ($bytes === false) { throw new \RuntimeException('Cannot read output file'); }
        return $bytes;
    }

    public static function write(string $root, string $relative, string $bytes): bool
    {
        $path = self::path($root, $relative, true);
        if (self::read($root, $relative) === $bytes) { return false; }
        $temporary = dirname($path) . '/.scpp-output-' . bin2hex(random_bytes(16));
        $stream = @fopen($temporary, 'x+b');
        if ($stream === false) { throw new \RuntimeException('Cannot create output temporary'); }
        try {
            if (!chmod($temporary, 0600)) { throw new \RuntimeException('Cannot protect output temporary'); }
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $count = fwrite($stream, substr($bytes, $offset));
                if ($count === false || $count === 0) { throw new \RuntimeException('Cannot write output temporary'); }
                $offset += $count;
            }
            if (!fflush($stream)) { throw new \RuntimeException('Cannot flush output temporary'); }
            fclose($stream);
            $stream = null;
            self::regular_or_missing($path);
            if (!@rename($temporary, $path)) { throw new \RuntimeException('Cannot replace output file'); }
            return true;
        } finally {
            if (is_resource($stream)) { fclose($stream); }
            if (is_file($temporary)) { unlink($temporary); }
        }
    }

    public static function remove(string $root, string $relative, string $expected): bool
    {
        $bytes = self::read($root, $relative);
        if ($bytes === null) { return false; }
        if ($bytes !== $expected) { throw new \RuntimeException('Refusing to remove edited output'); }
        if (!@unlink(self::path($root, $relative, false))) { throw new \RuntimeException('Cannot remove output file'); }
        return true;
    }

    public static function digest(string $root, string $relative): string
    {
        $path = self::path($root, $relative, false);
        if (!self::regular_or_missing($path)) { return ''; }
        $digest = hash_file('sha256', $path);
        if ($digest === false) { throw new \RuntimeException('Cannot hash output file'); }
        return $digest;
    }

    /** Internal stage primitives; the named owner retains the freshly allocated name. */
    public static function stage(string $root): string
    {
        $name = '.scpp-stage-' . bin2hex(random_bytes(16));
        $path = self::path($root, $name, false);
        $stream = @fopen($path, 'x+b');
        if ($stream === false) { throw new \RuntimeException('Cannot reserve staged output'); }
        try {
            if (!chmod($path, 0600)) { throw new \RuntimeException('Cannot protect staged output'); }
        } catch (\Throwable $error) { unlink($path); throw $error; }
        finally { fclose($stream); }
        return $name;
    }

    private static function stage_path(string $root, string $name): string
    {
        if (!preg_match('/^\.scpp-stage-[a-zA-Z0-9]+$/D', $name)) { throw new \RuntimeException('Invalid stage name'); }
        $path = self::path($root, $name, false);
        if (self::regular_or_missing($path)) {
            if (lstat($path)['nlink'] !== 1) { throw new \RuntimeException('Staged output has external hard links'); }
        }
        return $path;
    }

    public static function discard(string $root, string $name): void
    {
        $path = self::stage_path($root, $name);
        if (self::regular_or_missing($path) && !unlink($path)) { throw new \RuntimeException('Cannot discard staged output'); }
    }

    public static function promote(string $root, string $stage, string $relative, string $expected, bool $executable): bool
    {
        if ($expected !== '' && !preg_match('/^[a-f0-9]{64}$/D', $expected)) { throw new \RuntimeException('Invalid expected digest'); }
        if (str_starts_with($relative, '.scpp-stage-')) { throw new \RuntimeException('Reserved promotion target'); }
        $source = self::stage_path($root, $stage);
        if (!self::regular_or_missing($source)) { throw new \RuntimeException('Missing staged output'); }
        $target = self::path($root, $relative, true);
        $current = self::digest($root, $relative);
        if ($current !== $expected) { throw new \RuntimeException('Promotion target changed or is unowned'); }
        $mode = $executable ? 0700 : 0600;
        if ($current !== '' && $current === self::digest($root, $stage) && (fileperms($target) & 07777) === $mode) {
            self::discard($root, $stage);
            return false;
        }
        if (!chmod($source, $mode)) { throw new \RuntimeException('Cannot set staged output permissions'); }
        if (!@rename($source, $target)) { throw new \RuntimeException('Cannot promote staged output'); }
        return true;
    }
}

/** Explicit single-attempt owner. Aliases share the consumed/closed state. */
final class Output_Stage
{
    private string $root;
    private string $relative;
    private bool $owned = true;
    private function __clone() {}
    public function __construct(string $root) { $this->root = $root; $this->relative = Output_Files::stage($root); }
    public function path(): string {
        if (!$this->owned) { throw new \RuntimeException('Stage is closed'); }
        return $this->root . '/' . $this->relative;
    }
    public function promote(string $relative, string $expected_digest, bool $executable): bool {
        if (!$this->owned) { throw new \RuntimeException('Stage is closed'); }
        $changed = Output_Files::promote($this->root, $this->relative, $relative, $expected_digest, $executable);
        $this->owned = false;
        return $changed;
    }
    public function close(): void {
        if ($this->owned) { Output_Files::discard($this->root, $this->relative); $this->owned = false; }
    }
}

function fs_output_root(string $path): string { return Output_Files::root($path); }
function fs_output_read(string $root, string $relative): ?string { return Output_Files::read($root, $relative); }
function fs_output_write(string $root, string $relative, string $bytes): bool { return Output_Files::write($root, $relative, $bytes); }
function fs_output_remove(string $root, string $relative, string $expected): bool { return Output_Files::remove($root, $relative, $expected); }
function content_sha256(string $bytes): string { return hash('sha256', $bytes); }
function fs_output_digest(string $root, string $relative): string { return Output_Files::digest($root, $relative); }
function fs_output_permissions(string $root, string $relative): int {
    $path = Output_Files::path($root, $relative, false);
    if (!is_file($path)) { return 0; }
    $mode = fileperms($path);
    if ($mode === false) { throw new \RuntimeException('Cannot read output permissions'); }
    return $mode & 07777;
}
function fs_file_digest(string $path): string {
    if ($path === '' || $path[0] !== '/' || str_contains($path, "\0")) { throw new \RuntimeException('Digest input must be absolute'); }
    $resolved = realpath($path);
    if ($resolved === false || !is_file($resolved)) { throw new \RuntimeException('Digest input must be a regular file'); }
    $digest = hash_file('sha256', $resolved);
    if ($digest === false) { throw new \RuntimeException('Cannot hash input file'); }
    return $digest;
}
