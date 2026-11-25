<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/local_resolution_support.php';

use resolve_symbols\Symbol_Resolver as Resolver;
use resolve_symbols\Resolution_Set;
use resolve_symbols\Symbol_Resolution;
use resolve_symbols\local_access;
use collect_symbols\symbol_kind;
use compile\Phases;
use compile\Update_Context;

$path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$baseline = $session->compile($path);
$before = serialize($baseline);
$root = $baseline->inputs->manifest->directory;
$main = $root . '/src/main.phs';
$answer = $root . '/src/answer.phs';
$value = $root . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_answer = file_get_contents($answer);
$original_value = file_get_contents($value);
$source = '$x int = answer(); { $y int32 = $x; $x = $y; { $x uint32 = value(); $x = $x; } $y = $x; } { $x int = 8; $x; } return $x;';
Local_Resolution_Test::edit($main, $source);
Local_Resolution_Test::edit($answer, 'function answer(): int { $x int = 42; return $x; }');
$symbols = Local_Resolution_Test::project($baseline);
$main_id = $symbols->entry_symbol_id($baseline->inputs->sources->find_file_id($main));
$answer_id = $symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$value_id = $symbols->find_symbol('value', '', symbol_kind::function_symbol);
$fixed = serialize([$symbols, $baseline]);
$tasks = \Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $baseline->resolutions, false, $baseline->types->catalog);
Local_Resolution_Test::check(count($tasks) === 3, 'Only callables in the two edited files are selected');
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \resolve_symbols\Resolution_Worker($symbols, $task, $baseline->types->catalog))->run();
}
$names = (new \resolve_symbols\Resolution_Join($baseline->resolutions, $symbols, $tasks, $baseline->types->catalog))->join($results);
Local_Resolution_Test::check(serialize([$symbols, $baseline]) === $fixed, 'Workers and coordinator preserve all fixed inputs');
Local_Resolution_Test::check($names->for_symbol($value_id) === $baseline->resolutions->for_symbol($value_id), 'Untouched callable result is shared');
$r = $names->for_symbol($main_id);
Local_Resolution_Test::check(array_map(static fn($s) => $s->parent_scope_id, $r->scopes) === [0, 1, 2, 1], 'Scope table describes lexical nesting, including siblings');
Local_Resolution_Test::check(array_map(static fn($l) => $l->scope_id, $r->locals) === [1, 2, 3, 4], 'Every declaration belongs to one scope');
Local_Resolution_Test::check(array_map(static fn($u) => $u->local_id, $r->local_bindings) === [1, 1, 2, 3, 3, 2, 1, 4, 1],
    'Nearest lookup, outer assignment and restored shadowed bindings use the correct identities');
Local_Resolution_Test::check(array_map(static fn($u) => $u->access->name, $r->local_bindings) === ['read', 'write', 'read', 'write', 'read', 'write', 'read', 'read', 'read'],
    'Assignment destinations are writes; initializer, RHS and returned references are reads');
Local_Resolution_Test::check(array_map(static fn($c) => $c->target_symbol_id, $r->bindings) === [$answer_id, $value_id], 'Initializers use the existing cross-file call lookup');
Local_Resolution_Test::check(count($symbols->records()) === count($baseline->symbols->current->records()), 'No locals leak into the project declaration index');
Local_Resolution_Test::check((count($names->for_symbol($answer_id)->locals) === 1)
    && ($names->for_symbol($answer_id)->local_bindings[0]->local_id === 1), 'Local IDs belong to one callable snapshot');
