<?php
// Adapt only vocabulary/loading to run the preserved 5,000-stream unit against the rewrite.
$root = dirname(__DIR__, 3);
require $root . '/tools/php_portability/runtime/bootstrap.php';
foreach (['01_prepare_inputs/read_sources/data/buffer.php','02_tokenize/structures.php','02_tokenize/store.php',
    '03_parse/data/nodes.php','03_parse/utilities/binary_syntax.php'] as $file) { require $root . '/compiler/src/' . $file; }
$adapt = static function (string $source): string {
    $source = preg_replace_callback('/(?:\\\\?tokenize\\\\)?token_kind::(\w+)/',
        fn($m) => '\\tokenize\\TOKEN_' . strtoupper($m[1]), $source);
    $source = preg_replace_callback('/syntax_kind::(\w+)/', fn($m) => '\\parse\\SYNTAX_' . strtoupper($m[1]), $source);
    $source = str_replace(['token_kind $kind', 'syntax_kind $kind', '?syntax_kind', 'Token_Buffer', 'tokenize\\token()'],
        ['int $kind', 'int $kind', '?int', 'Lexical_Buffer', 'tokenize\\Token_Row()'], $source);
    return $source;
};
$baseline = $adapt(file_get_contents($root . '/tests/portability/oracles/binary_syntax_original.php'));
eval(substr($baseline, 5));
$unit = file_get_contents($root . '/tests/portability/reference/pre-rewrite/binary_syntax_oracle.php');
$unit = preg_replace('/^require .*;\n/m', '', $unit);
$unit = str_replace("new read_sources\\Source_Buffer(1, 'oracle', 0, '')", 'new read_sources\\Source_Buffer()', $unit);
eval(substr($adapt($unit), 5));
