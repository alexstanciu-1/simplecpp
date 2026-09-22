<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/parsing_support.php';
use Body_Test_Stages as Check;

final class Comparison_Test
{
    /** Execute the native program so both comparison results and evaluation effects are observed. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        Check::check(is_resource($process), 'Start comparison executable');
        return proc_close($process);
    }
}

// Angle pairing is lexical and scoped: named comparisons coexist with nested template suffixes.
foreach (['return LIMIT < choose<int>();', 'return choose<box<item>, (LIMIT < 3)>();',
    'return choose<int>(LIMIT < 3);', 'return LIMIT < 3 + 4;', 'return choose<int>;'] as $syntax) {
    Parsing_Test::parse($syntax)->validate();
}
$grouped = Parsing_Test::parse('return 1 + (2 + 3) < 7;');
$operators = [];
foreach ($grouped->syntax->nodes as $index => $node) {
    if (\parse\Binary_Syntax::operation($node->kind) !== null) {
        $operators[] = Parsing_Test::text($grouped, $index + 1);
    }
}
Check::check($operators === ['2 + 3', '1 + (2 + 3)', '1 + (2 + 3) < 7'],
    'Delayed binary reductions preserve complete grouped source spans');
$depth = 2000;
$deep = Parsing_Test::parse('return ' . str_repeat('(', $depth) . '1 < 2' . str_repeat(')', $depth) . ';');
$chain = Parsing_Test::parse('return 0' . str_repeat(' < 1', $depth) . ';');
Check::check(count(array_filter($chain->syntax->nodes, static fn($node) => $node->kind === \parse\syntax_kind::less_than_expression)) === $depth,
    'Deep groups and long binary chains use flat iterative syntax');
foreach (['return 1 <;', 'return < 1;', 'return 1 + < 2;'] as $invalid) {
    Check::rejects(static fn() => Parsing_Test::parse($invalid), 'Expected literal');
}

$root = getcwd() . '/comparison-proof';
mkdir($root);
$path = $root . '/main.phs';
$source = <<<'PHS'
const LIMIT = 4;
const ONE: int32 = 1;
const HIGH: uint32 = 4294967295;
const LOW: uint32 = 1;
struct counter { public int32 $value; public bool $ready; }
function left(counter &$state): int { $state->value = $state->value + ONE; return 1; }
function right(counter &$state): int { $state->value = $state->value + $state->value; return 2; }
function compare($a int, $b int): bool { return $a < $b; }
template<int N>
function bound($value int): bool { return $value < N; }
template<typename T>
struct shell { public T $value; }
function check(): int {
    $i int = 0;
    while ($i + 1 < LIMIT + 1) { $i = $i + 1; }
    if ($i < LIMIT) { return 10; }
    if (LIMIT < $i) { return 11; }
    if (1 + 2 < 2 + 2) { } else { return 12; }
    if (9223372036854775807 + 1 < 0) { } else { return 13; }
    if (HIGH < LOW) { return 14; }
    if (LOW < HIGH) { } else { return 15; }
    if (bound<3>(2)) { } else { return 16; }
    $nested shell<shell<int32>>;
    $nested->value->value = ONE;
    $yes bool = true;
    $no bool = false;
    if ($no) { return 17; }
    if ($yes) { } else { return 18; }
    if (compare(1, 2)) { } else { return 19; }
    $order counter;
    if ($order->ready) { return 21; }
    $order->ready = true;
    if ($order->ready) { } else { return 22; }
    if (left($order) < right($order)) { } else { return 20; }
    return $order->value;
}
return check();
PHS;
file_put_contents($path, $source);
$session = new \compile\Compiler_Session();
$first = $session->compile($path, $root . '/program');
Check::check(Comparison_Test::run($root . '/program') === 2, 'Signed/unsigned order, equality, precedence, boolean storage, templates and left-to-right effects execute');
$fixed = serialize($first);
$tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), $tasks);
$join = new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $first->types, new \check_bodies\Body_Set(), $tasks);
Check::check($join->join(array_reverse($results))->to_json() === $first->bodies->to_json(), 'Binary workers join independently of completion order');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
Check::edit($path, str_replace('return $a < $b;', 'return $a + 1 < $b;', $source));
$second = $session->compile($path, $root . '/program');
Check::check((Comparison_Test::run($root . '/program') === 19) && (!$second->inputs->context->full_rebuild)
    && (serialize($first) === $fixed), 'Comparison body increment changes execution and preserves the old snapshot');

// Catalog roles and permissions, not the bool spelling or a fixed integer width, govern selection.
$catalog = json_decode(file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
$catalog['literal_types']['boolean']['name'] = 'truth';
foreach ($catalog['types'] as &$row) {
    if ($row['name'] === 'bool') {
        $row['name'] = 'truth';
    }
}
unset($row);
$catalog_path = $root . '/types.json';
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
file_put_contents($path, '$value truth = 1 < 2; if ($value) { return 3; } return 4;');
(new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($path, $root . '/program');
Check::check(Comparison_Test::run($root . '/program') === 3, 'Comparison follows a renamed boolean role');
// A new width needs only a truthful capability declaration; it need not support arithmetic.
$catalog['types'][] = ['name' => 'small_order', 'namespace' => '', 'kind' => 'integer',
    'bit_width' => 17, 'signed' => false, 'comparison' => 'ordered',
    'lifetime' => ['copy' => 'value', 'cleanup' => 'none']];
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
file_put_contents($path, 'const HIGH: small_order = 131071; const LOW: small_order = 1; if (LOW < HIGH) { return 7; } return 8;');
(new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($path, $root . '/program');
Check::check(Comparison_Test::run($root . '/program') === 7, 'Metadata authorizes ordering for a new integer width without arithmetic');
$invalid_catalog = $catalog;
foreach ($invalid_catalog['types'] as &$row) {
    if ($row['name'] === 'truth') {
        $row['bit_width'] = 2;
    }
}
unset($row);
file_put_contents($catalog_path, json_encode($invalid_catalog, JSON_THROW_ON_ERROR));
Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($path), 'one-bit');
foreach ($catalog['types'] as &$row) {
    unset($row['comparison']);
}
unset($row);
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($path), 'Unsupported less_than');
foreach ([
    'const SMALL: uint8 = 1; return 0; function bad($v int): bool { return $v < SMALL; }',
    'return 0; function bad($a float, $b float): bool { return $a < $b; }',
    'return 0; function bad(): bool { return true < false; }',
] as $invalid) {
    file_put_contents($path, $invalid);
    Check::rejects(static fn() => (new \compile\Compiler_Session())->compile($path), 'Unsupported less_than');
}
echo "integer comparison tests passed\n";
