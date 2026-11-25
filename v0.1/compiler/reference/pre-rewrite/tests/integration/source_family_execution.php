<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/source_family_support.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

$root = getcwd() . '/source-family';
[$session, $scope, $config] = Source_Family_Test::setup($root);
Check::edit($root . '/project/types.phs', str_replace('int32', 'int', Files::read($root . '/project/types.phs')));
Check::edit($root . '/project/main.phs', '$result int = exercise(); if (balanced() < 1) { return 99; } return $result;');
$body = <<<'PHS'
function exercise(): int {
    $a first = new first();
    $a->number = 17;
    $values vector<first> = new vector<first>();
    $zero size_count = $values->length();
    $values->append($a);
    $copied vector<first> = $values;
    $copied = $values;
    $b first = $copied->at($zero);
    $record nested = new nested();
    $record->child->number = 5;
    $record->tail = 3;
    $records vector<nested> = new vector<nested>();
    $records->append($record);
    $d nested = $records->at($zero);
    $groups vector<vector<first>> = new vector<vector<first>>();
    $groups->append($values);
    $retrieved vector<first> = $groups->at($zero);
    $e first = $retrieved->at($zero);
    $plain plain = new plain();
    $plain->number = 2;
    $plains vector<plain> = new vector<plain>();
    $plains->append($plain);
    $p plain = $plains->at($zero);
    return $b->number + $d->child->number + $d->tail + $e->number + $p->number;
}
PHS;
Check::edit($root . '/project/body.phs', $body);
$compiled = $session->compile($root . '/project/project.json', $root . '/native');
Check::check(Source_Export_Test::run($root . '/native') === 44, 'Source record copies execute through the native family');

// Native modules must retain project scope, exact source definitions and complete final imports.
$native_files = [];
$project_packages = [];
$source_imports = [];
$shared_imports = 0;
foreach ($compiled->backend->runtime->packages() as $package)
{
    if ($package->project === null) {
        continue;
    }
    $project_packages[] = $package;
    Check::check(str_starts_with($package->directory, $scope->output_root . '/'), 'Source dependencies select project output storage');
    foreach ($package->bindings->sources as $id => $export) {
        Check::check($package->type_for($id)->language_type === $export->task->layout->definition,
            'Source payload import preserves the canonical source definition');
    }
    foreach ($package->source_imports as $symbol => $operation)
    {
        Check::check(($compiled->backend->source_exports[$symbol] ?? null) === $operation,
            'Backend consumes the exact adapter-accepted source operation');
        if (isset($source_imports[$symbol])) {
            Check::check($source_imports[$symbol] === $operation, 'Direct and transitive imports share one operation plan');
            $shared_imports++;
        }
        $source_imports[$symbol] = $operation;
    }
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($package->directory, $package->base_catalog), 'manifest');
    foreach (['runtime.bc', 'runtime.lto.bc', 'runtime.thin.bc', 'manifest.json'] as $file) {
        $path = $package->directory . '/package/' . $file;
        $native_files[$path] = [hash_file('sha256', $path), filemtime($path)];
    }
}
Check::check(count($project_packages) === 4, 'Direct, nested-record, plain-record and nested-family requests remain project modules');
ksort($source_imports);
Check::check(($shared_imports > 0) && ($source_imports === $compiled->backend->source_exports),
    'Final source obligations deduplicate shared imports without losing any package requirements');
Check::rejects(static fn() => \emit_llvm\Source_Export_Emission::closure($compiled->backend, []), 'Missing');
Check::rejects(static fn() => \emit_llvm\Source_Export_Emission::closure($compiled->backend,
    [...$compiled->llvm->modules, ...$compiled->llvm->modules]), 'Duplicate');
$package = $project_packages[0];
$wrong = json_decode($package->project->receipt, true, flags: JSON_THROW_ON_ERROR);
$symbol = array_key_first($wrong['required_imports']['runtime.bc']);
$wrong['required_imports']['runtime.bc'][$symbol]['abi']['return_type'] = 'i64';
$wrong_receipt = json_encode($wrong, JSON_THROW_ON_ERROR);
// Retaining normalized imports never bypasses receipt revalidation during a new reservation.
$lease = \load_runtime\Package_Adapter::open($package->directory, $package->base_catalog, $package, $package->bindings, $package->project);
Check::check($lease->package === $package, 'Reopening unchanged artifacts retains their accepted contracts');
$lease->release();
Check::rejects(static fn() => \load_runtime\Package_Adapter::open($package->directory, $package->base_catalog,
    $package, $package->bindings, new \load_runtime\project_binding($wrong_receipt, $package->project->exports)), 'receipt changed');
Check::rejects(static fn() => \load_runtime\Project_Import::validate(
    new \load_runtime\project_binding($wrong_receipt, $package->project->exports), $wrong_receipt,
    ['triple' => $package->target_triple, 'data_layout' => $package->data_layout]), 'Unauthorized');

// A source body edit changes executable behavior without rewriting the prepared native specialization.
$before = serialize([$compiled->types, $compiled->backend, $compiled->llvm]);
Check::edit($root . '/project/body.phs', str_replace('= 17;', '= 19;', $body));
$increment = $session->compile($root . '/project/project.json', $root . '/native');
Check::check(Source_Export_Test::run($root . '/native') === 48, 'One body increment changes execution with balanced managed allocations');
Check::check(serialize([$compiled->types, $compiled->backend, $compiled->llvm]) === $before, 'Previous source/native snapshots remain unchanged');
clearstatcache();
foreach ($native_files as $path => $facts) {
    Check::check([hash_file('sha256', $path), filemtime($path)] === $facts, 'Body increment reuses native artifacts: ' . $path);
}

// Exercise the same source imports and complete implementations under LLVM optimization and ThinLTO.
$modules = [];
foreach ($increment->llvm->modules as $i => $module) {
    $path = $root . '/caller-' . $i . '.ll';
    Files::write($path, $module->ir);
    $modules[] = $path;
}
$runtime = $increment->backend->runtime;
$tools = new \runtime_preparation\Clang_Toolchain($config, $root);
foreach (['ordinary' => \load_runtime\runtime_module_kind::ordinary, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
{
    $flags = $mode === 'ordinary' ? [] : ['-flto=thin', '--ld-path=' . Files::executable('/', 'ld.lld-18')];
    $binary = $root . '/optimized-' . $mode;
    $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', ...$flags, ...$modules,
        ...$runtime->modules_for($kind), '-o', $binary]);
    Check::check(Source_Export_Test::run($binary) === 48, 'Source-native lifecycle execution and balanced cleanup at O1: ' . $mode);
}
echo "source/native family proof ok: common types, source exports, balanced cleanup, one increment, O0/O1/ThinLTO\n";
