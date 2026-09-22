<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use check_templates\Template_Checker;
use check_templates\Template_Join;
use check_templates\Template_Set;
use check_templates\Template_Worker;
use check_templates\definition_result;

final class Generic_Test
{
    /** Compile/run through the ordinary native pipeline; fixture identity never reaches semantic logic. */
    public static function execute(string $output): int
    {
        exec(escapeshellarg($output), $lines, $status);
        return $status;
    }
}

$root = getcwd() . '/generic-contracts';
mkdir($root);
file_put_contents($root . '/project.json', json_encode(['source_folders' => ['.'], 'entry' => 'main.phs']));
$definitions = <<<'PHS'
const SEVEN: int32 = 7;
const BYTE: uint8 = 3;
struct first { public int32 $value; }
struct second { public uint8 $value; }
template<typename T> struct holder {
    public T $value;
    public const function get(): T { return $this->value; }
    public function set($value T): void { $this->value = $value; }
}
template<typename T> function identity($value T): T { $copy T = $value; $copy = $value; return $copy; }
template<typename A, typename B> function first_value($left A, $right B): A { return identity<A>($left); }
template<typename T> function observe(const T &$value): int { return 2; }
template<typename T> function copy_forward(const T &$value): int {
    $copy T = $value;
    $copy = $value;
    return observe<T>($copy);
}
template<typename T> function read_holder(const holder<T> &$value): T { return $value->get(); }
PHS;
file_put_contents($root . '/definitions.phs', $definitions);
$main = <<<'PHS'
$a first;
$b second;
$box holder<int32>;
$box->set(SEVEN);
if (identity<uint8>(BYTE) < BYTE) { return 100; }
$left int = first_value<int32, uint8>(SEVEN, BYTE);
$read int = read_holder<int32>($box);
return identity<int>(5) + 3 + $left + copy_forward<first>($a) + copy_forward<second>($b) + $read;
PHS;
file_put_contents($root . '/main.phs', $main);
$session = new \compile\Compiler_Session();
$first = $session->compile($root . '/project.json', $root . '-program');
Check::check(Generic_Test::execute($root . '-program') === 26, 'Generic copies, assignments, two slots, records and declared family members execute');
$set = $first->types->instances->template_checks();
Check::check(count($set->definitions) === 8, 'Each definition is checked once, including family methods, independent of specialization count');
$before = serialize($first);

// Fixed worker inputs and private outputs use the same phase units as compilation.
$tasks = Template_Checker::select($first->symbols->current, $first->resolutions, $first->types->catalog, new Template_Set(), true);
$outputs = array_map(static fn($task) => (new Template_Worker($task, $first->symbols->current,
    $first->resolutions, $first->types->catalog))->check(), $tasks);
$join = new Template_Join($first->symbols->current, $first->resolutions, $first->types->catalog, new Template_Set(), $tasks);
$accepted = $join->join(array_reverse($outputs));
Check::check(array_keys($accepted->definitions) === array_keys($set->definitions), 'Reverse completion preserves definition ordering');
Check::rejects(static fn() => $join->join(array_slice($outputs, 1)), 'Incomplete');
Check::rejects(static fn() => $join->join([...$outputs, $outputs[0]]), 'duplicate');
$altered = $outputs;
$row = $altered[0];
$altered[0] = new definition_result($row->task, $row->catalog, [], $row->bindings, $row->visited_nodes);
Check::rejects(static fn() => $join->join($altered), 'Invalid');
$altered[0] = new definition_result($row->task, clone $row->catalog, $row->dependencies, $row->bindings, $row->visited_nodes);
Check::rejects(static fn() => $join->join($altered), 'stale');
Check::check(serialize($first) === $before, 'Workers and rejected joins preserve retained snapshots');

// One ordinary body edit selects no definition work and preserves accepted proof identities.
Check::edit($root . '/main.phs', str_replace('identity<int>(5)', 'identity<int>(6)', $main));
$second = $session->compile($root . '/project.json', $root . '-program');
Check::check((Generic_Test::execute($root . '-program') === 27) && !$second->inputs->context->full_rebuild,
    'One ordinary body increment changes execution');
