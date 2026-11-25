<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use resolve_types\Signature_Resolver as Signatures;
use resolve_types\Local_Type_Resolver as Locals;
use resolve_types\Type_Resolver;
use type_model\Type_Store;
use resolve_types\Type_Resolution;
use resolve_types\signature_request;
use collect_symbols\symbol_kind;

class Parameter_Type_Store extends Type_Store
{
    public int $completions = 0;

    public function set_representation(int $type_id, int $representation_id): void
    {
        ++$this->completions;
        parent::set_representation($type_id, $representation_id);
    }
}

$manifest = '../fixtures/three_files/project.json';
$catalog_path = getcwd() . '/types.json';
$data = json_decode(file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
$data['types'][] = ['name' => 'Metric', 'namespace' => '', 'kind' => 'integer', 'bit_width' => 23, 'signed' => true,
    'lifetime' => ['copy' => 'value', 'cleanup' => 'none']];
file_put_contents($catalog_path, json_encode($data, JSON_THROW_ON_ERROR));
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$output = getcwd() . '/program';
$baseline = $session->compile($manifest, $output);
$before = $baseline->to_json();
$published = $session->published;
$generation = $session->generation;
$key = hash_file('sha256', $output);
$root = $baseline->inputs->manifest->directory;
$value = $root . '/src/nested/value.phs';
$answer = $root . '/src/answer.phs';
$original_value = file_get_contents($value);
$original_answer = file_get_contents($answer);
$source = 'function value($a int, $b uint32, $c float, $d Metric): int { $copy int = $a; { $a uint32 = $b; } return $copy; }';
Check::edit($value, $source);
Check::edit($answer, 'function answer($x int, $y uint32, $z float, $w Metric): int { return $x; }');
$input = Check::prepare($baseline);
$types = $input->types;
$symbols = $input->symbols;
$names = $input->names;
$value_id = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$answer_id = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$int = $types->types->find_type('int');
$uint = $types->types->find_type('uint32');
$float = $types->types->find_type('float');
$metric = $types->types->find_type('Metric');
Check::check(($types->locals_for($value_id)->type_ids === [$int, $uint, $float, $metric, $int, $uint])
    && ($types->for_symbol($value_id)->representation_id === $types->for_symbol($answer_id)->representation_id),
    'Cross-file identical signatures share an ordered shape; locals include the parameter prefix and shadowed body locals');
foreach ([$int, $uint, $float, $metric] as $position => $id) {
    Check::check(($types->parameter_type_for($value_id, $position + 1) === $id)
        && ($types->locals_for($value_id)->type_for($position + 1) === $id), 'Parameter and local views share canonical type IDs');
}
Check::check(($types->definition_for($metric)->representation->payload->bit_width === 23)
    && ($types->definition_for($uint)->signed === false), 'Provider-defined types and signedness follow ordinary materialization');
Check::rejects(static fn() => $types->parameter_type_for($value_id, 0), 'Missing signature parameter');
Check::rejects(static fn() => $types->parameter_type_for($value_id, 5), 'Missing signature parameter');
$export = json_decode($types->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($export['signatures'], static fn($r) => $r['symbol_id'] === $value_id))[0];
Check::check($row['parameter_type_ids'] === [$int, $uint, $float, $metric], 'Debug signatures export ordered parameter IDs');

// Both worker batches are selected before execution. Local workers need no completed signatures.
$catalog = $types->catalog;
$entry = $types->entry;
$store = new Parameter_Type_Store($types->types->context);
$tasks = Signatures::select($symbols, null, $store, true, $entry);
$local_tasks = Locals::select($symbols, $names, $store, null, true, $entry);
// The coordinator prepares language roles before accepting annotation results.
\resolve_types\Type_Cache::materialize($store, $catalog->integer_literal_type);
\resolve_types\Type_Cache::materialize($store, $catalog->boolean_type);
$fixed = serialize([$symbols, $names, $catalog, $types, $store]);
$local_results = array_map(static fn($t) => Locals::resolve($symbols, $names, $catalog, $t), array_reverse($local_tasks));
$results = array_map(static fn($t) => Signatures::resolve($symbols, $catalog, $t, $entry, $names), array_reverse($tasks));
Check::check((serialize([$symbols, $names, $catalog, $types, $store]) === $fixed)
    && ((count($local_results[0]->definitions) + count($local_results[1]->definitions)) === 2),
    'Workers preserve fixed inputs and request only body-local suffix definitions');
$contracts = (new \resolve_types\Signature_Join($symbols, $catalog, $store, null, $tasks, $entry, $names))->join($results);
$locals = (new \resolve_types\Local_Type_Join($symbols, $names, $catalog, $store, null, $local_tasks, $entry, $contracts))->join($local_results);
$fresh = new Type_Resolution($store, $catalog, $entry, $contracts, $locals, $names);
$full = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $catalog, new Type_Store($store->context), null, true, $entry, $names));
Check::check(($store->completions === 5) && ($fresh->to_json() === $full->to_json()),
    'Reversed workers/full selection give equal exports with one completion per named type');
