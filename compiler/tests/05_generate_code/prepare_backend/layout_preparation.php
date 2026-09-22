<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use prepare_backend as backend;
use type_model as model;
use resolve_types\Record_Definitions;

// Small real target probes isolate batching, dependency ownership and retained reuse.
$types = new model\Type_Store(new model\type_context('layout-proof', 'language', 'target'));
$word = new model\named_type_definition('word', 'proof',
    new model\representation_record(model\representation_kind::integer, new model\integer_representation(32)),
    new model\lifetime_contract(model\copy_kind::value, model\cleanup_kind::none,
        construction: model\construction_kind::zero, assignment: model\assignment_kind::value),
    signed: true, struct_field: true);
$leaf = Record_Definitions::materialize($types, new model\record_declaration('leaf', 'proof',
    [new model\field_declaration('value', $word)], true, model\record_layout_policy::target));
$outer = Record_Definitions::materialize($types, new model\record_declaration('outer', 'proof',
    [new model\field_declaration('items', new model\array_type_definition($types->definition_for_type($leaf), 3)),
        new model\field_declaration('last', $word)], true, model\record_layout_policy::target));
$toolchain = new backend\LLVM_Toolchain();
$config = $toolchain->configuration();
[$command, $launcher] = $toolchain->layout_tools($config);
$input = backend\Layout_Preparation::capture($types, [$outer, $leaf]);
$before = serialize($input);
$tasks = backend\Layout_Preparation::select($input, $config, [], true, $command, $launcher);
Check::check(count($tasks) === 2, 'Only explicit roots select measurements; dependency rows are shared');
Check::check($tasks[0]->input === $tasks[1]->input, 'One dependency view serves the entire fixed batch');

// The coordinator can grow its private canonical store after selection without changing workers.
$later = Record_Definitions::materialize($types, new model\record_declaration('later', 'proof',
    [new model\field_declaration('payload', $types->definition_for_type($outer))], true, model\record_layout_policy::target));
Check::check(!isset($input->dependencies[$later]) && (serialize($input) === $before),
    'Later canonical materialization does not enter selected layout dependencies');
$results = array_map(backend\Layout_Preparation::prepare(...), array_reverse($tasks));
$join = new backend\Layout_Join($input, $config, [], $tasks);
$layouts = $join->join($results);
Check::check(($layouts[$outer]->size === 4 * $layouts[$leaf]->size) && (serialize($input) === $before),
    'Nested array layout uses the fixed view and joins in arbitrary completion order');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
Check::rejects(static fn() => (new backend\Layout_Join($input, clone $config, [], $tasks))->join($results), 'stale layout task');

// Equal numeric IDs or equal-size definitions cannot substitute for accepted provenance.
$foreign = new backend\Layout_Input(new model\type_lineage(), $input->roots, $input->dependencies);
Check::check(count(backend\Layout_Preparation::select($foreign, $config, $layouts, false, $command, $launcher)) === 2,
    'Foreign lineage layouts are selected again despite matching numeric IDs');
Check::rejects(static fn() => (new backend\Layout_Join($foreign, $config, $layouts, []))->join([]), 'stale record layout');
$dependencies = $input->dependencies;
$old = $dependencies[$leaf];
$dependencies[$leaf] = new backend\layout_dependency($leaf, clone $old->definition, $old->fields, $old->children);
$changed = new backend\Layout_Input($input->lineage, $input->roots, $dependencies);
Check::rejects(static fn() => (new backend\Layout_Join($changed, $config, [], $tasks))->join($results), 'stale layout task');
$outer_node = $input->dependencies[$outer];
$array_id = $outer_node->fields[0]->type_id;
$array_node = $input->dependencies[$array_id];
$dependencies[$array_id] = new backend\layout_dependency($array_id, $array_node->definition, $array_node->fields,
    [$leaf => $dependencies[$leaf]]);
$children = $outer_node->children;
$children[$array_id] = $dependencies[$array_id];
$dependencies[$outer] = new backend\layout_dependency($outer, $outer_node->definition, $outer_node->fields, $children);
$changed = new backend\Layout_Input($input->lineage, [$outer], $dependencies);
Check::check(count(backend\Layout_Preparation::select($changed, $config, $layouts, false, $command, $launcher)) === 1,
    'A changed nested dependency invalidates layout even with the same root definition');
Check::check(backend\Layout_Preparation::select($input, $config, $layouts, false, $command, $launcher) === [],
    'Unchanged exact contracts require no new target work');

// One update service spans early subsets and final completeness, including full rebuilds.
$coordinator = new backend\Layout_Coordinator($toolchain, [], true);
$early = $coordinator->prepare($types, [$leaf]);
$complete = $coordinator->prepare($types, array_keys(backend\Layout_Preparation::definitions($types)));
Check::check(($complete[$leaf] === $early[$leaf]) && isset($complete[$later], $complete[$outer]),
    'Final completion reuses the exact early result and prepares remaining roots');
$array = $complete[$outer]->dependency->fields[0]->type_id;
Check::check($complete[$outer]->dependency->children[$array]->children[$leaf] === $early[$leaf]->dependency,
    'Nested accepted layouts share dependency records across early batches');
Check::check($coordinator->prepare($types, [$outer]) === [$outer => $complete[$outer]],
    'Requested membership excludes unrelated retained contributions');
$increment = new backend\Layout_Coordinator($toolchain, $complete, false);
Check::check($increment->prepare(clone $types, array_keys($complete)) === $complete,
    'One incremental layout attempt reuses exact accepted facts through the same algorithm');
$other = new model\Type_Store(new model\type_context('other', 'language', 'target'));
Check::rejects(static fn() => $coordinator->prepare($other, []), 'cannot cross type lineages');
echo "layout preparation ok: fixed dependency views, subset batches, early/final reuse and provenance rejection\n";