foreach ($r->locals as $row => $local) {
    Local_Resolution_Test::check(($r->local_for_declaration($local->declaration_node_id) === ($row + 1))
        && ($r->local_for($row + 1) === $local) && ($r->scope_for($local->scope_id) === $r->scopes[$local->scope_id - 1]), 'Owned accessors return shared records');
}
foreach ($r->local_bindings as $use) {
    Local_Resolution_Test::check($r->binding_for($use->use_node_id) === $use, 'Use lookup returns the original binding');
}
foreach ($r->scopes as $row => $scope) {
    Local_Resolution_Test::check($r->scope_for_block($scope->block_node_id) === ($row + 1), 'Block lookup uses the owned scope index');
}
Local_Resolution_Test::rejects(static fn() => $r->local_for(0), 'Missing resolved local');
Local_Resolution_Test::rejects(static fn() => $r->scope_for(0), 'Missing resolved scope');
Local_Resolution_Test::rejects(static fn() => $r->scope_for_block(0), 'Missing resolved block scope');
Local_Resolution_Test::rejects(static fn() => $r->binding_for(0), 'Missing local binding');
Local_Resolution_Test::rejects(static fn() => $r->local_for_declaration(0), 'Missing resolved local declaration');
$export = $names->to_json();
$rows = json_decode($export, true, 512, JSON_THROW_ON_ERROR);
$entry = array_values(array_filter($rows, static fn($row) => $row['symbol_id'] === $main_id))[0];
Local_Resolution_Test::check(($entry['scopes'][2]['parent_scope_id'] === 2) && ($entry['local_bindings'][1]['access'] === 'write')
    && ($entry['locals'][3]['scope_id'] === 4) && ($names->to_json() === $export), 'Debug export reports actual stable stage facts');
Local_Resolution_Test::check(\Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $names, false, $baseline->types->catalog) === [], 'Unchanged AST and project lookup dependencies select zero work');
$warm = Phases::run_symbols($symbols, $names, new Update_Context(), $baseline->types->catalog);
Local_Resolution_Test::check($warm->for_symbol($main_id) === $r, 'Warm phase shares locals, scopes and bindings together');
$full = new Update_Context();
$full->full_rebuild = true;
$rebuilt = Phases::run_symbols($symbols, $names, $full, $baseline->types->catalog);
Local_Resolution_Test::check(($rebuilt->to_json() === $export) && ($rebuilt->for_symbol($main_id) !== $r), 'Full selection uses the same algorithm and has equivalent results');
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($baseline->resolutions, $symbols, $tasks, $baseline->types->catalog))->join([]), 'Incomplete');
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, $tasks, $baseline->types->catalog))->join([...$results, $results[0]]), 'duplicate');

// Several callables can share one file AST; the scope root must still match its owner.
$answer_entry = $symbols->entry_symbol_id($baseline->inputs->sources->find_file_id($answer));
$function_names = $names->for_symbol($answer_id);
$wrong_root = new Symbol_Resolution($answer_entry, $function_names->syntax, [], $function_names->scopes, $function_names->locals, $function_names->local_bindings);
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, [$symbols->symbol_by_id($answer_entry)], $baseline->types->catalog))->join([$wrong_root]), 'stale');
$missing_root = new Symbol_Resolution($answer_entry, $function_names->syntax);
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, [$symbols->symbol_by_id($answer_entry)], $baseline->types->catalog))->join([$missing_root]), 'stale');

// Result validation covers local ID domains and indexed record integrity.
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($main_id, $r->syntax, [],
        [new \resolve_symbols\lexical_scope($r->scopes[0]->block_node_id, 1)]), 'Invalid resolved scope');
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($main_id, $r->syntax, [], $r->scopes,
        [$r->locals[0], $r->locals[0]]), 'duplicate resolved local');
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($main_id, $r->syntax, [], $r->scopes,
        [new \resolve_symbols\local_record($r->locals[0]->declaration_node_id, 99)]), 'Invalid');
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($main_id, $r->syntax, [], $r->scopes, $r->locals,
        [new \resolve_symbols\local_binding($r->local_bindings[0]->use_node_id, 99, local_access::read)]), 'Invalid');
Local_Resolution_Test::rejects(static fn() => new Symbol_Resolution($main_id, $r->syntax, [], $r->scopes, $r->locals,
        [$r->local_bindings[0], $r->local_bindings[0]]), 'duplicate local binding');

