<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation\Files;

/** Persistent exact-key allocation. Slots are local to this root and never recycled. */
final class Store
{
    public function __construct(public readonly string $root)
    {
    }

    /** Keep the root lock short; never acquire a package lock while holding it. */
    public function locate(string $key): string
    {
        Files::directory($this->root);
        $root = realpath($this->root);
        $lock = fopen($root . '/.index.lock', 'c');
        if (($lock === false) || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new \RuntimeException('Family index is busy');
        }
        try
        {
            $path = $root . '/index.json';
            $index = is_file($path) ? Files::json($path) : ['keys' => []];
            if (!is_array($index['keys'] ?? null) || !array_is_list($index['keys'])
                || (count(array_unique($index['keys'])) !== count($index['keys']))) {
                throw new \RuntimeException('Invalid family specialization index');
            }
            foreach ($index['keys'] as $entry) {
                if (!is_string($entry)) {
                    throw new \RuntimeException('Invalid specialization key');
                }
            }
            $slot = array_search($key, $index['keys'], true);
            if ($slot === false)
            {
                $slot = count($index['keys']);
                $index['keys'][] = $key;
                Files::write_json($path . '.tmp', $index);
                if (!rename($path . '.tmp', $path)) {
                    throw new \RuntimeException('Cannot publish specialization index');
                }
            }
            return $root . '/specialization-' . ($slot + 1);
        }
        finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** Read only an authenticated receipt while the caller owns the package reservation. */
    public static function retained(string $output): ?array
    {
        if (!is_file($output . '/current.json')) {
            return null;
        }
        $pointer = Files::json($output . '/current.json');
        if (($pointer['manifest'] ?? null) !== 'package/manifest.json') {
            throw new \RuntimeException('Invalid family publication');
        }
        $manifest_bytes = Files::read($output . '/package/manifest.json');
        if (hash('sha256', $manifest_bytes) !== ($pointer['manifest_sha256'] ?? null)) {
            throw new \RuntimeException('Invalid retained family manifest');
        }
        $manifest = Files::object($manifest_bytes, 'family manifest');
        $receipt = Files::read($output . '/package/request.json');
        if (hash('sha256', $receipt) !== ($manifest['artifacts']['request.json'] ?? null)) {
            throw new \RuntimeException('Invalid retained family request');
        }
        return Files::object($receipt, 'family receipt');
    }
}
