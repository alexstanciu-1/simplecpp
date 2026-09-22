<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';
require_once dirname(__DIR__, 3) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use prepare_backend as backend;
use runtime_preparation\Files;

require_once __DIR__ . '/../../support/source_export_support.php';

$root = getcwd() . '/source-exports';
[$session, $first, $project, $body] = Source_Export_Test::project($root);
$types = $first->types->types;
$roots = array_map($types->find_type(...), ['first', 'second', 'nested', 'plain']);
foreach ($first->types->instances->concrete_types as $definition) {
    $roots[] = $types->find_type($definition->name, $definition->namespace_name);
}
$current = Source_Export_Test::capture($first, $project, $roots);
$selected = backend\Source_Export_Preparation::select($current, [], true);
$before = serialize([$first, $selected]);
$results = array_map(backend\Source_Export_Preparation::prepare(...), array_reverse($selected, true));
$join = new backend\Source_Export_Join($current, [], $selected);
$exports = $join->join($results);
Check::check(serialize([$first, $selected]) === $before, 'Export workers and join preserve fixed inputs and previous snapshots');
Check::check(count($exports) === 8, 'Explicit demands include four ordinary records and four template instances');
[$left, $right, $nested, $plain] = array_slice($roots, 0, 4);
Check::check(($current[$left]->layout->size === $current[$right]->layout->size)
    && ($current[$left]->identity->key !== $current[$right]->identity->key), 'Same-layout records retain distinct nominal exports');
$instance_keys = array_map(static fn($id) => $current[$id]->identity->key, array_slice($roots, 4));
Check::check(count(array_unique($instance_keys)) === 4, 'Ordered tagged type and integer arguments distinguish source specializations');
$nested_key = json_decode($instance_keys[3], true, flags: JSON_THROW_ON_ERROR);
Check::check(is_array($nested_key[3][0][1]) && ($nested_key[3][0][1][0] === 'source'),
    'Nested generic identities embed structured components without repeated string escaping');
foreach ($exports as $id => $export)
{
    Check::check(count($export->operations) === 6, 'All six role states are explicit');
    foreach ([backend\source_export_role::default_construct, backend\source_export_role::copy_construct,
        backend\source_export_role::copy_assign, backend\source_export_role::destroy] as $role)
    {
        $row = $export->require_operation($role);
        Check::check(($row->import->parameters == $row->implementation->parameters)
            && ($row->import->link_name !== $row->implementation->link_name), 'Stable import retains the prepared pointer ABI and distinct internal implementation');
        Check::check(!str_contains($row->import->link_name, 'lifecycle__type__'), 'Export symbols do not encode lineage-local operation names');
    }
    Check::rejects(static fn() => $export->require_operation(backend\source_export_role::move_construct), 'unsupported');
    Check::rejects(static fn() => $export->require_operation(backend\source_export_role::move_assign), 'unsupported');
}
Check::check($exports[$left]->require_operation(backend\source_export_role::copy_construct)->capability->operation
    === $types->definition_for_type($left)->lifetime->copy_constructor, 'Managed export points at the exact already-emitted complete operation');
Check::check($exports[$plain]->require_operation(backend\source_export_role::destroy)->capability->operation->members === [],
    'Cleanup-free destruction has a justified complete empty plan');
Check::check(count($exports[$plain]->require_operation(backend\source_export_role::copy_construct)->capability->operation->members) === 1,
    'Plain copying is composed by the existing field lifecycle owner');
$custom = Source_Export_Test::capture($first, $project, [$types->find_type('enclosing_custom')]);
$custom_operations = [];
foreach ($custom as $task) {
    foreach ($task->capabilities as $capability) {
        if ($capability->operation !== null) {
            $custom_operations[] = $capability->operation;
        }
    }
}
Check::rejects(static fn() => backend\Export_Verification::prepare($custom_operations, $types, null, null, [], true), 'body analysis');
$evidence = backend\Export_Verification::prepare($custom_operations, $types, $first->bodies, $first->lifetimes, [], true);
Check::check(count($evidence) === 4, 'Declared nested custom operations require current analysis before export');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...array_values($results), array_values($results)[0]]), 'duplicate');
$foreign = backend\Source_Export_Preparation::prepare(clone $current[$left]);
Check::rejects(static fn() => (new backend\Source_Export_Join($current, $exports, [$left => $current[$left]]))->join([$foreign]), 'stale');
$rows = $exports[$left]->operations;
$copy = $rows['copy_construct'];
$rows['copy_construct'] = new backend\source_operation_export($copy->capability, $copy->implementation,
    new backend\abi_target($copy->import->link_name, 'ccc', 'i64', $copy->import->parameters));
