<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Composition_Test
{
    /** Capture exact events and exit status for comparison with independently compiled C++. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start composition proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }
    /** Refresh artifact integrity to exercise semantic metadata rejection independently of checksums. */
    public static function metadata(string $root, array $metadata): void
    {
        $manifest = Files::json($root . '/package/manifest.json');
        $path = $root . '/package/' . $manifest['metadata'];
        Files::write_json($path, $metadata);
        $manifest['artifacts'][$manifest['metadata']] = hash_file('sha256', $path);
        Files::write_json($root . '/package/manifest.json', $manifest);
        $pointer = Files::json($root . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $root . '/package/manifest.json');
        Files::write_json($root . '/current.json', $pointer);
    }

}

$root = getcwd() . '/composition';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
Files::write($root . '/container.hpp', <<<'CPP'
#pragma once
#include <scpp/vector_t.hpp>
#include <cstdint>
#include <cstdio>
#include <cstdlib>
namespace proof {
struct item { std::int64_t value; };
inline int serial = 0, live = 0;
// Observable adapters around a real permitted vector of a plain native record.
// The local provider contract authorizes these test containers as inline fields.
template<int Alignment> struct alignas(Alignment) observed_vector {
    mutable scpp::vector_t<item> data;
    const observed_vector *identity;
    int id;
    void check() const { if (identity != this || (reinterpret_cast<std::uintptr_t>(this) % Alignment)) std::abort(); }
    observed_vector() : data{{7}}, identity(this), id(++serial) {
        check(); ++live; std::printf("C:%d\n", id);
    }
    observed_vector(const observed_vector &other) : data(other.data), identity(this), id(++serial) {
        other.check(); check(); ++live; std::printf("K:%d:%d\n", id, other.id);
    }
    ~observed_vector() { check(); std::printf("D:%d\n", id); --live; }
    std::int64_t read() const { check(); return data.at(std::size_t(0)).value; }
    std::int64_t advance() const { check(); return ++data.at(std::size_t(0)).value; }
};
using sequence = observed_vector<64>;
using other_sequence = observed_vector<128>;
inline std::int64_t finished() { if (live != 0) std::abort(); return 0; }
}
CPP);
$types = [['id' => 'integer', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
    'language_type' => ['name' => 'int', 'namespace' => '']]];
