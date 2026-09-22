<?php
declare(strict_types=1);
namespace runtime_preparation;

/** Stable directory/pointer replacement. Caller owns the exclusive package lock. */
final class Publication
{
    /** Finish or undo an interrupted directory replacement before inspecting reuse. */
    public static function recover_publication(string $output): void
    {
        $previous = $output . '/.previous-package';
        if (!is_dir($previous)) {
            return;
        }
        if (self::pointer_matches($output, $output . '/package')) {
            Files::remove_tree($previous);
            return;
        }
        if (!self::pointer_matches($output, $previous)) {
            throw new \RuntimeException('Interrupted runtime publication: neither package matches current.json');
        }
        Files::remove_tree($output . '/package');
        if (!rename($previous, $output . '/package')) {
            throw new \RuntimeException('Cannot restore previous runtime package');
        }
    }

    /** Check which publication directory matches the accepted manifest without changing files. */
    public static function pointer_matches(string $output, string $directory): bool
    {
        try {
            $pointer = Files::json($output . '/current.json');
            return (($pointer['manifest'] ?? '') === 'package/manifest.json')
                && (hash('sha256', Files::read($directory . '/manifest.json')) === ($pointer['manifest_sha256'] ?? ''));
        }
        catch (\Throwable) {
            return false;
        }
    }

    /** Replace the whole stable directory under the output lock; roll back on failure. */
    public static function publish(string $output, string $private, string $key): void
    {
        $package = $output . '/package';
        $previous = $output . '/.previous-package';
        $pointer_path = $output . '/.current-' . bin2hex(random_bytes(8));
        $backed_up = false;
        $installed = false;
        try
        {
            $pointer = ['schema_version' => 1, 'input_key' => $key, 'manifest' => 'package/manifest.json',
                'manifest_sha256' => hash('sha256', Files::read($private . '/manifest.json'))];
            Files::write_json($pointer_path, $pointer);
            if (file_exists($package) || is_link($package)) {
                if (is_link($package) || !is_dir($package) || !@rename($package, $previous)) {
                    throw new \RuntimeException('Cannot preserve previous runtime package');
                }
                $backed_up = true;
            }
            if (!@rename($private, $package)) {
                throw new \RuntimeException('Cannot publish runtime package directory');
            }
            $installed = true;
            if (!@rename($pointer_path, $output . '/current.json')) {
                throw new \RuntimeException('Cannot publish runtime package pointer');
            }
        }
        catch (\Throwable $error)
        {
            if (($installed) && !@rename($package, $private)) {
                throw new \RuntimeException('Runtime publication failed; cannot remove replacement package', 0, $error);
            }
            if (($backed_up) && !@rename($previous, $package)) {
                throw new \RuntimeException('Runtime publication failed; cannot restore previous package', 0, $error);
            }
            throw $error;
        }
        finally {
            if (is_file($pointer_path)) {
                Files::remove_tree($pointer_path);
            }
        }
    }

    /** Remove the temporary backup and legacy hashed packages, including on reuse. */
    public static function cleanup_publication(string $output): void
    {
        $packages = $output . '/packages';
        if (is_link($packages)) {
            throw new \RuntimeException('Runtime package is current, but cleanup refuses a symlinked packages directory');
        }
        try
        {
            Files::remove_tree($output . '/.previous-package');
            if (!is_dir($packages)) {
                return;
            }
            foreach (new \FilesystemIterator($packages) as $entry) {
                if ($entry->isLink() || !$entry->isDir()
                    || !preg_match('/^[a-f0-9]{64}-[a-f0-9]{8}$/D', $entry->getFilename())) {
                    continue;
                }
                Files::remove_tree($entry->getPathname());
            }
            if (!(new \FilesystemIterator($packages))->valid()) {
                Files::remove_tree($packages);
            }
        }
        catch (\Throwable $error) {
            throw new \RuntimeException('Runtime package is current, but superseded package cleanup failed: '
                . $error->getMessage(), 0, $error);
        }
    }
}