$warm = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $catalog, clone $types->types, $types, false, $entry, $names));
Check::check((Signatures::select($symbols, $types, $warm->types, false, $entry) === [])
    && (Locals::select($symbols, $names, $warm->types, $types, false, $entry) === [])
    && ($warm->for_symbol($value_id) === $types->for_symbol($value_id))
    && ($warm->locals_for($value_id) === $types->locals_for($value_id)), 'Warm selection shares unchanged complete associations');

// A parameter-only dependency must invalidate both the signature and its local view.
$invalid = clone $types->types;
$invalid->invalidate_definition($metric);
Check::check((count(Signatures::select($symbols, $types, $invalid, false, $entry)) === 2)
    && (count(Locals::select($symbols, $names, $invalid, $types, false, $entry)) === 2),
    'Parameter type invalidation selects affected owners even with unchanged returns');
$repaired_types = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $catalog, $invalid, $types, false, $entry, $names));
Check::check(($repaired_types->parameter_type_for($value_id, 4) === $metric)
    && ($repaired_types->for_symbol($value_id) !== $types->for_symbol($value_id)), 'Definition repair refreshes associations on the same canonical IDs');

$request = array_values(array_filter($results, static fn($r) => $r->symbol->symbol_id === $value_id))[0];
foreach ([[], array_reverse($request->parameter_definitions), [...$request->parameter_definitions, $request->definition],
        [clone $request->parameter_definitions[0], ...array_slice($request->parameter_definitions, 1)]] as $parameters)
{
    $bad = new signature_request($request->symbol, $request->return_annotation_id, $request->definition, $parameters);
    $batch = array_map(static fn($r) => $r === $request ? $bad : $r, $results);
    $candidate = new Type_Store($store->context);
    $empty = $candidate->to_json();
    Check::rejects(static fn() => (new \resolve_types\Signature_Join($symbols, $catalog, $candidate, null, $tasks, $entry, $names))->join($batch), 'Stale');
    Check::check($candidate->to_json() === $empty, 'Bad ordered definitions fail before candidate materialization');
}
foreach ([[], [...$results, $results[0]]] as $batch) {
    Check::rejects(static fn() => (new \resolve_types\Signature_Join($symbols, $catalog, new Type_Store($store->context), null, $tasks, $entry, $names))->join($batch),
        $batch === [] ? 'Incomplete' : 'duplicate');
}
Check::rejects(static fn() => (new \resolve_types\Local_Type_Join($symbols, $names, $catalog, clone $store, null, $local_tasks, $entry, []))->join($local_results), 'Missing or stale local type signature');
$bad_locals = [new \resolve_types\Local_Types($names->for_symbol($value_id), [$uint, $uint, $float, $metric, $int, $uint])];
Check::rejects(static fn() => new Type_Resolution($store, $catalog, $entry, $contracts, $bad_locals, $names), 'Local parameter type differs');

// Checked parameters receive entry lifetimes; the old argument-free caller fails arity checking.
$body = (new \check_bodies\Body_Worker(new \check_bodies\body_check_task($symbols->symbol_by_id($value_id),
    $names->for_symbol($value_id), $types)))->check();
