<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Runtime_Assignment_Test
{
    /** Observe exact events and process status independently of the compiler's internal plans. */
    public static function run(string $program): array
    {
        $process = proc_open([$program], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start assignment proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

    /** Keep artifact integrity valid so malformed contracts reach the adapter's semantic checks. */
    public static function metadata(string $directory, array $metadata): void
    {
        $path = $directory . '/package/manifest.json';
        $manifest = Files::json($path);
        $metadata_path = $directory . '/package/' . $manifest['metadata'];
        Files::write_json($metadata_path, $metadata);
        $manifest['artifacts'][$manifest['metadata']] = hash_file('sha256', $metadata_path);
        Files::write_json($path, $manifest);
        $pointer = Files::json($directory . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $path);
        Files::write_json($directory . '/current.json', $pointer);
    }
}

$root = getcwd() . '/runtime-assignment';
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
Files::directory($root . '/definitions');
Files::directory($root . '/project');
foreach (glob($preparation . '/definitions/*.json') as $path) {
    Files::write($root . '/definitions/' . basename($path), Files::read($path));
}
Files::write($root . '/observer.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdio>
#include <cstdlib>
#include <unordered_set>
namespace proof {
inline std::unordered_set<const void*> live;
inline int allocations = 0, releases = 0;
struct alignas(64) observed {
    const observed* identity;
    std::int64_t* value;
    explicit observed(std::int64_t n = 0) : identity(this), value(new std::int64_t(n)) {
        ++allocations;
        if (!live.insert(this).second) std::abort();
        std::printf("C:%lld\n", (long long)n);
    }
    observed(const observed& source) : identity(this), value(new std::int64_t(source.read())) {
        ++allocations;
        if (!live.insert(this).second) std::abort();
        std::printf("K:%lld\n", (long long)*value);
    }
    observed& operator=(const observed& source) {
        const auto previous = read();
        const auto next = source.read();
        std::printf("%c:%lld:%lld\n", this == &source ? 'S' : 'A', (long long)previous, (long long)next);
        // Allocate before releasing, including self-assignment: both objects must still be live.
        auto replacement = new std::int64_t(next);
        ++allocations; delete value; ++releases; value = replacement;
        return *this;
    }
    ~observed() {
        std::printf("D:%lld\n", (long long)read());
        live.erase(this); delete value; ++releases;
    }
    std::int64_t read() const {
        if (identity != this || !live.contains(this)) std::abort();
        return *value;
    }
};
inline std::int64_t finished() {
    if (!live.empty() || allocations != releases) std::abort();
    return 0;
}
}
CPP);
$definition = ['schema_version' => 1, 'types' => [
    ['id' => 'observed', 'kind' => 'runtime_value', 'cpp_name' => 'proof::observed', 'header' => 'observer.hpp',
        'storage' => 'inline', 'struct_field' => true, 'language_type' => ['name' => 'observed', 'namespace' => ''],
        'lifecycle' => ['default_construct' => 'observed.default', 'copy_construct' => 'observed.copy',
            'copy_assign' => 'observed.assign', 'destroy' => 'observed.destroy']],
], 'operations' => []];
foreach (['default' => 'construct', 'copy' => 'copy_construct', 'assign' => 'copy_assign', 'destroy' => 'destroy'] as $id => $kind) {
    $operation = ['id' => 'observed.' . $id, 'kind' => $kind, 'type' => 'observed', 'error_policy' => 'terminate'];
    if ($kind === 'construct') {
        $operation['parameters'] = [];
    }
    $definition['operations'][] = $operation;
}
$definition['operations'][] = ['id' => 'observed.make', 'kind' => 'construct', 'type' => 'observed',
    'parameters' => ['native_int'], 'error_policy' => 'terminate', 'expose_as' => ['name' => 'make_observed', 'namespace' => '']];
$definition['operations'][] = ['id' => 'observed.read', 'kind' => 'const_method', 'type' => 'observed',
    'member' => 'read', 'result_type' => 'native_int', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'read_observed', 'namespace' => '']];
$definition['operations'][] = ['id' => 'finished', 'kind' => 'free_function', 'cpp_name' => 'proof::finished',
    'header' => 'observer.hpp', 'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'finished', 'namespace' => '']];
Files::write_json($root . '/definitions/observed.json', $definition);
$config = Files::json($preparation . '/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/runtime';
$config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($preparation, $path)),
    $config['include_directories']), $root];
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$metadata = Files::json($root . '/runtime/package/metadata.json');
$operations = array_column($metadata['operations'], null, 'id');
$assignment = $operations['observed.assign'];
Check::check(($assignment['result'] === null) && ($assignment['self_assignment'] === 'native_call')
    && ($assignment['storage_precondition'] === 'live_object') && ($assignment['source_after'] === 'live_object')
    && (array_column($assignment['abi']['parameters'], 'type') === ['ptr', 'ptr']),
    'Prepared assignment has no constructed result and preserves both live operands');

Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
Files::write($root . '/project/main.phs', 'text(); return objects() + finished();');
Files::write($root . '/project/generic.phs', <<<'PHS'
template<typename T>
function copied(const T &$source): T
{
    $first T = $source;
    $second T = $first;
    $second = $source;
    $second = $second;
    return $second;
}
PHS);
Files::write($root . '/project/text.phs', <<<'PHS'
function text(): void
{
    $left string = "old destination contents long enough to allocate";
    $right string = "new contents long enough to allocate independently";
    $left = $right;
    $left = $left;
    $copy string = copied<string>($left);
    $replacement string = "changed";
    $right = $replacement;
    echo $left, "/", $copy, "/", $right, "\n";
}
PHS);
$source = <<<'PHS'
struct pair { public observed $first; public observed $last; }
function objects(): int
{
    $a observed = make_observed(3);
    $b observed = make_observed(9);
    $b = $a;
    $b = $b;
    $c observed = make_observed(7);
    $a = $c;
    $copy observed = copied<observed>($b);
    $one pair;
    $two pair;
    $one->first = $a;
    $one->last = $b;
    $two = $one;
    $two = $two;
    $one->first = $c;
    return read_observed($b) + read_observed($a) + read_observed($copy) + read_observed($two->first);
}
PHS;
$path = $root . '/project/objects.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime');
$first = $session->compile($root . '/project/project.json', $root . '/program');
$prefix = "new contents long enough to allocate independently/new contents long enough to allocate independently/changed\n";
$trace = "C:3\nC:9\nA:9:3\nS:3:3\nC:7\nA:3:7\nK:3\nK:3\nA:3:3\nS:3:3\nK:3\nD:3\nD:3\n"
    . "C:0\nC:0\nC:0\nC:0\nA:0:7\nA:0:3\nA:0:7\nA:0:3\nS:7:7\nS:3:3\nA:7:7\n"
    . "D:3\nD:7\nD:3\nD:7\nD:3\nD:7\nD:3\nD:7\n";
