<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/local_resolution_support.php';

use resolve_symbols\Symbol_Resolver as Resolver;
use resolve_symbols\Symbol_Resolution;
use resolve_symbols\Resolution_Set;
use resolve_symbols\local_record;
use compile\Phases;
use compile\Update_Context;
use collect_symbols\symbol_kind;

$path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$baseline = $session->compile($path);
$before = serialize($baseline);
$root = $baseline->inputs->manifest->directory;
$value_path = $root . '/src/nested/value.phs';
$original = file_get_contents($value_path);
$source = 'function value($first int32, $second uint32): int {
    $copy int32 = $first;
    $second = $copy;
    { $first uint32 = $second; $first = $first; }
    return $first;
}';
Local_Resolution_Test::edit($value_path, $source);
$symbols = Local_Resolution_Test::project($baseline);
$id = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$answer_id = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$tasks = \Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $baseline->resolutions, false, $baseline->types->catalog);
$fixed = serialize([$symbols, $baseline]);
$results = array_map(static fn($task) => (new \resolve_symbols\Resolution_Worker($symbols, $task, $baseline->types->catalog))->run(), array_reverse($tasks));
$names = (new \resolve_symbols\Resolution_Join($baseline->resolutions, $symbols, $tasks, $baseline->types->catalog))->join($results);
$r = $names->for_symbol($id);
Local_Resolution_Test::check(($r->parameter_count === 2) && (count($r->locals) === 4)
    && (array_column($r->locals, 'scope_id') === [1, 1, 1, 2]), 'Parameters and body locals share one flat dataset, with parameters first in root scope');
Local_Resolution_Test::check(array_column($r->local_bindings, 'local_id') === [1, 2, 3, 2, 4, 4, 1],
    'Parameter reads/writes, body initializers and nested shadowing use the same nearest-binding path');
foreach ([1, 2] as $position) {
    $parameter = $r->parameter_for($position);
    Local_Resolution_Test::check(($parameter === $r->local_for($position))
        && ($r->local_for_declaration($parameter->declaration_node_id) === $position),
        'Parameter access returns the existing callable-local record, without a copied parameter dataset');
}
Local_Resolution_Test::check((count($symbols->records()) === count($baseline->symbols->current->records()))
    && ($names->for_symbol($answer_id) === $baseline->resolutions->for_symbol($answer_id))
    && (serialize([$symbols, $baseline]) === $fixed), 'No new project symbols; unchanged callers and fixed inputs stay shared');
$export = json_decode($names->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($export, static fn($r) => $r['symbol_id'] === $id))[0];
Local_Resolution_Test::check(($row['parameter_count'] === 2) && (count($row['locals']) === 4)
    && ($row['locals'][0]['declaration_node_id'] === $r->parameter_for(1)->declaration_node_id),
    'Exports identify the parameter prefix and retain source declaration identities');
Local_Resolution_Test::check((\Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $names, false, $baseline->types->catalog) === [])
    && (Phases::run_symbols($symbols, $names, new Update_Context(), $baseline->types->catalog)->for_symbol($id) === $r), 'Unchanged binding results need no work');

// Declaration binding now rejects unknown annotation names before concrete type preparation.
$unknown_source = str_replace('int32', 'Missing', $source);
Local_Resolution_Test::edit($value_path, $unknown_source);
$unknown_symbols = Local_Resolution_Test::project($baseline);
$type_inputs = serialize([$symbols, $names, $baseline]);
$error = Local_Resolution_Test::rejects(static fn() => Phases::run_symbols(
    $unknown_symbols, $names, new Update_Context(), $baseline->types->catalog), "Unknown or unsupported parameter type 'Missing'");
Local_Resolution_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $value_path)
    && ($error->start === strpos($unknown_source, 'Missing')) && (serialize([$symbols, $names, $baseline]) === $type_inputs),
    'Unknown parameter type names are source-anchored and preserve fixed inputs');
Local_Resolution_Test::edit($value_path, $source);
$full = new Update_Context();
$full->full_rebuild = true;
Local_Resolution_Test::check((Phases::run_symbols($symbols, $names, $full, $baseline->types->catalog)->to_json() === $names->to_json())
    && (Phases::run_symbols($symbols, new Resolution_Set(), new Update_Context(), $baseline->types->catalog)->to_json() === $names->to_json()),
    'Full selection and fresh resolution produce equivalent facts through the common worker/join');
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($baseline->resolutions, $symbols, $tasks, $baseline->types->catalog))->join([]), 'Incomplete');
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($baseline->resolutions, $symbols, $tasks, $baseline->types->catalog))->join([...$results, $results[0]]), 'duplicate');
Local_Resolution_Test::rejects(static fn() => $r->parameter_for(0), 'Missing resolved parameter');
Local_Resolution_Test::rejects(static fn() => $r->parameter_for(3), 'Missing resolved parameter');
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($id, $r->syntax, [], $r->scopes,
        [$r->locals[2], $r->locals[0]]), 'parameters must precede');
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($id, $r->syntax, [], $r->scopes,
        [new local_record($r->locals[0]->declaration_node_id, 2)]), 'root scope');

