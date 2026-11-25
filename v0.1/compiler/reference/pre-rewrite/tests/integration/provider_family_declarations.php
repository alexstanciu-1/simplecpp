<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use runtime_preparation\families\Catalog;
use load_runtime\Family_Adapter;

$root = getcwd() . '/family-declarations';
Files::directory($root . '/project');
$data = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/tests/families/catalog.json');
$mapping = ['signed32' => 'int32', 'signed64' => 'int', 'byte' => 'uint8', 'size' => 'native_size', 'nothing' => 'void'];
foreach ($data['types'] as &$type) {
    $type['language_type'] = ['name' => $mapping[$type['id']], 'namespace' => ''];
}
unset($type);
foreach ($data['families'] as &$family)
{
    $family['language_type'] = ['name' => $family['id'] === 'sequence' ? 'bag' : 'duo', 'namespace' => ''];
    foreach ($family['operations'] as &$operation) {
        if (isset($operation['receiver'])) {
            $operation['expose_as'] = ['name' => match ($operation['id']) { 'append_copy' => 'put', 'difference' => 'length', default => $operation['id'] }, 'namespace' => ''];
        }
    }
    unset($operation);
}
unset($family);
$native = Catalog::parse($data, 'fixture');
$declarations = Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($native->families)),
    Catalog::language_bindings($native));
$language = Files::json(dirname(__DIR__, 2) . '/language/named_types.json');
$size = $language['types'][1];
$size['name'] = 'native_size';
$size['signed'] = false;
$language['types'][] = $size;
Files::write_json($root . '/types.json', $language);
$definitions = <<<'PHS'
template<typename T> function identity($value T): T { return $value; }
template<typename T> function insert(bag<T> &$items, const T &$value): void { $items->put($value); }
template<typename T> function forward(bag<T> &$items, const T &$value): void { insert<T>($items, $value); }
template<typename A, typename B> function difference(const duo<A, B> &$pair): int { return $pair->length(); }
template<typename T> function read(const bag<T> &$items, $index native_size): T { return identity<T>($items->read_copy($index)); }
template<typename T> function count(const bag<T> &$items): native_size { return $items->length(); }
PHS;
Files::write($root . '/project/definitions.phs', $definitions);
Files::write($root . '/project/main.phs', 'return identity<int>(41);');
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$session = new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', family_declarations: $declarations);
$first = $session->compile($root . '/project/project.json', $root . '/program');
exec(escapeshellarg($root . '/program'), $stdout, $status);
Check::check($first->completed && ($status === 41), 'Ordinary native compilation checks unused provider templates without preparing native families');
$symbols = $first->symbols->current;
$bag_id = $symbols->find_symbol('bag', '', \collect_symbols\symbol_kind::template_struct);
$bag = $symbols->symbol_by_id($bag_id);
$put_id = $symbols->find_symbol('put', '', \collect_symbols\symbol_kind::template_function, $bag_id);
$put = $symbols->symbol_by_id($put_id);
Check::check(($bag->frontend === null) && ($put->frontend === null) && ($put->owner_symbol_id === $bag_id)
    && ($put->external->operation === $declarations[0]->definition->operations['append_copy']) && !$put->has_executable_body(),
    'Exposed methods keep semantic owner identity and original operation without fabricated AST/ABI');
Check::check($symbols->find_symbol('put', '', \collect_symbols\symbol_kind::template_function) === 0, 'Member exposure does not create a global function');
$duo_id = $symbols->find_symbol('duo', '', \collect_symbols\symbol_kind::template_struct);
Check::check($symbols->find_symbol('length', '', \collect_symbols\symbol_kind::template_function, $duo_id)
    !== $symbols->find_symbol('length', '', \collect_symbols\symbol_kind::template_function, $bag_id), 'Same member spelling remains scoped by its family');
Check::check(($first->backend->runtime === null) && (count($first->types->instances->template_checks()->definitions) === 6),
    'Symbolic permissions require no prepared runtime package');