// Unknown annotation names are diagnosed by the shared declaration binder.
Local_Resolution_Test::edit($main, '$x Missing = 1; return 0;');
Local_Resolution_Test::rejects(static fn() => $session->compile($path), "Unknown or unsupported local type 'Missing'");
foreach ([
        ['return $missing;', '$missing', 'Unknown local'],
        ['$x = 42;', '$x', 'Unknown local'],
        ['$x; $x int = 42;', '$x;', 'Unknown local'],
        ['{ $hidden int = 42; } return $hidden;', '$hidden;', 'Unknown local'],
        ['$x int = 1; $x int = 2;', '$x int = 2', 'Duplicate local'],
        ['$x int = $x;', '$x;', 'cannot read itself'],
        ['$x int = 1; { $x int = $x; }', '$x;', 'cannot read itself'],
        ['$x int = absent();', 'absent', "Unknown function 'absent'"],
        ['$x int = 1; $x = absent();', 'absent', "Unknown function 'absent'"],
        ['$X int = 1; return $x;', '$x;', 'Unknown local'],
    ] as [$text, $anchor, $message]) {
    Local_Resolution_Test::edit($main, $text);
    $error = Local_Resolution_Test::rejects(static fn() => $session->compile($path), $message);
    Local_Resolution_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main)
        && ($error->start === strpos($text, $anchor)), 'Local diagnostic anchors the failing name');
    Local_Resolution_Test::check(($session->observed?->resolutions === $baseline->resolutions) && (serialize($baseline) === $before), 'Failed updates preserve the accepted session');
}

// Another callable cannot capture an entry local, including in the same file.
Local_Resolution_Test::edit($main, '$x int = 1; function leak(): int { return $x; } return 42;');
Local_Resolution_Test::rejects(static fn() => $session->compile($path), 'Unknown local');

// An AST replacement discards its local identities as a unit; stale results cannot join.
Local_Resolution_Test::edit($main, str_replace('$y', '$renamed', $source));
$edited = Local_Resolution_Test::project($baseline);
Local_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $edited, [$edited->symbol_by_id($main_id)], $baseline->types->catalog))->join([$r]), 'stale');
$edited_names = Phases::run_symbols($edited, $names, new Update_Context(), $baseline->types->catalog);
Local_Resolution_Test::check(($edited_names->for_symbol($main_id) !== $r) && ($r->to_array() === $entry), 'Edits replace the complete result without altering the previous snapshot');

// Removing a call target invalidates lookup inside a local initializer as usual.
Local_Resolution_Test::edit($value, 'function renamed(): int { return 42; }');
$removed = Local_Resolution_Test::project($baseline);
Local_Resolution_Test::rejects(static fn() => Phases::run_symbols($removed, $names, new Update_Context(), $baseline->types->catalog), "Unknown function 'value'");
Local_Resolution_Test::edit($value, $original_value);
Local_Resolution_Test::edit($main, '$return int = 42; return $return;');
$repaired = Local_Resolution_Test::project($baseline);
$repaired_names = Phases::run_symbols($repaired, $names, new Update_Context(), $baseline->types->catalog);
Local_Resolution_Test::check(count($repaired_names->for_symbol($main_id)->locals) === 1, 'Failure followed by repair uses the same resolution path; keywords remain valid variable names');

// Deep blocks and many names exercise linear storage and explicit traversal.
$text = '$root int = 42;';
for ($i = 0; $i < 1000; ++$i) {
    $text .= '$v' . $i . ' int = $root;';
}
$text .= str_repeat('{', 128) . '$root = $v999;' . str_repeat('}', 128) . 'return $root;';
Local_Resolution_Test::edit($main, $text);
$large = Local_Resolution_Test::project($baseline);
$large_names = Phases::run_symbols($large, new Resolution_Set(), new Update_Context(), $baseline->types->catalog);
$large_entry = $large_names->for_symbol($main_id);
Local_Resolution_Test::check((count($large_entry->locals) === 1001) && (count($large_entry->scopes) === 129)
    && ($large_entry->local_bindings[1001]->local_id === 1001), 'Large local tables and deep scope lookup stay independent of call stack traversal');
Local_Resolution_Test::edit($main, $original_main);
Local_Resolution_Test::edit($answer, $original_answer);
Local_Resolution_Test::check($session->compile($path)->llvm->ir_by_file() === $baseline->llvm->ir_by_file(), 'Original executable subset still compiles after all failed and repaired requests');
echo "local resolution ok: lexical scopes, declaration identities, read/write bindings, shadowing, diagnostics, fixed workers, joins, reuse, replacement, exports and repair\n";
