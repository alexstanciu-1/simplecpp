<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Owned_Result_Test
{
    /** Capture lifecycle events independently of compiler debug representations. */
    public static function run(string $program): array
    {
        $process = proc_open([$program], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start owned-result proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }
}

$root = getcwd() . '/owned-results';
Files::directory($root . '/definitions');
Files::directory($root . '/src');
Files::write($root . '/observer.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdio>
#include <cstdlib>
#include <utility>
namespace proof {
inline int live = 0;
struct item {
    const item* self;
    std::int64_t value;
    item() : self(this), value(7) { ++live; std::puts("C"); }
    item(const item& other) : self(this), value(other.read()) { ++live; std::puts("K"); }
    item(item&& other) : self(this), value(other.read()) { ++live; other.value = 0; std::puts("M"); }
    ~item() { read(); --live; std::puts("D"); }
    std::int64_t read() const { if (self != this) std::abort(); return value; }
};
struct copy_only {
    item value;
    copy_only() = default;
    copy_only(const copy_only&) = default; // suppress implicit move: T&& selects copying
    std::int64_t read() const { return value.read(); }
};
inline std::int64_t finished() { if (live) std::abort(); return 0; }
}
CPP);
$types = [['id' => 'int', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
    'language_type' => ['name' => 'int', 'namespace' => '']],
    ['id' => 'void', 'kind' => 'void', 'cpp_name' => 'void', 'header' => 'cstddef',
        'language_type' => ['name' => 'void', 'namespace' => '']]];
$operations = [];
foreach (['item', 'copy_only'] as $type)
{
    $types[] = ['id' => $type, 'kind' => 'runtime_value', 'cpp_name' => 'proof::' . $type, 'header' => 'observer.hpp',
        'storage' => 'inline', 'struct_field' => true,
        'language_type' => ['name' => $type, 'namespace' => ''],
        'lifecycle' => ['default_construct' => $type . '.default', 'copy_construct' => $type . '.copy',
            'move_construct' => $type . '.move', 'destroy' => $type . '.destroy']];
    foreach (['default' => 'construct', 'copy' => 'copy_construct', 'move' => 'move_construct', 'destroy' => 'destroy'] as $name => $kind) {
        $operation = ['id' => $type . '.' . $name, 'kind' => $kind, 'type' => $type, 'error_policy' => 'terminate'];
        if ($kind === 'construct') {
            $operation['parameters'] = [];
        }
        $operations[] = $operation;
    }
    $operations[] = ['id' => $type . '.read', 'kind' => 'const_method', 'type' => $type, 'member' => 'read',
        'result_type' => 'int', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
        'expose_as' => ['name' => 'read_' . $type, 'namespace' => '']];
}
$operations[] = ['id' => 'finished', 'kind' => 'free_function', 'cpp_name' => 'proof::finished', 'header' => 'observer.hpp',
    'parameters' => [], 'result_type' => 'int', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'finished', 'namespace' => '']];
Files::write_json($root . '/definitions/observed.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['include_directories'] = [$root];
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$manifest = $root . '/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/src/main.phs', 'return exercise() + finished();');
Files::write($root . '/src/types.phs', <<<'PHS'
struct plain { public int32 $value; }
struct generic_record {
    public int32 $value;
    public function __copy_construct(const generic_record &$source): void { $this->value = $source->value; }
}
struct nested { public item $first; public copy_only $last; }
struct automatic { public nested $part; public function __construct(): void {} }
struct suppressed { public item $field; public function __destruct(): void {} }
PHS);
$source = <<<'PHS'
function plain_make($value int32): plain { $p plain; $p->value = $value; return $p; }
function fresh(): item { return new item(); }
function forward(): item { return fresh(); }
function named(): item { $x item; return $x; }
function fallback(): copy_only { $x copy_only; return $x; }
function composite(): automatic { $x automatic; return $x; }
function suppressed_make(): suppressed { $x suppressed; return $x; }
function copied(const item &$x): item { return $x; }
function consume(const item &$input): item { return new item(); }
function early($flag int): item
{
    $local item;
    if ($flag) { return consume(fresh()); }
    return new item();
}

template<typename T>
function generic_copy(const T &$input): T { $local T = $input; return $local; }