Check::check(Runtime_Assignment_Test::run($root . '/program') === [20, $prefix . $trace, ''],
    'Native assignment preserves identity, replaces old contents, calls self-assignment and cleans every allocation once');
Lifecycle_Test::preparation($first);
$before = serialize($first);

// The same assignment tasks work under fixed inputs and arbitrary worker completion order.
$tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$join = new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $tasks);
Check::check(($join->join($results)->to_json() === $first->lowered->to_json()) && (serialize($first) === $before),
    'Assignment lowering joins independent private outputs without mutating retained inputs');
Check::rejects(static fn() => $join->join(array_slice($results, 1)), 'Incomplete');

Check::edit($path, str_replace('    $b = $b;', '    $b = $a;', $source));
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Runtime_Assignment_Test::run($root . '/program') === [20, $prefix . preg_replace('/S:3:3/', 'A:3:3', $trace, 1), ''],
    'One assignment body edit changes the executed operation while preserving values and cleanup');
Check::check(!$second->inputs->context->full_rebuild && ($second->backend === $first->backend)
    && (serialize($first) === $before), 'Assignment increment reuses ABI/layout and preserves its previous snapshot');

// Optimize and link the same accepted ABI artifacts; this is compatibility, not a new compiler mode.
$modules = [];
foreach ($first->llvm->modules as $index => $module) {
    $file = $root . '/caller-' . $index . '.ll';
    Files::write($file, $module->ir);
    $modules[] = $file;
}
$tools = new \runtime_preparation\Clang_Toolchain($config, $preparation);
$runtime = $first->backend->runtime;
$tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', '-flto=thin',
    '--ld-path=' . Files::executable('/', 'ld.lld-18'), ...$modules,
    ...$runtime->modules_for(\load_runtime\runtime_module_kind::thin_lto), '-o', $root . '/optimized']);
