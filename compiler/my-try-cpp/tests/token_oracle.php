<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/my-try/boot.php';
$root = $argv[1];
$paths = [];
foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $path) {
    if ($path->isFile() && $path->getExtension() === 'phs') { $paths[] = $path->getPathname(); }
}
sort($paths, SORT_STRING);
foreach ($paths as $path) {
    $source = new file();
    $source->path = substr($path, strlen($root) + 1);
    $source->content = file_get_contents($path);
    $tokens = (new Tokenizer($source))->tokenize();
    echo 'FILE ', $source->path, "\n";
    foreach ($tokens->tokens as $token) { echo $token->offset, ' ', $token->length, "\n"; }
}