$before = serialize($first);
Check::edit($root . '/project/main.phs', 'return identity<int>(42);');
$second = $session->compile($root . '/project/project.json', $root . '/program');
exec(escapeshellarg($root . '/program'), $stdout, $status);
Check::check(($status === 42) && !$second->inputs->context->full_rebuild
    && ($second->symbols->current->symbol_by_id($put_id) === $put)
    && ($second->types->instances->template_checks()->selected_count === 0) && (serialize($first) === $before),
    'Body increment reuses provider declarations and definition permissions without mutating snapshots');

// Permission workers use the normal fixed-input task/join path independently of completion order.
$checks = new \check_templates\Template_Set();
$tasks = \check_templates\Template_Checker::select($symbols, $first->resolutions, $first->types->catalog, $checks, true);
$results = array_map(static fn($task) => (new \check_templates\Template_Worker($task, $symbols, $first->resolutions, $first->types->catalog))->check(), $tasks);
$join = new \check_templates\Template_Join($symbols, $first->resolutions, $first->types->catalog, $checks, $tasks);
Check::check(count($join->join(array_reverse($results))->definitions) === 6, 'Provider permissions join reordered private results');
Check::rejects(static fn() => $join->join(array_slice($results, 1)), 'Incomplete');
Check::check(serialize($first) === $before, 'Permission workers and rejected join preserve original inputs');

// Changed metadata dependencies invalidate the affected definition proofs without entering bodies of callees.
$renamed = $data;
$renamed['families'][0]['operations'][2]['expose_as']['name'] = 'store';
$renamed_native = Catalog::parse($renamed, 'fixture');
$renamed_declarations = Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($renamed_native->families)),
    Catalog::language_bindings($renamed_native));
$changed_symbols = Step_Test::run(new \collect_symbols\Declaration_Collector($symbols, $first->inputs->sources,
    $first->inputs->frontends, false, null, $renamed_declarations))->current;
$changed_names = Step_Test::run(new \resolve_symbols\Symbol_Resolver($changed_symbols, $first->resolutions, false, $first->types->catalog));
$selected = \check_templates\Template_Checker::select($changed_symbols, $changed_names, $first->types->catalog,
    $first->types->instances->template_checks(), false);
Check::check(count($selected) === 5, 'Family changes select dependent definitions while retaining unrelated identity template');
Check::rejects(static fn() => (new \check_templates\Template_Join($changed_symbols, $changed_names, $first->types->catalog,
    $first->types->instances->template_checks(), $selected))->join($results), 'stale');
Check::check(serialize($first) === $before, 'Metadata replacement checks do not mutate previous symbols or permission results');

// Every bad definition remains unused: no favorable instantiation can authorize these operations.
foreach ([
    ['template<typename T> function bad($v bag<T>): void {}', 'Whole provider value lifecycle'],
    ['template<typename T> function bad(const bag<T> &$v): bag<T> { return $v; }', 'Whole provider value lifecycle'],
    ['template<typename T> function bad(const bag<T> &$v): void { $copy bag<T> = $v; }', 'Whole provider value lifecycle'],
    ['template<typename T> function bad(bag<T> &$v, const bag<T> &$x): void { $v = $x; }', 'Whole provider value lifecycle'],
    ['template<typename T> function bad(bag<T> &$v): void { $v->put(1); }', 'same declared type'],
    ['template<typename A, typename B> function bad(bag<A> &$v, const B &$x): void { $v->put($x); }', 'same declared type'],
    ['template<typename T> function bad(const bag<T> &$v, const T &$x): void { $v->put($x); }', 'non-const receiver'],
    ['template<typename T> function bad(bag<T> &$v): void { $v->missing(); }', 'Unknown method'],
    ['template<typename T> function bad(bag<T> &$v): int { return $v->value; }', 'structural fields'],
    ['template<typename T> function bad(bag<T> &$v): void { $v->put(); }', 'argument count'],
    ['template<typename T> function bad(bag<T, T> &$v): void {}', 'argument count'],
    ['template<typename T> function bad(duo<T> &$v): void {}', 'argument count'],
    ['template<typename T> struct wrapper { public T $item; } template<typename T> function bad(wrapper<bag<T>> &$v): void {}', 'baseline is not established'],
    ['template<typename T> function bad(bag<bag<T>> &$v): void {}', 'baseline is not established'],
    ['template<typename T> function bad(const T &$v): int { return $v->length(); }', 'member access'],
    ['template<typename T> function bad(const bag<T> &$v): void { identity<bag<T>>($v); }', 'baseline is not established'],
] as [$body, $message])
{
    Files::write($root . '/invalid.phs', $definitions . "\n" . $body . "\nreturn 0;");
    $invalid = new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', family_declarations: $declarations);
    Check::rejects(static fn() => $invalid->compile($root . '/invalid.phs'), $message);
}
Files::write($root . '/demand.phs', '$v bag<int>; return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', family_declarations: $declarations))
    ->compile($root . '/demand.phs'), 'specialization preparation is not implemented');