const VALUE: int32 = 31;
const SEVEN: int32 = 7;
function exercise(): int
{
    $p plain = plain_make(VALUE);
    if ($p->value < VALUE) { return 1; }
    { $x item = forward(); if (read_item($x) < 7) { return 2; } }
    { $x item = named(); if (read_item($x) < 7) { return 3; } }
    { $x copy_only = fallback(); if (read_copy_only($x) < 7) { return 4; } }
    { $x automatic = composite(); if (read_item($x->part->first) < 7) { return 5; } }
    { $x suppressed = suppressed_make(); if (read_item($x->field) < 7) { return 6; } }
    { $a item; $b item = copied($a); if (read_item($a) < 7) { return 7; } }
    { $a generic_record; $a->value = SEVEN; $b generic_record = generic_copy<generic_record>($a); if ($b->value < SEVEN) { return 8; } }
    forward();
    { $x item = early(1); }
    { $x item = early(0); }
    return 0;
}
PHS;
$path = $root . '/src/body.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
$out = Owned_Result_Test::run($root . '/program');
Check::check(($out[0] === 0) && ($out[2] === ''), 'Owned results execute: ' . json_encode($out));
$traces = [
    'forward' => "C\nD\n", 'named' => "C\nM\nD\nD\n", 'fallback' => "C\nK\nD\nD\n",
    'composite' => "C\nC\nM\nK\nD\nD\nD\nD\n", 'suppressed' => "C\nK\nD\nD\n",
    'copy' => "C\nK\nD\nD\n", 'discard' => "C\nD\n",
    'early' => "C\nC\nC\nD\nD\nD\nC\nC\nD\nD\n",
];
$expected = implode('', $traces);
Check::check($out[1] === $expected, 'Return construction/cleanup order: ' . $out[1]);
// O1 consumes the same emitted modules and prepared implementations; no compiler mode is added.
$tools = new \runtime_preparation\Clang_Toolchain($config, $root);
$modules = [];
foreach ($first->llvm->modules as $index => $module) {
    $module_path = $root . '/module-' . $index . '.ll';
    Files::write($module_path, $module->ir);
    $modules[] = $module_path;
}
$runtime = $first->backend->runtime;
$tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', ...$modules,
    ...$runtime->modules_for(\load_runtime\runtime_module_kind::ordinary), '-o', $root . '/optimized']);
Check::check(Owned_Result_Test::run($root . '/optimized') === $out, 'External O1 preserves the owned-result lifecycle trace');
echo "owned results: ok\n";

// Workers share fixed inputs; the join accepts reordered complete outputs and rejects gaps.
$before = serialize($first);
$tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($tasks));
$join = new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $first->types, new \check_bodies\Body_Set(), $tasks);
Check::check($join->join($results)->to_json() === $first->bodies->to_json(), 'Owned-result workers join deterministically');
Check::rejects(static fn() => $join->join(array_slice($results, 1)), 'Incomplete');
Check::check(serialize($first) === $before, 'Owned-result workers preserve their fixed inputs');

// The baseline grants a copy, despite the eventual source type's concrete construction rules.
foreach ($first->bodies->bodies() as $body) {
    if ($body->owner->name === 'generic_copy') {
        $returns = array_filter($body->statements, static fn($row) => $row->kind === \check_bodies\statement_kind::return_statement);
        Check::check(array_values($returns)[0]->return === \check_bodies\return_kind::copy_construct,
            'Generic return keeps its definition-level copy permission');
    }
}

Check::edit($path, str_replace('return fresh();', '$local item; return $local;', $source));
$second = $session->compile($manifest, $root . '/program');
$traces['forward'] = $traces['discard'] = "C\nM\nD\nD\n";
Check::check(Owned_Result_Test::run($root . '/program') === [0, implode('', $traces), ''],
    'A body edit changes construction without changing its owned signature');
Check::check(($second->backend === $first->backend) && (serialize($first) === $before),
    'Owned-result update reuses prepared storage/ABI and preserves the previous snapshot');

// Missing preparation is not permission to silently copy an expiring source.
$missing = $types;
foreach ($missing as &$type) {
    if ($type['id'] === 'item') {
        unset($type['lifecycle']['move_construct']);
    }
}
unset($type);
Files::write_json($root . '/definitions/observed.json', ['schema_version' => 1, 'types' => $missing, 'operations' => $operations]);
$config['output_directory'] = $root . '/missing-runtime';
Files::write_json($root . '/missing.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/missing.json');
Files::write($root . '/missing.phs', 'function named(): item { $x item; return $x; } return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))->compile($root . '/missing.phs'),
    'expiring return source is unavailable');
echo "owned result workers, generic permissions, increment and missing-support diagnostic: ok\n";
