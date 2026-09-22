<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Allocation_Test
{
    /** Run native proofs without exposing compiler-private descriptors to source code. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start allocation proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }
    /** Refresh integrity records so malformed metadata is tested at the semantic adapter boundary. */
    public static function metadata(string $runtime, array $metadata): void
    {
        Files::write_json($runtime . '/package/metadata.json', $metadata);
        $manifest = Files::json($runtime . '/package/manifest.json');
        $manifest['artifacts']['metadata.json'] = hash_file('sha256', $runtime . '/package/metadata.json');
        Files::write_json($runtime . '/package/manifest.json', $manifest);
        $pointer = Files::json($runtime . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $runtime . '/package/manifest.json');
        Files::write_json($runtime . '/current.json', $pointer);
    }
}

$root = getcwd() . '/allocation-proof';
Files::directory($root . '/src');
// Prepare both the production owner and an unrelated owner with reordered parameters.
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
Files::directory($root . '/definitions');
$definitions = Files::json($preparation . '/definitions/allocation.json');
$scalars = Files::json($preparation . '/definitions/scalars.json');
$definitions['types'][] = $scalars['types'][0];
$definitions['types'][] = ['id' => 'void', 'cpp_name' => 'void', 'header' => 'cstddef', 'kind' => 'void',
    'language_type' => ['name' => 'void', 'namespace' => '']];
Files::write($root . '/observer.hpp', <<<'CPP'
#pragma once
#include <scpp_provider/allocation.hpp>
#include <cstdlib>
#include <cstdio>
namespace proof {
inline int live = 0;
struct lease {
    void* pointer = nullptr;
    std::int64_t bytes = 0;
    lease() = default;
    lease(const lease&) = delete;
};
inline void acquire(std::int64_t bytes, lease& owner) {
    if (bytes <= 0 || owner.pointer) std::abort();
    owner.pointer = std::malloc(static_cast<std::size_t>(bytes));
    if (!owner.pointer) throw std::bad_alloc();
    owner.bytes = bytes;
    ++live;
}
inline void transfer(lease& destination, std::int64_t unused, lease& source) {
    if (destination.pointer || !source.pointer || unused != 0) std::abort();
    destination.pointer = source.pointer;
    destination.bytes = source.bytes;
    source.pointer = nullptr;
    source.bytes = 0;
}
inline std::int64_t release(lease& owner) {
    if (owner.pointer) { --live; std::free(owner.pointer); }
    owner.pointer = nullptr;
    owner.bytes = 0;
    return 0;
}
inline std::int64_t inspect(const lease& owner, std::int64_t extra) {
    if (!owner.pointer) std::abort();
    return owner.bytes + extra;
}
inline std::int64_t finished() { if (live) std::abort(); return 0; }
inline std::int64_t aligned(const scpp_provider::allocation& owner) {
    if (!owner.address || reinterpret_cast<std::uintptr_t>(owner.address) % owner.alignment) std::abort();
    // Touch both ends so the proof observes real writable storage of the requested size.
    auto data = static_cast<unsigned char*>(owner.address);
    data[0] = 7;
    data[owner.bytes - 1] = 9;
    return 0;
}
}
CPP);
$definitions['types'][] = ['id' => 'lease', 'cpp_name' => 'proof::lease', 'header' => 'observer.hpp',
    'kind' => 'runtime_value', 'storage' => 'inline', 'resource' => 'allocation',
    'lifecycle' => ['default_construct' => 'lease.default', 'cleanup' => 'none'],
    'language_type' => ['name' => 'lease', 'namespace' => '']];
$definitions['operations'][] = ['id' => 'lease.default', 'kind' => 'construct', 'type' => 'lease',
    'parameters' => [], 'error_policy' => 'terminate'];