$analysis = (new \analyze_lifetimes\Lifetime_Worker($body))->analyze();
Check::check($analysis->local_for(1)->initialized_statement_id === 0, 'Incoming parameter lifetime starts at entry');
Check::rejects(static fn() => $session->compile($manifest, $output), 'Call argument count does not match the resolved signature');

Check::edit($value, str_replace('$b uint32', '$b float', $source));
$edited = Check::prepare($baseline, $input);
Check::check(($edited->types->parameter_type_for($value_id, 2) === $float)
    && ($edited->types->for_symbol($answer_id) === $types->for_symbol($answer_id)), 'Annotation edit replaces its owner and shares unaffected signatures');
Check::rejects(static fn() => (new \resolve_types\Signature_Join($edited->symbols, $catalog, clone $store, $types, [$edited->symbols->symbol_by_id($value_id)], $edited->types->entry, $names))->join([$request]), 'Unexpected');
$comparison = \Step_Test::run(new \collect_symbols\Symbol_Comparer(\Step_Test::run(new \collect_symbols\Declaration_Collector(
        $input->symbols, $edited->inputs->sources, $edited->inputs->frontends, false)), false));
Check::check((count(array_filter($comparison->changes, static fn($c) => ($c->current?->symbol_id === $value_id)
                && ($c->own_status === \collect_symbols\change_status::changed))) === 1) && (!\compile\Input_Selection::supports_increment($comparison)),
    'Parameter annotation edits are definition changes requiring full-rebuild admission');

Check::edit($value, str_replace('$a int, $b uint32', '$b uint32, $a int', $source));
$reordered = Check::prepare($baseline, $input);
Check::check(($reordered->types->parameter_type_for($value_id, 1) === $uint)
    && ($reordered->types->locals_for($value_id)->type_for(2) === $int), 'Reordering changes positions in signature and locals together');
Check::edit($value, str_replace('$copy int = $a;', '$copy int = 42;', $source));
$body_edit = Check::prepare($baseline, $input);
Check::check(($body_edit->types->for_symbol($value_id)->representation_id === $types->for_symbol($value_id)->representation_id)
    && ($body_edit->types->for_symbol($value_id) !== $types->for_symbol($value_id)), 'Body reparse refreshes anchors and shares signature shape');

foreach ([['Missing', 'Unknown or unsupported parameter type'], ['void', 'A parameter requires a value type']] as [$name, $reason]) {
    $text = 'function value($unused ' . $name . '): int { return 42; }';
    Check::edit($value, $text);
    $error = Check::rejects(static fn() => $session->compile($manifest, $output), $reason);
    Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $value) && ($error->start === strpos($text, $name)),
        'Unused invalid parameter annotation has an exact diagnostic');
}
Check::check(($session->published === $published) && ($session->generation === $generation)
    && (hash_file('sha256', $output) === $key) && ($baseline->to_json() === $before), 'Failures preserve accepted observations and executable');
Check::edit($value, 'function value(' . implode(',', array_map(static fn($i) => '$p' . $i . ' int', range(1, 1500))) . '): int { return $p1500; }');
$wide = Check::prepare($baseline);
Check::check(($wide->types->signature_for($value_id)->count === 1500)
    && ($wide->types->parameter_type_for($value_id, 1500) === $wide->types->integer_literal_type()), 'Wide parameter lists share one type without recursive traversal');
Check::edit($value, $original_value);
Check::edit($answer, $original_answer);
$removed = Check::prepare($baseline, $input);
Check::check(($removed->types->signature_for($value_id)->count === 0) && ($removed->types->locals_for($value_id) === null),
    'Removing parameters retires their association prefix');
$repair = $session->compile($manifest, $output);
Check::check(($repair->completed) && ($baseline->to_json() === $before), 'Failure followed by repair returns to the real executable pipeline');
$process = proc_open([$output], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
Check::check(is_resource($process) && (proc_close($process) === 42), 'Repaired executable returns 42');
echo "parameter types ok: ordered/shared signatures and locals, catalog facts, workers/joins, reuse, invalidation, edits, diagnostics, exports and downstream gates\n";
