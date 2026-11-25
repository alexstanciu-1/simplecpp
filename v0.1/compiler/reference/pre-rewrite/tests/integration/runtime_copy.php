<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Copy_Runtime_Test
{
    /** Capture native exit status and exact traces for an independent C++ comparison. */
    public static function execute(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start copy probe');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

    /** Refresh artifact integrity so negative cases exercise semantic validation. */
    public static function metadata(string $directory, array $metadata): void
    {
        $manifest_path = $directory . '/package/manifest.json';
        $manifest = Files::json($manifest_path);
        $metadata_path = $directory . '/package/' . $manifest['metadata'];
        Files::write_json($metadata_path, $metadata);
        $manifest['artifacts'][$manifest['metadata']] = hash_file('sha256', $metadata_path);
        Files::write_json($manifest_path, $manifest);
        $pointer = Files::json($directory . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $manifest_path);
        Files::write_json($directory . '/current.json', $pointer);
    }
}

$root = getcwd() . '/copy-runtime';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
Files::write($root . '/objects.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdio>
#include <cstdlib>
namespace sample {
inline int live = 0, serial = 0;
class alignas(64) owned {
    const owned *identity;
    std::int64_t *value;
    int id;
    void check() const { if (identity != this) std::abort(); }
public:
    explicit owned(std::int64_t v) : identity(this), value(new std::int64_t(v)), id(++serial) {
        ++live; std::printf("C:%d:%lld\n", id, (long long)*value);
    }
    owned(const owned& source) : identity(this), value(new std::int64_t(*source.value)), id(++serial) {
        source.check(); ++live; std::printf("K:%d:%d\n", id, source.id);
    }
    ~owned() { check(); std::printf("D:%d\n", id); delete value; --live; }
    std::int64_t read() const { check(); std::printf("R:%d:%lld\n", id, (long long)*value); return *value; }
    std::int64_t advance() const { check(); ++*value; return read(); }
};
struct plain {
    std::int64_t value;
    explicit plain(std::int64_t v) : value(v) { std::printf("P:%lld\n", (long long)value); }
    plain(const plain& source) : value(source.value) { std::printf("Q:%lld\n", (long long)value); }
    std::int64_t read() const { std::printf("S:%lld\n", (long long)value); return value; }
};
inline std::int64_t live_count() { if (live != 0) std::abort(); return live; }
}
CPP);
$types = [['id' => 'integer', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
    'language_type' => ['name' => 'int', 'namespace' => '']]];
$operations = [];
foreach (['owned', 'plain'] as $name)
{
    $lifecycle = ['construct' => $name . '.make', 'copy_construct' => $name . '.copy'];
    $lifecycle += $name === 'owned' ? ['destroy' => $name . '.destroy'] : ['cleanup' => 'none'];
    $types[] = ['id' => $name, 'kind' => 'runtime_value', 'cpp_name' => 'sample::' . $name, 'header' => 'objects.hpp',
        'storage' => 'inline', 'lifecycle' => $lifecycle, 'language_type' => ['name' => $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.make', 'kind' => 'construct', 'type' => $name, 'parameters' => ['integer'],
        'error_policy' => 'terminate', 'expose_as' => ['name' => 'make_' . $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.copy', 'kind' => 'copy_construct', 'type' => $name, 'error_policy' => 'terminate'];
    foreach ($name === 'owned' ? ['read', 'advance'] : ['read'] as $member) {
        $operations[] = ['id' => $name . '.' . $member, 'kind' => 'const_method', 'type' => $name, 'member' => $member,
            'result_type' => 'integer', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
            'expose_as' => ['name' => $member . '_' . $name, 'namespace' => '']];
    }
}
$operations[] = ['id' => 'owned.destroy', 'kind' => 'destroy', 'type' => 'owned', 'error_policy' => 'terminate'];
$operations[] = ['id' => 'live', 'kind' => 'free_function', 'header' => 'objects.hpp', 'cpp_name' => 'sample::live_count',
    'parameters' => [], 'result_type' => 'integer', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'live_count', 'namespace' => '']];
Files::write_json($root . '/definitions/objects.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['provider'] = 'copy_fixture';
$config['include_directories'] = [$root];
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/generated';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$package = Files::json($config['output_directory'] . '/package/manifest.json');
$metadata = Files::json($config['output_directory'] . '/package/' . $package['metadata']);

// One managed file changes; the caller and unrelated managed function remain separate.
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'return managed(1) + managed(0) + unrelated() + live_count();');
Files::write($root . '/project/src/unrelated.phs', 'function unrelated(): int { $a owned = make_owned(3); $b owned = $a; return read_owned($b); }');
$managed_path = $root . '/project/src/managed.phs';
$source = <<<'PHS'
/** Copies retain independent resources across nested scopes, loop iterations and both exits. */
function managed($flag int): int
{
    $a owned = make_owned(7);
    $b owned = $a;
    advance_owned($b);
    $total int = read_owned($a) + read_owned($b);
    {
        $c owned = $b;
        $total = $total + read_owned($c);
    }
    $p plain = make_plain(2);
    $q plain = $p;
    $total = $total + read_plain($p) + read_plain($q);
    $again int = 1;
    while ($again) {
        $loop owned = $a;
        $total = $total + read_owned($loop);
        $again = 0;
    }
    if ($flag) {
        $early owned = $b;
        return $total + read_owned($early);
    }
    return $total + read_owned($a);
}
PHS;
Files::write($managed_path, $source);
Files::write($root . '/oracle.cpp', <<<'CPP'
#include "objects.hpp"
using sample::owned; using sample::plain;
static std::int64_t managed(std::int64_t flag) {
    owned a(7);
    owned b = a;
    b.advance();
    std::int64_t total = a.read() + b.read();
    { owned c = b; total = total + c.read(); }
    plain p(2);
    plain q = p;
    total = total + p.read() + q.read();
    std::int64_t again = 1;
    while (again) { owned loop = a; total = total + loop.read(); again = 0; }
    if (flag) {
        owned early = b;
#if COPY_EDIT
        owned extra = early;
        return total + extra.read();
#else
        return total + early.read();
#endif
    }
    return total + a.read();
}
static std::int64_t unrelated() { owned a(3); owned b = a; return b.read(); }
int main() { return managed(1) + managed(0) + unrelated() + sample::live_count(); }
CPP);
$expected = [];
foreach ([0, 1] as $edit) {
    [$status, , $error] = Copy_Runtime_Test::execute([$package['link_driver']['executable'], '--driver-mode=g++',
        '--target=' . $package['target']['triple'], '-std=c++23', '-DCOPY_EDIT=' . $edit, $root . '/oracle.cpp', '-o', $root . '/oracle']);
    Check::check($status === 0, 'Compile native copy oracle: ' . $error);
    $expected[] = Copy_Runtime_Test::execute([$root . '/oracle']);
}
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$output = $root . '/program';
$first = $session->compile($manifest, $output);
$actual = Copy_Runtime_Test::execute([$output]);
Check::check($actual === $expected[0], 'Copies, source ownership and destruction match native C++: ' . json_encode([$actual, $expected[0]]));
Check::check((substr_count($actual[1], 'C:') + substr_count($actual[1], 'K:')) === substr_count($actual[1], 'D:'),
    'Each managed construction and copy has exactly one destruction');
Check::check(str_contains($actual[1], "R:1:7\nR:2:8\n"), 'Copy owns independent data while the source stays live');
Lifecycle_Test::preparation($first);
$id = $first->symbols->current->find_symbol('managed', '', \collect_symbols\symbol_kind::function_symbol);
$plan = $first->lowered->for_symbol($id);
$analysis = $first->lifetimes->for_symbol($id);
$copies = array_filter($plan->instructions, static fn($row) => $row->kind === \lower\instruction_kind::copy_construct);
Check::check(count($copies) === 5, 'Both object types use explicit copy instructions, direct construction emits none');
foreach ($copies as $copy) {
    Check::check(count($copy->payload->target->parameters) === 2, 'Every copy uses the shared two-address ABI');
}
$exported = array_values(array_filter($plan->to_array()['instructions'], static fn($row) => $row['kind'] === 'copy_construct'));
Check::check((count($exported) === count($copies))
    && (array_keys($exported[0]['payload']) === ['destination', 'source_value_id', 'link_name']),
    'Copy exports keep operand identities and an exact target reference without repeating its contract');
$before = serialize($first);

// Analysis and lowering use the same fixed work units in arbitrary completion order.
$tasks = \Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $first->bodies, new \analyze_lifetimes\Lifetime_Set(), true);
$results = array_map(static fn($task) => (new \analyze_lifetimes\Lifetime_Worker($task))->analyze(), array_reverse($tasks));
$joined = (new \analyze_lifetimes\Lifetime_Join($first->bodies, new \analyze_lifetimes\Lifetime_Set(), $tasks))->join($results);
Check::check($joined->to_json() === $first->lifetimes->to_json(), 'Copy source borrows and destination cleanups join deterministically');
$tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->lowered->to_json()) && (serialize($first) === $before),
    'Copy lowering joins deterministically without changing retained inputs');

