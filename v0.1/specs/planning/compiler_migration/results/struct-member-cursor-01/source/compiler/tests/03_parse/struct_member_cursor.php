<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/parsing_support.php';
require_once __DIR__ . '/../support/body_support.php';

use Body_Test_Stages as Check;
use parse\Syntax_Access as Syntax;
use parse\syntax_kind;

// Frozen generator algorithm from the adoption checkpoint: host-only reference.
function original_struct_members(\parse\Syntax_Tree $tree, int $declaration, syntax_kind $kind): Generator
{
    $parts = Syntax::struct_parts($tree, Syntax::underlying_declaration($tree, $declaration));
    for ($id = $parts->first_member_id; $id !== 0; $id = $tree->nodes[$id - 1]->next_sibling_id) {
        if ($tree->nodes[$id - 1]->kind === $kind) {
            yield $id;
        }
    }
}

$cases = [
    'struct empty {}',
    'struct pair { public int $a; public int $b; }',
    'struct mixed_members { public int $a; public function read(): int { return 1; } public int $b; }',
    'template<typename T> struct holder { public T $value; }',
];
foreach ($cases as $text)
{
    $file = Parsing_Test::parse($text);
    $tree = $file->syntax;
    $declaration = $file->defined_entities[0];
    $before = serialize($tree);
    foreach ([syntax_kind::field_declaration, syntax_kind::method_declaration, syntax_kind::name] as $kind)
    {
        $expected = iterator_to_array(original_struct_members($tree, $declaration, $kind), false);
        $cursor = Syntax::struct_members($tree, $declaration, $kind);
        Check::rejects(static fn() => $cursor->current(), 'not positioned');
        $actual = [];
        while ($cursor->advance()) {
            $actual[] = $cursor->current();
            Check::check($cursor->current() === $actual[count($actual) - 1], 'Repeated current does not advance');
        }
        Check::check($actual === $expected, 'Streaming and materialized IDs preserve original order and filtering');
        Check::check(!$cursor->advance() && !$cursor->advance(), 'Exhaustion is stable');
        Check::rejects(static fn() => $cursor->current(), 'not positioned');
        $counted = 0;
        $counter = Syntax::struct_members($tree, $declaration, $kind);
        while ($counter->advance()) {
            $counted++;
        }
        Check::check($counted === count($expected), 'Counting consumer retains membership');
    }
    Check::check(serialize($tree) === $before, 'Traversal preserves the complete retained tree');
    $cursor = Syntax::struct_members($tree, $declaration, syntax_kind::field_declaration);
    $property = new ReflectionProperty($cursor, 'tree');
    Check::check($property->getValue($cursor) === $tree, 'Cursor shares the original tree identity');
}

$file = Parsing_Test::parse('struct pair { public int $a; public int $b; }');
$tree = $file->syntax;
$id = $file->defined_entities[0];
$parts = Syntax::struct_parts($tree, $id);
$bad = clone $tree;
$bad->nodes[$parts->name_id - 1] = clone $bad->nodes[$parts->name_id - 1];
$bad->nodes[$parts->name_id - 1]->kind = syntax_kind::integer_literal;
$cursor = Syntax::struct_members($bad, $id, syntax_kind::field_declaration);
Check::rejects(static fn() => $cursor->advance(), 'name leaf');
Check::check(!$cursor->advance(), 'Validation failure closes the cursor');
Check::rejects(static fn() => $cursor->current(), 'not positioned');
$cursor = Syntax::struct_members($tree, 0, syntax_kind::field_declaration);
Check::rejects(static fn() => $cursor->advance(), 'struct declaration');

// A poisoned later node proves early termination does not traverse the tail.
$tail = clone $tree;
$first = $parts->first_member_id;
$second = $tail->nodes[$first - 1]->next_sibling_id;
$tail->nodes[$second - 1] = null;
$cursor = Syntax::struct_members($tail, $id, syntax_kind::field_declaration);
set_error_handler(static function (int $severity, string $message): never {
    throw new ErrorException($message, 0, $severity);
});
try {
    Check::check($cursor->advance() && ($cursor->current() === $first), 'First result does not inspect the tail');
}
finally {
    restore_error_handler();
}
Check::check($cursor->current() === $first, 'Early stopping retains current position');
echo "struct member cursor ok: lazy roles, wrappers, order, filtering, exhaustion, identity and early stop\n";
