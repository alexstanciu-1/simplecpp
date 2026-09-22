<?php
declare(strict_types=1);
require_once __DIR__ . '/src/exception_policy.php';

// Assembly owns framework files; conversion keeps its one-to-one source manifest.
try {
    if ($argc < 2 || count(array_unique(array_slice($argv, 2))) !== $argc - 2 || array_diff(array_slice($argv, 2), ['--os', '--filesystem', '--json']) !== [] || ($root = realpath($argv[1])) === false || !is_dir($root)) {
        throw new RuntimeException('Usage: php install_native_runtime.php EXISTING_CONVERTED_DIRECTORY [--os] [--filesystem] [--json]');
    }
    $conversion = $root . '/.scpp-portability.json';
    if (is_link($conversion) || !is_file($conversion)) { throw new RuntimeException('Expected a regular conversion manifest'); }
    $state = json_decode(file_get_contents($conversion), true, 512, JSON_THROW_ON_ERROR);
    if (($state['version'] ?? null) !== 1 || !is_array($state['files'] ?? null)) { throw new RuntimeException('Invalid conversion manifest'); }
    foreach (array_keys($state['files']) as $name) {
        if (str_starts_with($name, 'scpp_framework/')) { throw new RuntimeException('Converted source conflicts with reserved scpp_framework directory'); }
    }
    $directory = $root . '/scpp_framework';
    $manifest = $root . '/.scpp-native-runtime.json';
    $artifacts = [
        'exceptions.phs' => scpp\portability\Exception_Policy::nativeSource(),
        'strings.phs' => file_get_contents(__DIR__ . '/runtime/strings.phs'),
        'collections.phs' => file_get_contents(__DIR__ . '/runtime/collections.phs'),
    ];
    if (in_array('--filesystem', $argv, true)) {
        $artifacts['filesystem.phs'] = file_get_contents(__DIR__ . '/runtime/filesystem.phs');
    }
    if (in_array('--json', $argv, true)) {
        $artifacts['json_document.phs'] = file_get_contents(__DIR__ . '/runtime/json_document.phs');
    }
    if (in_array('--os', $argv, true)) {
        $artifacts['file_locks.phs'] = file_get_contents(__DIR__ . '/runtime/file_locks.phs');
        $artifacts['lock_reservation.phs'] = file_get_contents(__DIR__ . '/runtime/lock_reservation.phs');
        $artifacts['processes.phs'] = file_get_contents(__DIR__ . '/runtime/processes.phs');
    }
    foreach ([$directory, $manifest, ...array_map(static fn($name) => $directory . '/' . $name, array_keys($artifacts))] as $path) {
        if (is_link($path)) { throw new RuntimeException('Symlink framework paths are unsupported'); }
    }
    $owned = [];
    if (file_exists($manifest)) {
        if (!is_file($manifest)) { throw new RuntimeException('Invalid native runtime manifest path'); }
        $old = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
        if (($old['version'] ?? null) === 1 && isset($old['exceptions_sha256'])) {
            $owned = ['exceptions.phs' => $old['exceptions_sha256']];
        } elseif (($old['version'] ?? null) === 2 && is_array($old['files'] ?? null)) {
            $owned = $old['files'];
        } else { throw new RuntimeException('Invalid native runtime manifest'); }
        foreach ($owned as $name => $hash) {
            if (!isset($artifacts[$name]) || !is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) {
                throw new RuntimeException('Invalid native runtime manifest');
            }
        }
    }
    if (file_exists($directory) && !is_dir($directory)) { throw new RuntimeException('Invalid framework directory'); }
    $hashes = [];
    $changed = false;
    // Check every artifact before publishing any; new framework families have independent owners.
    foreach ($artifacts as $name => $bytes) {
        $path = $directory . '/' . $name;
        if (file_exists($path) && (!is_file($path) || !isset($owned[$name]) || hash_file('sha256', $path) !== $owned[$name])) {
            throw new RuntimeException('Refusing to replace unowned or edited native runtime: ' . $name);
        }
        $hashes[$name] = hash('sha256', $bytes);
        $changed = $changed || !is_file($path) || hash_file('sha256', $path) !== $hashes[$name];
    }
    if (!is_dir($directory) && !mkdir($directory)) { throw new RuntimeException('Cannot create framework directory'); }
    $publish = static function (string $path, string $content): void {
        if (is_file($path) && file_get_contents($path) === $content) { return; }
        $temp = tempnam(dirname($path), '.framework-');
        try {
            if ($temp === false || file_put_contents($temp, $content) !== strlen($content) || !rename($temp, $path)) {
                throw new RuntimeException('Native runtime publication failed');
            }
        } finally { if (is_string($temp) && is_file($temp)) { unlink($temp); } }
    };
    foreach ($artifacts as $name => $bytes) { $publish($directory . '/' . $name, $bytes); }
    $publish($manifest, json_encode(['version' => 2, 'files' => $hashes], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
    echo json_encode(['updated' => $changed ? 1 : 0]), "\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
