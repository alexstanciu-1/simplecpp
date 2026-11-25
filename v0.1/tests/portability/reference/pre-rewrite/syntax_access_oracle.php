<?php
declare(strict_types=1);
require_once __DIR__ . '/../../compiler/tests/support/parsing_support.php';
require_once __DIR__ . '/reference/metaprogramming_syntax_before.php';
require_once __DIR__ . '/reference/syntax_access_before.php';

function query_outcome(string $owner, string $method, \parse\Syntax_Tree $tree, int $id): array
{
    try {
        $value = $owner::$method($tree, $id);
        return ['value', serialize($value)];
    }
    catch (Throwable $error) {
        return ['error', get_class($error), $error->getMessage()];
    }
}

$methods = [];
foreach ((new ReflectionClass(\parse\Syntax_Access::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if ($method->getNumberOfParameters() === 2) {
        $methods[] = $method->getName();
    }
}
$texts = [
    'struct pair { public int $a; public int $b; public function read(): int { return 1; } }',
    'template<typename T> struct holder { public T $value; }',
    'template<typename T> constexpr function select($x T): T { return $x; }',
    'const N: int = 1; function add($x int): int { $v int = 2; if ($x < $v) { return $x; } else { return $v; } }',
    'function f(): int { $v int; $v = 1; while (false) { $v = 2; } return $v; }',
];
$checks = 0;
foreach ($texts as $text)
{
    $file = Parsing_Test::parse($text);
    $original = $file->syntax;
    // One-field mutations isolate validation order without introducing graph cycles.
    for ($variant = 0; $variant < 13; ++$variant)
    {
        $tree = unserialize(serialize($original));
        if ($variant !== 0)
        {
            $index = ($variant * 7) % count($tree->nodes);
            if (($variant % 3) === 0) {
                $tree->nodes[$index]->kind = \parse\syntax_kind::parameter_list;
            }
            elseif (($variant % 3) === 1) {
                $tree->nodes[$index]->first_child_id = 0;
            }
            else {
                $tree->nodes[$index]->next_sibling_id = 0;
            }
        }
        $before = serialize($tree);
        for ($id = 0; $id < count($tree->nodes) + 2; ++$id)
        {
            foreach ($methods as $method)
            {
                $expected = query_outcome(\parse\Original_Syntax_Access::class, $method, $tree, $id);
                $actual = query_outcome(\parse\Syntax_Access::class, $method, $tree, $id);
                if ($actual !== $expected) {
                    throw new RuntimeException(json_encode([$text, $variant, $id, $method, $expected, $actual], JSON_THROW_ON_ERROR));
                }
                ++$checks;
            }
        }
        if (serialize($tree) !== $before) {
            throw new RuntimeException('Structural query mutated its input');
        }
    }
}
echo 'syntax query oracle: ', $checks, " comparisons passed\n";
