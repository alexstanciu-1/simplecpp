<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/../php/input.php';
require_once __DIR__ . '/../php/publication.php';

function expectFailure(callable $action, string $message): void {
    try { $action(); }
    catch (\RuntimeException $error) { requireThat(str_contains($error->getMessage(), $message), $error->getMessage()); return; }
    throw new \RuntimeException('Expected rejection: ' . $message);
}
$root = sys_get_temp_dir() . '/scpp-publication-' . bin2hex(random_bytes(8));
mkdir($root);
$output = $root . '/generated';
$files = ['public/scpp/core.hpp' => "// core\n", 'src/runtime.cpp' => "// code\n", 'prepared.json' => "{}\n",
    'inventory.json' => encode(['generator' => 'scpp-api', 'version' => OUTPUT_VERSION, 'files' => ['prepared.json', 'public/scpp/core.hpp', 'src/runtime.cpp']]) . "\n"];
try {
    publish($files, $output, false);
    publish($files, $output, true);
    mkdir($output . '/old');
    file_put_contents($output . '/old/retired.hpp', 'old');
    file_put_contents($output . '/old/retired.cpp', 'old');
    file_put_contents($output . '/old/retired.json', 'old');
    file_put_contents($output . '/keep.txt', 'preserve');
    expectFailure(fn() => publish($files, $output, true), 'Obsolete generated file');
    requireThat(file_exists($output . '/old/retired.hpp'), 'Check removed output');
    publish($files, $output, false);
    requireThat(!is_dir($output . '/old'), 'Full cleanup left obsolete generated directory');
    requireThat(readFile($output . '/keep.txt') === 'preserve', 'Cleanup removed a different extension');
    foreach (['[]', '{broken', '{"generator":"scpp-api","version":0,"files":[]}'] as $old) {
        file_put_contents($output . '/inventory.json', $old);
        expectFailure(fn() => publish($files, $output, true), 'Stale generated file');
        requireThat(readFile($output . '/inventory.json') === $old, 'Check changed manifest');
        publish($files, $output, false);
        publish($files, $output, true);
    }
    file_put_contents($root . '/outside.hpp', 'outside');
    symlink($root . '/outside.hpp', $output . '/obsolete.hpp');
    publish($files, $output, false);
    requireThat(readFile($root . '/outside.hpp') === 'outside', 'Cleanup followed file symlink');
    mkdir($root . '/external');
    file_put_contents($root . '/external/safe.cpp', 'outside');
    symlink($root . '/external', $output . '/linked');
    publish($files, $output, false);
    requireThat(readFile($root . '/external/safe.cpp') === 'outside', 'Cleanup followed directory symlink');
    $bad = $files + ['../outside.hpp' => 'replace'];
    expectFailure(fn() => publish($bad, $output, false), 'Invalid generated artifact path');
    requireThat(readFile($root . '/outside.hpp') === 'outside', 'Invalid output escaped directory');
    requireThat(readFile($output . '/public/scpp/core.hpp') === "// core\n", 'Invalid publication changed current files');
    echo "Publication cleanup contracts passed\n";
}
finally {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) rmdir($entry->getPathname());
        else unlink($entry->getPathname());
    }
    rmdir($root);
}