Check::edit($managed_path, str_replace('        return $total + read_owned($early);',
    "        \$extra owned = \$early;\n        return \$total + read_owned(\$extra);", $source));
$second = $session->compile($manifest, $output);
Check::check(Copy_Runtime_Test::execute([$output]) === $expected[1], 'Incremental copied-local addition matches native C++ on both paths');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->lowered->for_symbol($id) !== $plan) && ($second->lifetimes->for_symbol($id) !== $analysis)
    && (count($second->lifetimes->for_symbol($id)->cleanups) === (count($analysis->cleanups) + 1))
    && ($second->llvm->function_for($id) !== $first->llvm->function_for($id))
    && ($second->native->object_for($plan->source_file_id()) !== $first->native->object_for($plan->source_file_id())),
    'Body edit replaces analysis, copy plan, emission and native object under unchanged contracts');
$unchanged = 0;
foreach ($first->llvm->modules as $module)
{
    if ($module->source_file_id === $plan->source_file_id()) {
        continue;
    }
    Check::check($second->native->object_for($module->source_file_id) === $first->native->object_for($module->source_file_id), 'Reuse unchanged native object');
    foreach ($module->functions as $function) {
        $other = $function->body->binding->callable_id;
        Check::check(($second->lifetimes->for_symbol($other) === $first->lifetimes->for_symbol($other))
            && ($second->lowered->for_symbol($other) === $first->lowered->for_symbol($other))
            && ($second->llvm->function_for($other) === $function), 'Reuse unchanged caller and unrelated managed function');
        ++$unchanged;
    }
}
Check::check(($unchanged >= 2) && (serialize($first) === $before), 'Preserve retained snapshots and unchanged managed code');

