<?php
declare(strict_types=1);
namespace scpp\compiler;
require $argv[1] . '/compiler/boot.php';

function shape(ast_expression $node): string {
    if ($node instanceof ast_index_expression) return 'index(' . shape($node->receiver) . ', ' . shape($node->index) . ')';
    if ($node instanceof ast_field_expression) return 'field(' . shape($node->receiver) . ', ' . $node->field . ')';
    if ($node instanceof ast_call_expression) {
        $parts = $node->receiver === null ? [] : [shape($node->receiver)];
        foreach ($node->arguments as $arg) $parts[] = shape($arg);
        return $node->name . '(' . implode(', ', $parts) . ')';
    }
    return $node->source_text();
}

try {
    $file = (new Source_Frontend())->parse(new source_file('audit.' . $argv[2], stream_get_contents(STDIN), flavor::from($argv[2])));
    foreach ($file->main()->body()->statements as $stmt) {
        if ($stmt instanceof ast_call_statement) echo shape($stmt->value), "\n";
    }
} catch (diagnostic $e) {
    fwrite(STDOUT, $e->getMessage());
    exit(1);
}