$operations = [];
foreach (['sequence', 'other_sequence'] as $name)
{
    $types[] = ['id' => $name, 'kind' => 'runtime_value', 'cpp_name' => 'proof::' . $name, 'header' => 'container.hpp',
        'storage' => 'inline', 'struct_field' => true,
        'lifecycle' => ['default_construct' => $name . '.default', 'copy_construct' => $name . '.copy', 'destroy' => $name . '.destroy'],
        'language_type' => ['name' => $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.default', 'kind' => 'construct', 'type' => $name, 'parameters' => [], 'error_policy' => 'terminate'];
    $operations[] = ['id' => $name . '.copy', 'kind' => 'copy_construct', 'type' => $name, 'error_policy' => 'terminate'];
    $operations[] = ['id' => $name . '.destroy', 'kind' => 'destroy', 'type' => $name, 'error_policy' => 'terminate'];
    foreach (['read', 'advance'] as $method) {
        $operations[] = ['id' => $name . '.' . $method, 'kind' => 'const_method', 'type' => $name, 'member' => $method,
            'result_type' => 'integer', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
            'expose_as' => ['name' => $method . '_' . $name, 'namespace' => '']];
    }
}
$operations[] = ['id' => 'finished', 'kind' => 'free_function', 'cpp_name' => 'proof::finished', 'header' => 'container.hpp',
    'parameters' => [], 'result_type' => 'integer', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'finished', 'namespace' => '']];
Files::write_json($root . '/definitions/containers.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['include_directories'] = array_map(static fn($path) => realpath(dirname(__DIR__, 2) . '/src-runtime-preparation/' . $path), $config['include_directories']);
$config['include_directories'][] = $root;
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$package = Files::json($root . '/runtime/package/manifest.json');
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'return exercise(1) + exercise(0) + finished();');
Files::write($root . '/project/src/types.phs', <<<'PHS'
struct inner { public uint8 $tag; public sequence $first; public int32 $tail; }
struct outer { public uint8 $leading; public inner $nested; public other_sequence $last; public inner $repeated[2]; }
struct alternative { public other_sequence $last; public int32 $middle; public sequence $first; }
PHS);
$body = <<<'PHS'
function exercise($flag int): int {
    $a outer = new outer();
    $b outer = $a;
    advance_sequence($b->nested->first);
    $child inner = $b->nested;
    $total int = read_sequence($a->nested->first) + read_sequence($b->nested->first) + read_sequence($child->first);
    $total = $total + read_sequence($b->repeated[1]->first);
    new outer();
    if ($flag) {
        $extra alternative;
        return $total + read_sequence($extra->first);
    }
    return $total;
}
PHS;
$path = $root . '/project/src/body.phs';
Files::write($path, $body);
Files::write($root . '/oracle.cpp', <<<'CPP'
#include "container.hpp"
struct inner { std::uint8_t tag; proof::sequence first; std::int32_t tail; };
struct outer { std::uint8_t leading; inner nested; proof::other_sequence last; inner repeated[2]; };
struct alternative { proof::other_sequence last; std::int32_t middle; proof::sequence first; };
static std::int64_t exercise(std::int64_t flag) {
    outer a{};
    outer b = a;
    b.nested.first.advance();
    inner child = b.nested;
    std::int64_t total = a.nested.first.read() + b.nested.first.read() + child.first.read();
    total += b.repeated[1].first.read();
    (void)outer{};
    if (flag) {
        alternative extra{};
#if BODY_EDIT
        alternative another = extra;
        return total + another.first.read();
#else
        return total + extra.first.read();
#endif
    }
    return total;
}
int main() { return exercise(1) + exercise(0) + proof::finished(); }
CPP);
$expected = [];
foreach ([0, 1] as $edit)
{
    $command = [$package['link_driver']['executable'], '--driver-mode=g++', '-std=c++23', '-DBODY_EDIT=' . $edit];
    foreach ($config['include_directories'] as $include) {
        $command[] = '-I' . $include;
    }
    array_push($command, $root . '/oracle.cpp', '-o', $root . '/oracle');
    [$status, , $error] = Composition_Test::run($command);
    Check::check($status === 0, 'Build native composition oracle: ' . $error);
    $expected[] = Composition_Test::run([$root . '/oracle']);
}
$session = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime');
$first = $session->compile($manifest, $root . '/program');
$actual = Composition_Test::run([$root . '/program']);
Check::check($actual === $expected[0], 'Composed lifecycle matches C++: ' . json_encode([$actual, $expected[0]]));
Check::check($actual[0] === 67, 'Nested copies own independent vector contents');
// Check emitted artifacts with LLVM optimization/LTO without adding compiler mode switches.
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Files::write($modules[count($modules) - 1], $ir);
}
foreach ([\load_runtime\runtime_module_kind::ordinary, \load_runtime\runtime_module_kind::full_lto, \load_runtime\runtime_module_kind::thin_lto] as $mode)
{
    $flags = match ($mode) {
        \load_runtime\runtime_module_kind::ordinary => [],
        \load_runtime\runtime_module_kind::full_lto => ['-flto=full', '-fuse-ld=lld'],
        \load_runtime\runtime_module_kind::thin_lto => ['-flto=thin', '-fuse-ld=lld'],
    };
    [$status, , $error] = Composition_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
        '--target=' . $package['target']['triple'], '-O1', ...$flags, ...$modules,
        ...$first->backend->runtime->modules_for($mode), ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
    Check::check(($status === 0) && (Composition_Test::run([$root . '/optimized']) === $expected[0]),
        'Composed artifacts preserve lifecycle under O1/' . $mode->value . ': ' . $error);
}
$before = serialize($first);
$tasks = [];
foreach ($first->types->types->lifecycle_operations() as $operation) {
    $tasks[] = new \emit_llvm\lifecycle_emission_task($operation, $first->backend);
}
$arrays = array_filter($tasks, static fn($task) => $task->operation->repeat > 0);
Check::check(count($arrays) === 3, 'One default/copy/destroy plan for the fixed-array type');
foreach ($arrays as $task) {
    Check::check(count($task->operation->members) === 1, 'Array plan size is independent of element count');
}
$results = array_map(static fn($task) => \emit_llvm\Lifecycle_Emission::emit($task), array_reverse($tasks));
$joined = (new \emit_llvm\Lifecycle_Emission_Join($first->backend, [], $tasks))->join($results);
Check::check(array_map(static fn($row) => $row->ir, $joined) === array_map(static fn($row) => $row->ir, $first->llvm->lifecycle),
    'Private lifecycle workers join in deterministic type/role order');
