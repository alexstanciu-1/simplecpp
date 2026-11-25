<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use read_sources\Source_Paths;
use read_sources\Source_Reader;
use read_sources\Source_Set;

class Source_Path_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

$windows = DIRECTORY_SEPARATOR === '\\';
foreach (['', 'src', '../src', 'C:src'] as $path) {
    Source_Path_Test::check(!Source_Paths::is_absolute($path), 'Expected relative path: ' . $path);
}
Source_Path_Test::check(Source_Paths::is_absolute('/src'), 'Slash-rooted path');
foreach (['\\src', '\\\\server\\share', 'C:\\src', 'C:/src'] as $path) {
    Source_Path_Test::check(Source_Paths::is_absolute($path) === $windows, 'Host path syntax: ' . $path);
}

$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader('../fixtures/three_files/project.json'));
Source_Path_Test::check(Source_Paths::resolve($manifest->directory, 'src/../src')
    === Source_Paths::resolve($manifest->directory, 'src'), 'Relative paths resolve from manifest directory');
if (!$windows)
{
    foreach (['\\sources', 'C:\\sources', 'inner\\name'] as $name)
    {
        $root = $manifest->directory . '/' . $name;
        mkdir($root);
        file_put_contents($root . '/main.phs', 'return 42;');
        $manifest->source_folder_paths = [$name];
        $manifest->entry_path = $name . '/main.phs';
        $sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, new Source_Set()));
        Source_Path_Test::check((Source_Paths::resolve($manifest->directory, $name) === $root)
            && (count($sources->files) === 1)
            && ($sources->files[0]->full_path === $root . '/main.phs'),
            'POSIX discovery preserves literal backslashes and resolves against the manifest');
    }
}

echo "source paths ok: host syntax, manifest-relative resolution and native filesystem discovery\n";
