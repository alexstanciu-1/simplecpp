<?php
declare(strict_types=1);

// Read-only lexical census, not a portability validator or semantic analysis.
// Run from repository root; stdout is the reproducible evidence JSON.
$root = getcwd();
require_once $root . '/tools/php_portability/src/converter.php';
$converter = new scpp\portability\Converter(require $root . '/tools/php_portability/function_map.php');
$roots = ['compiler/src', 'compiler/src-runtime-preparation', 'compiler/tool_process'];
$kinds = [T_TRAIT, T_FN, T_CLONE, T_MATCH, T_FINALLY, T_INSTANCEOF,
    T_NULLSAFE_OBJECT_OPERATOR, T_ELLIPSIS, T_READONLY];
$files = [];
$counts = [];
foreach ($roots as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = $file->getPathname();
        if ($file->getExtension() !== 'php' || str_contains($path, '/tests/')) { continue; }
        $source = file_get_contents($path);
        $features = [];
        foreach (token_get_all($source, TOKEN_PARSE) as $token) {
            if (!is_array($token) || !in_array($token[0], $kinds, true)) { continue; }
            $features[token_name($token[0])][] = $token[2];
        }
        foreach ($features as $kind => $lines) {
            $counts[$kind]['files'] = ($counts[$kind]['files'] ?? 0) + 1;
            $counts[$kind]['occurrences'] = ($counts[$kind]['occurrences'] ?? 0) + count($lines);
        }
        try {
            $converter->convert($source, $path);
            $conversion = 'accepted_syntax_only';
        } catch (RuntimeException $error) {
            $conversion = $error->getMessage();
        }
        $files[$path] = ['sha256' => hash('sha256', $source), 'features' => $features, 'conversion' => $conversion];
    }
}
ksort($files);
ksort($counts);
$owners = [];
foreach (['tools/php_portability/src/converter.php', 'tools/php_portability/src/import_policy.php',
    'tools/php_portability/src/exception_policy.php', 'tools/php_portability/function_map.php'] as $path) {
    $owners[$path] = hash_file('sha256', $path);
}
echo json_encode(['scope' => $roots, 'excluded' => 'tests subdirectories; external bootstrap/composition files; historical sources',
    'php_version' => PHP_VERSION, 'file_count' => count($files), 'converter_owners' => $owners,
    'accepted_syntax_only' => count(array_filter($files, static fn($row) => $row['conversion'] === 'accepted_syntax_only')),
    'token_counts' => $counts, 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
