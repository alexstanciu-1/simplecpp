<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Generate the bounded compiler storage/literal policy from the shared scalar registry. */
function renderCompiler(mixed $metadata, array $facts): array {
    $types = registry($metadata, $facts);
    requireThat(isset($types['int64']), 'Compiler lowering requires int64 metadata');
    $source = readFile(__DIR__ . '/../templates/native_values.php.tpl');
    $source = str_replace('@representation@', $types['int64']['representation'], $source);
    $files = ['cpp/native_values.php' => $source];
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 1,
        'files' => array_keys($files)]) . "\n";
    return $files;
}