Check::rejects(static fn() => (new \emit_llvm\Lifecycle_Emission_Join($first->backend, [], $tasks))->join([]), 'Incomplete');
Check::rejects(static fn() => (new \emit_llvm\Lifecycle_Emission_Join($first->backend, [], $tasks))->join([...$results, $results[0]]), 'duplicate');
$foreign = \emit_llvm\Lifecycle_Emission::emit(new \emit_llvm\lifecycle_emission_task($tasks[0]->operation, $first->backend));
Check::rejects(static fn() => (new \emit_llvm\Lifecycle_Emission_Join($first->backend, $first->llvm->lifecycle, [$tasks[0]]))
    ->join([$foreign]), 'Unexpected');
Check::edit($path, str_replace('return $total + read_sequence($extra->first);',
    '$another alternative = $extra; return $total + read_sequence($another->first);', $body));
$second = $session->compile($manifest, $root . '/program');
Check::check(Composition_Test::run([$root . '/program']) === $expected[1], 'Body replacement preserves native lifecycle order');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->llvm->lifecycle === $first->llvm->lifecycle) && (serialize($first) === $before),
    'One increment reuses generated lifecycle definitions and leaves the old snapshot unchanged');

// Reject unsupported actions before publishing code, without guessing lifecycle from layout.
$declarations = file_get_contents($root . '/project/src/types.phs');
foreach ([
    ['$a outer; $b outer; $a = $b; return 0;', 'assignment is unavailable'],
    ['$a outer; $b alternative = $a; return 0;', 'Unsupported'],
    ['struct cycle { public cycle $self; } return 0;', 'Unresolved concrete preparation prerequisite'],
    ['function take($value outer): int { return 0; } return 0;', 'Struct function parameters'],
] as [$source, $message]) {
    Files::write($root . '/negative.phs', $declarations . "\n" . $source);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))
        ->compile($root . '/negative.phs'), $message);
}
$metadata = Files::json($root . '/runtime/package/' . $package['metadata']);
foreach (['default_construct', 'copy_construct'] as $capability)
{
    $missing = $metadata;
    foreach ($missing['types'] as &$type) {
        if ($type['id'] === 'sequence') {
            unset($type['lifecycle'][$capability]);
        }
    }
    unset($type);
    Composition_Test::metadata($root . '/runtime', $missing);
    Files::write($root . '/negative.phs', $declarations . "\n" . '$a outer; $b outer = $a; return 0;');
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))
        ->compile($root . '/negative.phs'), $capability === 'default_construct' ? 'Default construction is unavailable' : 'copy construction is unavailable');
}
// The new metadata permissions and constructor ABI fail closed on malformed input.
foreach (['field', 'abi'] as $fault)
{
    $invalid = $metadata;
    if ($fault === 'field')
    {
        foreach ($invalid['types'] as &$type) {
            if ($type['id'] === 'sequence') {
                $type['struct_field'] = null;
            }
        }
        unset($type);
    }
    else
    {
        foreach ($invalid['operations'] as &$operation) {
            if ($operation['id'] === 'sequence.default') {
                $operation['abi']['parameters'] = [];
            }
        }
        unset($operation);
    }
    Composition_Test::metadata($root . '/runtime', $invalid);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($root . '/runtime', $first->backend->runtime->base_catalog),
        $fault === 'field' ? 'field eligibility' : 'default construction contract');
}
Composition_Test::metadata($root . '/runtime', $metadata);
Check::check(serialize($first) === $before, 'Rejected operations preserve the retained compiler snapshot');

echo "lifecycle composition ok: nested aligned runtime fields, compact arrays, member copies, temporary/early cleanup, private workers and one increment\n";