// Unsupported assignment is not authorized by a copy-construction capability.
Files::directory($root . '/negative/src');
$negative = $root . '/negative/project.json';
Files::write_json($negative, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/negative/src/main.phs', '$a owned = make_owned(1); $b owned = $a; $a = $b; return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
    ->compile($negative, $root . '/negative-program'), 'assignment is unavailable');

// A copyable C++ trait without an explicit lifecycle binding grants no capability.
$missing = $metadata;
foreach ($missing['types'] as &$type) {
    unset($type['lifecycle']['copy_construct']);
}
unset($type);
Copy_Runtime_Test::metadata($config['output_directory'], $missing);
Files::write($root . '/negative/src/main.phs', '$a owned = make_owned(1); $b owned = $a; return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
    ->compile($negative, $root . '/negative-program'), 'copy construction is unavailable');

// Reject ownership and physical ABI corruption independently of artifact integrity.
foreach (['ownership', 'abi'] as $fault)
{
    $invalid = $metadata;
    foreach ($invalid['operations'] as &$operation)
    {
        if ($operation['kind'] === 'copy_construct')
        {
            if ($fault === 'ownership') {
                $operation['parameters'][0]['ownership'] = 'consumed';
            }
            else {
                array_pop($operation['abi']['parameters']);
            }
            break;
        }
    }
    unset($operation);
    Copy_Runtime_Test::metadata($config['output_directory'], $invalid);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($config['output_directory'], $first->backend->runtime->base_catalog), 'Unsupported runtime copy construction contract');
}
Check::check(serialize($first) === $before, 'Rejected metadata never mutates the retained snapshot');
echo "runtime copy ok: explicit metadata, independent storage, cleanup-free copies, control flow, native oracle, lifecycle workers and incremental replacement\n";