Check::rejects(static fn() => (new backend\Source_Export_Join($current, $exports, [$left => $current[$left]]))
    ->join([new backend\source_type_export($current[$left], $rows)]), 'ABI association');
Check::check(backend\Source_Export_Preparation::select($current, $exports, false) === [], 'Current exports select no repeated ABI work');
Check::check((new backend\Source_Export_Join([$left => $current[$left]], $exports, []))->join([]) === [$left => $exports[$left]],
    'Removed demands are excluded independently of selection');

// One body-only increment preserves native identity and accepted physical contracts.
$snapshot = serialize($first);
Check::edit($root . '/project/body.phs', str_replace('return 7;', 'return 9;', $body));
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(!$second->inputs->context->full_rebuild && (Source_Export_Test::run($root . '/program') === 9), 'One body increment changes execution');
$next = Source_Export_Test::capture($second, $project, $roots);
Check::check(backend\Source_Export_Preparation::select($next, $exports, false) === [], 'Body-only change reuses export contracts before any worker runs');
Check::check((new backend\Source_Export_Join($next, $exports, []))->join([]) === $exports, 'Retained export records are shared across compatible body replacement');
Check::check(serialize($first) === $snapshot, 'Increment preserves previous compiler snapshot');

// A separate build rebinds exports; portable names survive changed allocation order and physical roots.
Files::directory($root . '/relocated');
foreach (['types', 'body', 'main'] as $name) {
    Files::write($root . '/relocated/' . $name . '.phs', Files::read($root . '/project/' . $name . '.phs'));
}
Files::write($root . '/relocated/a_extra.phs', 'struct unrelated { public uint8 $byte; }');
Files::write_json($root . '/relocated/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$rebuilt = (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))->compile($root . '/relocated/project.json');
$relocated = new \compile\native_project($project->project_key, $root . '/relocated', $root . '/different-output');
$rebuilt_id = $rebuilt->types->types->find_type('first');
$new = Source_Export_Test::capture($rebuilt, $relocated, [$rebuilt_id]);
Check::check($new[$rebuilt_id]->identity->key === $current[$left]->identity->key, 'Project-relative nominal identity survives workspace relocation');
$rebound = backend\Source_Export_Preparation::prepare($new[$rebuilt_id]);
Check::check($rebound->operations['copy_construct']->import->link_name === $exports[$left]->operations['copy_construct']->import->link_name,
    'Compatible rebuild binds the same external symbol to the current complete implementation');
Check::check($rebound->operations['copy_construct']->implementation->link_name !== $exports[$left]->operations['copy_construct']->implementation->link_name,
    'Changed allocation order changes internal linkage without changing portable export linkage');
Check::check(!backend\Source_Export_Preparation::current($exports[$left], $new[$rebuilt_id]), 'Foreign layout/lineage cannot reuse an old export association');
Check::rejects(static fn() => backend\Source_Export_Preparation::capture($project, Source_Export_Test::identities($first, $project),
    $rebuilt->types->types, [$rebuilt_id], $rebuilt->backend->layouts, $rebuilt->backend->configuration), 'identity lineage');
Check::rejects(static fn() => backend\Source_Export_Preparation::capture($project, Source_Export_Test::identities($first, $project),
    $types, [$left], $first->backend->layouts, clone $first->backend->configuration), 'layout/lineage/target');
$other_project = new \compile\native_project('different-project', $project->source_root, $project->output_root);
$other_identity = Source_Export_Test::capture($first, $other_project, [$left])[$left]->identity;
Check::check($other_identity->key !== $current[$left]->identity->key, 'Project namespaces distinguish equal source declarations');
Check::rejects(static fn() => backend\Source_Export_Preparation::capture($other_project, Source_Export_Test::identities($first, $project),
    $types, [$left], $first->backend->layouts, $first->backend->configuration), 'identity lineage');
$outside = new \compile\native_project('proof', $root . '/other-project', $root . '/output');
Check::rejects(static fn() => Source_Export_Test::capture($first, $outside, [$left]), 'project-relative');
Check::check(!is_dir($project->output_root), 'Contract preparation does not publish native modules prematurely');
echo "source exports ok: exact identities, six-role contracts, private ABI joins, rebuild binding and body reuse\n";
