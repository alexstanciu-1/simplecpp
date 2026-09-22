<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/source_family_support.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use prepare_backend\Export_Verification;
use analyze_lifetimes\Export_Join;
use analyze_lifetimes\Export_Worker;

$root = getcwd() . '/source-family-custom';
[$session, $scope, $config] = Source_Family_Test::setup($root);
$source = <<<'PHS'
struct custom {
    public managed $resource;
    public int $number;
    public function __construct(): void { $this->number = 7; }
    public function __copy_construct(const custom &$source): void {
        $this->resource = $source->resource;
        $this->number = $source->number;
        note(1);
    }
    public function __copy_assign(const custom &$source): void {
        $this->resource = $source->resource;
        $this->number = $source->number;
        note(10);
    }
    public function __destruct(): void { note(100); }
}
struct outer { public custom $child; }
PHS;
Check::edit($root . '/project/types.phs', $source);
Check::edit($root . '/project/body.phs', <<<'PHS'
function exercise(): int {
    $a outer = new outer();
    $items vector<outer> = new vector<outer>();
    $zero size_count = $items->length();
    $items->append($a);
    $b outer = $items->at($zero);
    $b = $a;
    return $b->child->number;
}
PHS);
// Lifecycle events are observable separately from the returned field and native allocation balance.
Check::edit($root . '/project/main.phs', '$value int = exercise(); if (balanced() < 1) { return 99; } return $value + notes();');
$first = $session->compile($root . '/project/project.json', $root . '/program');
// append copy-in (temporary + stored element), copy-out (temporary + result), assignment,
// then five custom destructors: two bridge temporaries and three source/container objects.
$expected = (7 + 4 + 10 + 500) % 256;
Check::check(Source_Export_Test::run($root . '/program') === $expected, 'Nested custom lifecycle executes through native payload forwarding');
$artifacts = [];
foreach ($first->backend->runtime->packages() as $package) {
    if ($package->project !== null) {
        $path = $package->module_for(\load_runtime\runtime_module_kind::ordinary);
        $artifacts[$path] = [hash_file('sha256', $path), filemtime($path)];
    }
}
Check::check(count($artifacts) === 1, 'One native specialization contains the nested custom source record');

// Verification workers accept only the fixed selection, including nested custom bodies.
$operations = array_map(static fn($v) => $v->task->operation, $first->backend->source_verifications);
$tasks = Export_Verification::capture($operations, $first->types->types, $first->bodies, $first->lifetimes);
$before = serialize([$tasks, $first]);
$outputs = array_map(Export_Worker::prepare(...), array_reverse($tasks, true));
$join = new Export_Join($tasks, $tasks, []);
$join->join($outputs);
Check::check(serialize([$tasks, $first]) === $before, 'Private checks and join preserve fixed inputs');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...array_values($outputs), array_values($outputs)[0]]), 'duplicate');
$task = clone array_values($tasks)[0];
Check::rejects(static fn() => $join->join([Export_Worker::prepare($task)]), 'stale');
Check::rejects(static fn() => \prepare_backend\Project_Exports::prepare($first->types->types, $first->backend->runtime), 'body analysis');

// One custom body edit preserves the native contract while replacing its analysis and executable body.
Check::edit($root . '/project/types.phs', str_replace('note(1);', 'note(2);', $source));
$next = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(!$next->inputs->context->full_rebuild, 'Custom body replacement stays incremental');
Check::check(Source_Export_Test::run($root . '/program') === (($expected + 4) % 256), 'Edited custom copy body runs through the reused native specialization');
Check::check(serialize([$tasks, $first]) === $before, 'Custom body replacement preserves previous snapshots');
clearstatcache();
foreach ($artifacts as $path => $facts) {
    Check::check([hash_file('sha256', $path), filemtime($path)] === $facts, 'Custom body edit reuses native bytes and timestamps');
}
Check::rejects(static fn() => Export_Verification::capture($operations, $next->types->types, $next->bodies, $first->lifetimes), 'stale');
$current = Export_Verification::capture($operations, $next->types->types, $next->bodies, $next->lifetimes);
Check::rejects(static fn() => (new Export_Join($current, [], $first->backend->source_verifications))->join([]), 'stale');

// The same source implementations remain usable through ordinary O1 and ThinLTO native modules.
$paths = [];
foreach ($next->llvm->modules as $i => $module) {
    $path = $root . '/source-' . $i . '.ll';
    Files::write($path, $module->ir);
    $paths[] = $path;
}
$tools = new \runtime_preparation\Clang_Toolchain($config, $root);
$runtime = $next->backend->runtime;
foreach ([\load_runtime\runtime_module_kind::ordinary, \load_runtime\runtime_module_kind::thin_lto] as $kind)
{
    $flags = $kind === \load_runtime\runtime_module_kind::ordinary ? [] : ['-flto=thin', '--ld-path=' . Files::executable('/', 'ld.lld-18')];
    $program = $root . '/optimized-' . $kind->value;
    $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', ...$flags, ...$paths, ...$runtime->modules_for($kind), '-o', $program]);
    Check::check(Source_Export_Test::run($program) === (($expected + 4) % 256), 'Custom source exports execute at O1 and ThinLTO');
}
echo "custom source exports ok: nested lifecycle, checked evidence, rejected joins, one body increment, O0/O1/ThinLTO\n";
