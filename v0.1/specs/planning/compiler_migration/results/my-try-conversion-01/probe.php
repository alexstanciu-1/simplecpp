<?php
require getcwd() . '/tools/php_portability/src/converter.php';
require getcwd() . '/tools/php_portability/src/project_cache.php';
$root = $argv[1] ?? throw new RuntimeException('Pass a probe directory containing source/');
$map = require getcwd() . '/tools/php_portability/function_map.php';
$cache = new scpp\portability\Project_Cache($root . '/source', null, $map);
$files = $cache->scan();
$index = new scpp\portability\Declaration_Index($files);
$results = [];
foreach ($files as $path => $entry) {
    $start = microtime(true);
    $row = ['path' => $path, 'sha256' => hash_file('sha256', $root . '/source/' . $path)];
    try {
        $converter = new scpp\portability\Converter($map);
        $text = $converter->convertTokens($index->expand($path, $cache->load(...)), $path);
        $out = $root . '/partial/' . substr($path, 0, -4) . '.phs';
        if (!is_dir(dirname($out))) { mkdir(dirname($out), 0777, true); }
        file_put_contents($out, $text);
        $row['status'] = 'converted';
        $row['output_sha256'] = hash('sha256', $text);
    } catch (Throwable $error) {
        $row['status'] = 'rejected';
        $row['diagnostic'] = $error->getMessage();
    }
    $row['seconds'] = round(microtime(true) - $start, 6);
    $results[] = $row;
    echo $path . ': ' . ($row['diagnostic'] ?? 'converted') . "\n";
}
file_put_contents($root . '/results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