// Reordering parameters changes their positional identities, not lookup semantics.
Local_Resolution_Test::edit($value_path, str_replace('$first int32, $second uint32', '$second uint32, $first int32', $source));
$edited = Local_Resolution_Test::project($baseline);
$edited_names = Phases::run_symbols($edited, $names, new Update_Context(), $baseline->types->catalog);
Local_Resolution_Test::check((array_column($edited_names->for_symbol($id)->local_bindings, 'local_id') === [2, 1, 3, 1, 4, 4, 2])
    && ($r->to_array() === $row), 'Parameter edits replace binding identity as one callable snapshot without mutating the previous result');
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $edited, [$edited->symbol_by_id($id)], $baseline->types->catalog))->join([$r]), 'stale');

foreach ([
        ['function value($x int, $x int): int { return 42; }', '$x int):', 'Duplicate local'],
        ['function value($x int): int { $x int = 42; return $x; }', '$x int =', 'Duplicate local'],
        ['function value($x int): int { { $x int = $x; } return $x; }', '$x;', 'cannot read itself'],
        ['function value($X int): int { return $x; }', '$x;', 'Unknown local'],
        ['function value($x int): int { return 42; } function leak(): int { return $x; }', '$x;', 'Unknown local'],
    ] as [$text, $anchor, $reason])
{
    Local_Resolution_Test::edit($value_path, $text);
    $error = Local_Resolution_Test::rejects(static fn() => $session->compile($path), $reason);
    Local_Resolution_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $value_path)
        && ($error->start === strpos($text, $anchor)), 'Parameter scope diagnostics anchor the actual failing variable');
    Local_Resolution_Test::check(($session->observed?->resolutions === $baseline->resolutions) && (serialize($baseline) === $before),
        'Failed parameter binding preserves the accepted compiler snapshot');
}

// Binding is independent of types and does not accept a silently empty signature.
Local_Resolution_Test::edit($value_path, 'function value($unused int): int { return 42; }');
$error = Local_Resolution_Test::rejects(static fn() => $session->compile($path), 'Call argument count does not match the resolved signature');
Local_Resolution_Test::check(($error instanceof \diagnostics\Source_Error) && str_ends_with($error->path, '/src/answer.phs')
    && ($session->observed?->resolutions === $baseline->resolutions), 'An omitted argument is rejected even when its parameter is unused');
Local_Resolution_Test::edit($value_path, 'function value($x int): int { return value($x); }');
Local_Resolution_Test::rejects(static fn() => $session->compile($path), 'Call argument count does not match the resolved signature');

// Wide lists use the same declaration loop and ordinal-to-local relation.
$parameters = [];
for ($i = 1; $i <= 1000; ++$i) {
    $parameters[] = '$p' . $i . ' int32';
}
Local_Resolution_Test::edit($value_path, 'function value(' . implode(',', $parameters) . '): int { $copy int32 = $p1000; return $copy; }');
$wide_symbols = Local_Resolution_Test::project($baseline);
$wide = Phases::run_symbols($wide_symbols, $names, new Update_Context(), $baseline->types->catalog)->for_symbol($id);
Local_Resolution_Test::check(($wide->parameter_count === 1000) && (count($wide->locals) === 1001)
    && ($wide->local_bindings[0]->local_id === 1000) && ($wide->local_bindings[1]->local_id === 1001),
    'Many parameters and a body local retain one linear binding dataset');
Local_Resolution_Test::edit($value_path, $original);
$removed = Local_Resolution_Test::project($baseline);
$removed_names = Phases::run_symbols($removed, $edited_names, new Update_Context(), $baseline->types->catalog);
Local_Resolution_Test::check(($removed_names->for_symbol($id)->parameter_count === 0) && ($removed_names->for_symbol($id)->locals === []),
    'Removing parameters retires their binding rows through the common replacement boundary');
$repair = $session->compile($path);
Local_Resolution_Test::check(($repair->llvm->ir_by_file() === $baseline->llvm->ir_by_file()) && (serialize($baseline) === $before),
    'Failure followed by repair restores the existing executable subset');
echo "parameter binding ok: shared local table, root scope, reads/writes, shadowing, duplicates, ordinal lookup, fixed workers, reuse, edits, exports and explicit next-stage boundary\n";