Check::check(Runtime_Assignment_Test::run($root . '/optimized') === [20, $prefix . $trace, ''],
    'O1/ThinLTO preserves assignment and cleanup events');

// Existing objects are required; neither const destinations nor temporary sources gain permissions.
foreach ([
    ['function bad(const observed &$value): void { $value = $value; } return 0;', 'const reference parameter'],
    ['$a observed; $a = make_observed(1); return 0;', 'existing source object'],
] as [$text, $message])
{
    Files::write($root . '/bad.phs', $text);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))->compile($root . '/bad.phs'), $message);
}

// A native trait does not authorize assignment without an explicit lifecycle binding.
$missing = $metadata;
foreach ($missing['types'] as &$type) {
    unset($type['lifecycle']['copy_assign']);
}
unset($type);
Runtime_Assignment_Test::metadata($root . '/runtime', $missing);
Files::write($root . '/bad.phs', '$a observed; $b observed; $a = $b; return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))->compile($root . '/bad.phs'), 'Copy assignment is unavailable');
Files::write($root . '/bad.phs', 'template<typename T> function take_copy(const T &$a): T { return $a; } $a observed; take_copy<observed>($a); return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $root . '/runtime'))->compile($root . '/bad.phs'), 'copy assignment');

// Reject malformed semantics even when the package checksums agree with its contents.
foreach (['destination', 'source', 'lifetime', 'self', 'result', 'abi', 'trait'] as $fault)
{
    $invalid = $metadata;
    foreach ($invalid['operations'] as &$operation)
    {
        if ($operation['id'] !== 'observed.assign') {
            continue;
        }
        match ($fault)
        {
            'destination' => $operation['parameters'][0]['passing'] = 'const_address',
            'source' => $operation['parameters'][1]['ownership'] = 'consumed',
            'lifetime' => $operation['storage_precondition'] = 'aligned_uninitialized_storage',
            'self' => $operation['self_assignment'] = 'skip',
            'result' => $operation['result'] = ['ownership' => 'owned'],
            'abi' => $operation['abi']['parameters'][1]['type'] = 'i64',
            'trait' => null,
        };
    }
    unset($operation);
    if ($fault === 'trait')
    {
        foreach ($invalid['types'] as &$type) {
            if ($type['id'] === 'observed') {
                $type['cpp_traits']['copy_assignable'] = false;
            }
        }
        unset($type);
    }
    Runtime_Assignment_Test::metadata($root . '/runtime', $invalid);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($root . '/runtime', $runtime->base_catalog),
        $fault === 'abi' ? 'address' : 'Unsupported runtime copy assignment contract');
}
Check::check(serialize($first) === $before, 'Rejected assignment contracts preserve retained compiler inputs');

// Declared permission still needs an executable native implementation; a trait alone cannot supply it.
Files::directory($root . '/unavailable-definitions');
Files::write($root . '/unavailable.hpp', <<<'CPP'
#pragma once
namespace proof {
struct unavailable {
    unavailable& operator=(const unavailable&) = delete;
};
}
CPP);
Files::write_json($root . '/unavailable-definitions/type.json', ['schema_version' => 1,
    'types' => [['id' => 'unavailable', 'kind' => 'runtime_value', 'cpp_name' => 'proof::unavailable',
        'header' => 'unavailable.hpp', 'storage' => 'inline', 'lifecycle' => ['cleanup' => 'none', 'copy_assign' => 'assign']]],
    'operations' => [['id' => 'assign', 'kind' => 'copy_assign', 'type' => 'unavailable', 'error_policy' => 'terminate']]]);
$unavailable = $config;
$unavailable['definitions_directory'] = $root . '/unavailable-definitions';
$unavailable['include_directories'] = [$root];
$unavailable['output_directory'] = $root . '/unavailable-runtime';
Files::write_json($root . '/unavailable.json', $unavailable);
Check::rejects(static fn() => (new \runtime_preparation\Runtime_Preparation())->run($root . '/unavailable.json'), 'copy_assignable');
Check::check(!is_file($unavailable['output_directory'] . '/current.json'), 'Unavailable native assignment never publishes a package');
echo "runtime assignment ok: ordinary/family-independent metadata, live replacement, self-assignment, fields, generic permissions, cleanup, joins and one increment\n";