Check::check($second->types->instances->template_checks()->selected_count === 0, 'Unchanged definitions are reused before computation');
foreach ($set->definitions as $id => $result) {
    Check::check($second->types->instances->template_checks()->definitions[$id] === $result, 'Unchanged definition result identity is retained');
}
Check::check(serialize($first) === $before, 'Increment preserves the previous snapshot');

// Definition diagnostics do not depend on instantiation or a favorable concrete argument.
$negative = $root . '/invalid.phs';
foreach ([
    ['template<typename T> function bad(const T &$x): int { return $x->length; } return 0;', 'member access'],
    ['template<typename T> function bad(const T &$x): int { $copy T = $x; return $copy->length; } return 0;', 'member access'],
    ['template<typename T> function id($x T): T { return $x; } template<typename T> function bad($x T): int { $copy T = id<T>($x); return $copy->length; } return 0;', 'member access'],
    ['struct item { public int32 $length; } template<typename T> function bad(const T &$x): int { return $x->length; } $x item; return bad<item>($x);', 'member access'],
    ['template<typename T> function bad($x T): T { return $x + $x; } return 0;', 'operator'],
    ['template<typename T> function bad($x T): int { if ($x) { return 1; } return 0; } return 0;', 'condition'],
    ['template<typename A, typename B> function bad($a A, $b B): A { $a = $b; return $a; } return bad<int, int>(1, 2);', 'same declared type'],
    ['function concrete($x int): int { return $x; } template<typename T> function bad($x T): int { return concrete($x); } return 0;', 'same declared type'],
    ['template<typename T> function bad(): T { return new T(); } return 0;', 'default construction'],
    ['template<typename T> function bad(): int { $x T; return 0; } return 0;', 'default construction'],
    ['template<typename T> struct bad { public T $values[16]; } return 0;', 'fixed-array initialization'],
    ['template<typename T> struct bad { public T $value; public function __construct(): void {} } return 0;', 'field default construction'],
    ['template<typename T> function bad(const T &$x, const T &$y): int { $x = $y; return 0; } return 0;', 'const reference'],
    ['template<typename T> function bad(T &$x): int { return 0; } return 0;', 'Mutable borrowing'],
    ['template<typename T> function unused(): int { return 0; } return unused<void>();', 'value lifetime'],
] as [$source, $reason])
{
    file_put_contents($negative, $source);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session())->compile($negative), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Generic rejection has a source anchor');
}

// Imported contracts use the same baseline query; no native fixture-family name dispatch.
$data = json_decode(file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
$catalog = \load_runtime\Catalog_Syntax::parse(json_encode($data, JSON_THROW_ON_ERROR));
$provider = $data['types'][4];
$provider['name'] = 'uncopyable';
$provider['lifetime']['copy'] = 'unavailable';
$data['types'][] = $provider;
$catalog_path = $root . '/catalog.json';
file_put_contents($catalog_path, json_encode($data, JSON_THROW_ON_ERROR));
$prefix = 'template<typename T> function unused(): int { return 0; } ';
foreach ([
    ['return unused<uncopyable>();', 'copy construction'],
    ['struct nested { public uncopyable $field; } return unused<nested>();', 'copy construction'],
    ['struct copied { public uncopyable $field; public function __copy_construct(const copied &$source): void {} } return unused<copied>();', 'copy assignment'],
    ['template<typename T> function borrow(const T &$value): int { return 0; } struct nested { public uncopyable $field; } $x nested; return borrow<nested>($x);', 'copy construction'],
] as [$source, $reason])
{
    file_put_contents($negative, $prefix . $source);
    Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($negative), $reason);
}
$type = $catalog->integer_literal_type;
$no_assign = new \type_model\named_type_definition('no_assign', '', $type->representation,
    new \type_model\lifetime_contract(\type_model\copy_kind::value, \type_model\cleanup_kind::none), $type->signed);
Check::check(\type_model\Generic_Contracts::missing($no_assign, \type_model\generic_contract::copyable_value) === 'copy assignment',
    'Copy construction alone does not satisfy the baseline');
echo "generic contracts: native copy/forwarding, definition restrictions, private joins and one retained increment passed\n";