$mutable = ['type' => 'lease', 'passing' => 'mutable_address', 'borrow_scope' => 'call'];
$const = ['type' => 'lease', 'passing' => 'const_address', 'borrow_scope' => 'call'];
foreach ([
    ['acquire', ['native_int', $mutable], 'void', ['kind' => 'acquire', 'owner' => 1]],
    ['transfer', [$mutable, 'native_int', $mutable], 'void', ['kind' => 'transfer', 'owner' => 2, 'destination' => 0]],
    ['release', [$mutable], 'native_int', ['kind' => 'release', 'owner' => 0]],
    ['inspect', [$const, 'native_int'], 'native_int', ['kind' => 'inspect', 'owner' => 0]],
    ['aligned', [['type' => 'allocation', 'passing' => 'const_address', 'borrow_scope' => 'call']], 'native_int', ['kind' => 'inspect', 'owner' => 0]],
    ['finished', [], 'native_int', null],
] as [$name, $parameters, $result, $effect])
{
    $operation = ['id' => 'proof.' . $name, 'kind' => 'free_function', 'cpp_name' => 'proof::' . $name,
        'header' => 'observer.hpp', 'parameters' => $parameters, 'result_type' => $result,
        'error_policy' => 'terminate', 'expose_as' => ['name' => 'proof_' . $name, 'namespace' => '']];
    if ($effect !== null) {
        $operation['allocation_effect'] = $effect;
    }
    $definitions['operations'][] = $operation;
}
Files::write_json($root . '/definitions/owners.json', $definitions);
$config = Files::json($preparation . '/config.json');
$config['include_directories'] = [$preparation . '/include', $root];
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$runtime = $root . '/runtime';
$manifest = $root . '/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/src/main.phs', 'return exercise() + alternative() + proof_finished();');
$source = <<<'PHS'
function exercise(): int {
    $a allocation;
    $b allocation;
    allocation_acquire($a, 37, 64);
    allocation_transfer($a, $b);
    proof_aligned($b);
    $size int = allocation_size($b);
    if ($size) { proof_aligned($b); } else { proof_aligned($b); }
    allocation_release($a);
    allocation_release($b);
    allocation_acquire($a, 5, 8);
    allocation_release($a);
    return $size;
}
function alternative(): int {
    $a lease;
    $b lease;
    proof_acquire(9, $a);
    proof_transfer($b, 0, $a);
    proof_inspect($b, proof_inspect($b, 0));
    proof_release($b);
    $i int = 2;
    while ($i) {
        proof_acquire(8, $a);
        proof_release($a);
        $i = 0;
    }
    if ($i) { proof_acquire(4, $b); proof_release($b); }
    else { proof_acquire(5, $b); proof_release($b); }
    return 0;
}
PHS;
$path = $root . '/src/body.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$first = $session->compile($manifest, $root . '/program');
Check::check(Allocation_Test::run([$root . '/program']) === [37, '', ''], 'Prepared allocation, transfer and release execute');
$before = serialize($first);
$debug = json_decode($first->lifetimes->to_json(), true, flags: JSON_THROW_ON_ERROR);
Check::check(str_contains(json_encode($debug), '"allocations"'), 'Allocation analysis participates in debug exports');
$analyses = array_filter($first->lifetimes->bodies(), static fn($body) => $body->allocations !== null);
Check::check(count($analyses) === 2, 'Only allocation bodies retain resource flow facts');
Check::edit($path, str_replace('37, 64', '41, 64', $source));
$second = $session->compile($manifest, $root . '/program');
Check::check(Allocation_Test::run([$root . '/program']) === [41, '', ''], 'Body replacement changes native allocation');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend->layouts === $first->backend->layouts)
    && (serialize($first) === $before), 'One body increment retains layouts and old snapshot');

// Same selected body work units support arbitrary completion order and exact joins.
$tasks = $first->bodies->bodies();
$results = array_map(static fn($body) => (new \analyze_lifetimes\Lifetime_Worker($body))->analyze(), $tasks);
$join = new \analyze_lifetimes\Lifetime_Join($first->bodies, new \analyze_lifetimes\Lifetime_Set(), $tasks);
$accepted = $join->join(array_reverse($results));
Check::check(serialize($accepted) === serialize($first->lifetimes), 'Reordered private lifetime results preserve ownership facts');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($second->bodies, $second->lifetimes, $tasks))->join($results), 'stale');