// A receiver may occupy any declared semantic position; argument matching must remove exactly that slot.
$shifted = $data;
$append = &$shifted['families'][0]['operations'][2];
$append['parameters'] = array_reverse($append['parameters']);
$append['receiver'] = 1;
$append['effects'][0]['receiver'] = 1;
unset($append);
$shifted_native = Catalog::parse($shifted, 'fixture');
$shifted_declarations = Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($shifted_native->families)),
    Catalog::language_bindings($shifted_native));
Files::write($root . '/shifted.phs', $definitions . "\nreturn 0;");
Check::check((new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', family_declarations: $shifted_declarations))
    ->compile($root . '/shifted.phs')->bodies !== null, 'Non-leading receiver uses the same symbolic call contract');
$mutable = $data;
$mutable['families'][0]['operations'][2]['parameters'][1]['passing'] = 'mutable_address';
$mutable_native = Catalog::parse($mutable, 'fixture');
$mutable_declarations = Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($mutable_native->families)),
    Catalog::language_bindings($mutable_native));
Files::write($root . '/mutable.phs', 'template<typename T> function bad(bag<T> &$items, $value T): void { $items->put($value); } return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', family_declarations: $mutable_declarations))
    ->compile($root . '/mutable.phs'), 'Mutable borrowing of bare generic T');

$value_input = $data;
$value_input['families'][0]['operations'][2]['parameters'][1] = '$self';
$value_native = Catalog::parse($value_input, 'fixture');
$value_declarations = Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($value_native->families)),
    Catalog::language_bindings($value_native));
Files::write($root . '/value.phs', 'template<typename T> function bad(bag<T> &$items, const bag<T> &$other): void { $items->put($other); } return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', family_declarations: $value_declarations))
    ->compile($root . '/value.phs'), 'Whole provider value lifecycle');

// Source exposure cannot alter native identity or raise the formal baseline.
$bad = $data;
$bad['families'][0]['operations'][2]['requires'][0]['operation'] = 'default_construct';
Check::rejects(static fn() => Catalog::parse($bad, 'fixture'), 'baseline');
$bad = $data;
$bad['families'][0]['operations'][3]['expose_as']['name'] = 'put';
Check::rejects(static fn() => Catalog::parse($bad, 'fixture'), 'duplicate');
$bad = $data;
$bad['families'][0]['operations'][0]['expose_as'] = ['name' => 'init', 'namespace' => ''];
Check::rejects(static fn() => Catalog::parse($bad, 'fixture'), 'member exposure');
$bindings = Catalog::language_bindings($native);
unset($bindings[json_encode(['fixture', 'size'])]);
$unmapped = Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($native->families)), $bindings);
Check::rejects(static fn() => Family_Adapter::validate_exposures($unmapped, $first->types->catalog), 'explicit family language type mapping');
echo "family declarations ok: metadata exposure, scoped members, two slots, unused-definition permissions, native ordinary output and one increment\n";