foreach ([
    ['$a lease; proof_acquire(4, $a); return proof_inspect($a, proof_release($a));', 'requires an owned allocation'],
    ['$a lease; proof_acquire(4, $a); proof_release($a); return proof_inspect($a, 0);', 'requires an owned allocation'],
    ['if (1) { $a allocation; allocation_acquire($a, 4, 8); } return 0;', 'must be released'],
    ['$a allocation; while (1) { allocation_acquire($a, 4, 8); } return 0;', 'differs across control-flow'],
    ['$a allocation; allocation_acquire($a, 4, 8); return 0;', 'must be released'],
    ['$a allocation; allocation_acquire($a, 4, 8); allocation_acquire($a, 4, 8); return 0;', 'requires an empty owner'],
    ['$a allocation; $b allocation; allocation_acquire($a, 4, 8); allocation_transfer($a, $b); return allocation_size($a);', 'requires an owned allocation'],
    ['$a allocation; allocation_acquire($a, 4, 8); allocation_transfer($a, $a); return 0;', 'distinct empty destination'],
    ['$a allocation; $b allocation; allocation_acquire($a, 4, 8); allocation_acquire($b, 4, 8); allocation_transfer($a, $b); return 0;', 'distinct empty destination'],
    ['$a allocation; $b allocation = $a; return 0;', 'copy'],
    ['$a allocation; if (1) { allocation_acquire($a, 4, 8); } allocation_release($a); return 0;', 'differs across control-flow'],
    ['$a allocation; allocation_acquire($a, 4, 8); if (1) { return 0; } allocation_release($a); return 0;', 'must be released'],
] as [$invalid, $message]) {
    Files::write($root . '/negative.phs', $invalid);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))
        ->compile($root . '/negative.phs'), $message);
    Check::check($error instanceof \diagnostics\Source_Error, 'Ownership errors are source diagnostics');
}
// Fresh source compilation proves balanced branches, empty exits and empty release.
Files::write($root . '/empty.phs', '$a allocation; allocation_release($a); return 0;');
(new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/empty.phs', $root . '/empty');
Check::check(Allocation_Test::run([$root . '/empty']) === [0, '', ''], 'Empty owner can leave scope or be explicitly released');

// Bad dynamic facts are a fatal bridge failure, with a native diagnostic.
Files::write($root . '/failure.phs', '$a allocation; allocation_acquire($a, 4, 3); allocation_release($a); return 0;');
(new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/failure.phs', $root . '/failure');
[$status, , $error] = Allocation_Test::run([$root . '/failure']);
Check::check(($status !== 0) && str_contains($error, 'allocation alignment must be a power of two'), 'Native allocation checks cross the fatal bridge');

// Optimization consumes the same prepared calls and source modules, with no allocation-specific backend.
$package = Files::json($runtime . '/package/manifest.json');
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Files::write($modules[count($modules) - 1], $ir);
}
[$status, , $error] = Allocation_Test::run([$package['link_driver']['executable'], '--driver-mode=g++',
    '--target=' . $package['target']['triple'], '-O1', '-flto=thin', '-fuse-ld=lld', ...$modules,
    ...$first->backend->runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto),
    ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
Check::check(($status === 0) && (Allocation_Test::run([$root . '/optimized']) === [37, '', '']), 'Allocation proof at O1/ThinLTO: ' . $error);

// Preparation rejects absent/invalid effects before asking Clang to compile anything.
$cases = [];
$bad = $definitions;
unset($bad['operations'][1]['allocation_effect']);
$cases[] = [$bad, 'explicit allocation effect'];
$bad = $definitions;
$bad['operations'][1]['allocation_effect']['owner'] = 1;
$cases[] = [$bad, 'every resource parameter'];
$bad = $definitions;
$bad['operations'][1]['parameters'][0]['passing'] = 'const_address';
$cases[] = [$bad, 'incompatible borrowing'];
$bad = $definitions;
$bad['operations'][3]['parameters'][1]['type'] = 'lease';
$cases[] = [$bad, 'same-type owner positions'];
$bad = $definitions;
$bad['types'][0]['lifecycle']['copy_construct'] = 'allocation.default';
$cases[] = [$bad, 'copy'];
foreach ($cases as [$bad, $message]) {
    Files::write_json($root . '/definitions/owners.json', $bad);
    Check::rejects(static fn() => new \runtime_preparation\Definitions($root . '/definitions'), $message);
}
Files::write_json($root . '/definitions/owners.json', $definitions);

// Imported packages get their own validation; compiler consumers never trust raw JSON effects.
$metadata = Files::json($runtime . '/package/metadata.json');
$indices = array_flip(array_column($metadata['operations'], 'id'));
$cases = [];
$bad = $metadata;
$bad['operations'][$indices['allocation.default']]['allocation_effect'] = ['kind' => 'acquire', 'owner' => 0];
$cases[] = [$bad, 'default construction contract'];
$bad = $metadata;
unset($bad['operations'][$indices['allocation.acquire']]['allocation_effect']);
$cases[] = [$bad, 'allocation effect'];
$bad = $metadata;
$bad['operations'][$indices['allocation.acquire']]['allocation_effect']['owner'] = 1;
$cases[] = [$bad, 'effect positions'];
$bad = $metadata;
$bad['operations'][$indices['allocation.acquire']]['parameters'][0]['passing'] = 'const_address';
$cases[] = [$bad, 'compatible borrowed access'];
$bad = $metadata;
$bad['operations'][$indices['allocation.transfer']]['parameters'][1]['type'] = 'lease';
$cases[] = [$bad, 'same-type positions'];
foreach ($cases as [$bad, $message]) {
    Allocation_Test::metadata($runtime, $bad);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($runtime, \Step_Test::run(new \load_runtime\Language_Types())), $message);
}
Allocation_Test::metadata($runtime, $metadata);
Check::check(serialize($first) === $before, 'Worker reruns and rejected metadata preserve the retained snapshot');
echo "allocation ownership ok: native allocators, effects, joins, borrows, failures, incremental replacement and ThinLTO\n";
