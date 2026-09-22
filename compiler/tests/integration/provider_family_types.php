<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/families/compiler_bridge.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use runtime_preparation\families as native;

final class Family_Types_Test
{
    /** Capture observable construction/destruction across the real generated bridge. */
    public static function execute(string $binary): array
    {
        $process = proc_open([$binary], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $err];
    }
}

$root = getcwd() . '/family-types';
Files::directory($root . '/project');
Files::write($root . '/native.hpp', <<<'CPP'
#pragma once
#include <vector>
#include <cstdio>
#include <cstdint>
namespace fixture {
template<class T> struct list {
    std::vector<T> data;
    list() { std::putchar('C'); }
    ~list() { std::putchar('D'); }
};
template<class A, class B> struct holder {
    A first{}; B second{};
    holder() { std::putchar('H'); }
    ~holder() { std::putchar('h'); }
};
struct marker { int value = 1; ~marker() {} };
inline std::int64_t answer() { return 41; }
}
CPP);
$definition = ['schema_version' => 1, 'types' => [], 'families' => [[
    'id' => 'list', 'cpp_name' => 'fixture::list', 'header' => 'native.hpp',
    'language_type' => ['name' => 'bag', 'namespace' => ''],
    'parameters' => [['name' => 'element', 'contract' => 'copyable_value']],
    'lifecycle' => ['construct' => 'create', 'destroy' => 'release', 'move_construct' => 'move'],
    'operations' => [
        ['id' => 'create', 'kind' => 'construct', 'type' => '$self', 'parameters' => [], 'error_policy' => 'terminate'],
        ['id' => 'release', 'kind' => 'destroy', 'type' => '$self', 'error_policy' => 'terminate'],
        ['id' => 'move', 'kind' => 'move_construct', 'type' => '$self', 'error_policy' => 'terminate'],
    ],
]]];
foreach (['int' => 'std::int64_t', 'int32' => 'std::int32_t', 'uint8' => 'std::uint8_t'] as $name => $cpp) {
    $definition['types'][] = ['id' => $name, 'kind' => 'integer', 'cpp_name' => $cpp, 'header' => 'cstdint',
        'language_type' => ['name' => $name, 'namespace' => '']];
}
$definition['types'][] = ['id' => 'nothing', 'kind' => 'void', 'cpp_name' => 'void', 'header' => 'cstddef',
    'language_type' => ['name' => 'void', 'namespace' => '']];
$pair = $definition['families'][0];
$pair['id'] = 'pair';
$pair['cpp_name'] = 'fixture::holder';
$pair['language_type']['name'] = 'duo';
$pair['parameters'][] = ['name' => 'other', 'contract' => 'copyable_value'];
$definition['families'][] = $pair;
$catalog = native\Catalog::parse($definition, 'fixture');
$declarations = \load_runtime\Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($catalog->families)), native\Catalog::language_bindings($catalog));
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$provider = new native\compiler_provider($catalog, $root . '/generated', 'fixture-runtime-v1',
    ['clang' => $config['clang'], 'target' => $config['target'], 'standard' => $config['standard'], 'include_directories' => [$root]]);
$bridge = new native\Compiler_Bridge([$provider]);
// Compose an ordinary package that extends the base catalog with an unrelated opaque type.
Files::directory($root . '/ordinary-definitions');
Files::write_json($root . '/ordinary-definitions/types.json', ['schema_version' => 1,
    'types' => [$definition['types'][0],
        ['id' => 'marker', 'kind' => 'runtime_value', 'cpp_name' => 'fixture::marker', 'header' => 'native.hpp', 'storage' => 'inline',
            'language_type' => ['name' => 'marker', 'namespace' => ''],
            'lifecycle' => ['default_construct' => 'marker.create', 'destroy' => 'marker.release']]],
    'operations' => [
        ['id' => 'answer', 'kind' => 'free_function', 'cpp_name' => 'fixture::answer', 'header' => 'native.hpp',
            'parameters' => [], 'result_type' => 'int', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'native_answer', 'namespace' => '']],
        ['id' => 'marker.create', 'kind' => 'construct', 'type' => 'marker', 'parameters' => [], 'error_policy' => 'terminate'],
        ['id' => 'marker.release', 'kind' => 'destroy', 'type' => 'marker', 'error_policy' => 'terminate'],
    ]]);
$ordinary = $config;
$ordinary['provider'] = 'ordinary';
$ordinary['definitions_directory'] = $root . '/ordinary-definitions';
$ordinary['include_directories'] = [$root];
$ordinary['output_directory'] = $root . '/ordinary';
Files::write_json($root . '/ordinary.json', $ordinary);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/ordinary.json');
Files::write($root . '/project/functions.phs', 'template<typename T> function make(): int { $v bag<T>; return native_answer(); }');
$source = $root . '/project/main.phs';
Files::write($source, '$a bag<int>; $b bag<int>; $pair duo<int32, uint8>; $tag marker; return make<int32>();');
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$session = new \compile\Compiler_Session(runtime_package_path: $root . '/ordinary', family_declarations: $declarations, family_preparer: $bridge);
$first = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Family_Types_Test::execute($root . '/program') === [41, 'CCHCDhDD', ''], 'Demanded native families construct and clean up through generated ABI');
Check::check((count($first->types->families) === 3) && (count($first->backend->runtime->packages()) === 4),
    'Repeated type occurrences share one semantic instance and package; distinct arguments remain distinct');
$export = json_decode($first->to_json(), true, flags: JSON_THROW_ON_ERROR);
Check::check((count($export['types']['prepared_families']) === 3) && (count($export['inputs']['configured_runtime']['packages']) === 1),
    'Debug exports distinguish demanded instance packages from configured ordinary runtime inputs');
$before = serialize($first);
$packages = array_values($first->types->families);
$stable = $packages[0]->package;
Check::edit($source, '$a bag<int>; $b bag<int>; $pair duo<int32, uint8>; $tag marker; return make<uint8>();');
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(!$second->inputs->context->full_rebuild && (Family_Types_Test::execute($root . '/program') === [41, 'CCHCDhDD', '']),
    'One body increment discovers a new native specialization without a separate compiler path');
Check::check((count(Files::json($root . '/generated/index.json')['keys']) === 4) && (count($second->types->families) === 3)
    && (array_values($second->types->families)[0]->package === $stable) && (serialize($first) === $before),
    'Increment retains exact unchanged types/packages, omits unused membership and preserves prior snapshots');

// LTO remains an artifact compatibility proof, not a new compiler optimization mode.
$tools = new \runtime_preparation\Clang_Toolchain($config, $root);
foreach (['full' => \load_runtime\runtime_module_kind::full_lto, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
{
    $modules = [];
    foreach ($second->llvm->modules as $index => $module) {
        $path = $root . '/caller-' . $index . '.ll';
        Files::write($path, $module->ir);
        $modules[] = $path;
    }
    $runtime = $second->backend->runtime;
    $output = $root . '/' . $mode;
    $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', '-flto=' . $mode,
        '--ld-path=' . Files::executable('/', 'ld.lld-18'), ...$modules, ...$runtime->modules_for($kind), '-o', $output]);
    Check::check(Family_Types_Test::execute($output) === [41, 'CCHCDhDD', ''], $mode . ' LTO composes ordinary and demanded package lifecycles');
}

// Selected joins validate provenance before canonical type adoption.
$tasks = array_map(static fn($result) => $result->task, $packages);
$join = new \load_runtime\Family_Preparation_Join($tasks, $first->inputs->runtime->base_catalog);
Check::check(count($join->join(array_reverse($packages))) === 3, 'Family type acceptance is independent of completion order');
Check::rejects(static fn() => $join->join([$packages[0]]), 'Incomplete');
Check::rejects(static fn() => $join->join([$packages[0], $packages[0]]), 'duplicate');
$stale = new \load_runtime\family_preparation_result(clone $tasks[0], $packages[0]->package, $packages[0]->type_id);
Check::rejects(static fn() => $join->join([$stale, $packages[1]]), 'stale');
$lease = \load_runtime\Runtime_Import::reserve(array_values($second->backend->runtime->packages()), $second->backend->runtime);
Check::check($lease->inputs === $second->backend->runtime, 'Final reservations retain exact accepted package identity');
$current = array_values($second->types->families)[0];
Check::rejects(static fn() => $bridge->prepare([$current->task], $current->package->base_catalog, $second->types->families), 'locked');
$lease->release();

// A valid replacement between early reads and final leases must not silently become this build's input.
$directory = $current->package->directory;
$manifest_path = $directory . '/package/manifest.json';
$pointer_path = $directory . '/current.json';
$manifest_source = file_get_contents($manifest_path);
$pointer_source = file_get_contents($pointer_path);
try {
    Files::write($manifest_path, $manifest_source . "\n");
    $pointer = json_decode($pointer_source, true, flags: JSON_THROW_ON_ERROR);
    $pointer['manifest_sha256'] = hash('sha256', $manifest_source . "\n");
    Files::write_json($pointer_path, $pointer);
    Check::rejects(static fn() => \load_runtime\Runtime_Import::reserve(array_values($second->backend->runtime->packages())), 'changed before final reservation');
}
finally {
    Files::write($manifest_path, $manifest_source);
    Files::write($pointer_path, $pointer_source);
}
foreach ($second->backend->runtime->packages() as $package) {
    $writer = new \runtime_preparation\Package_Reservation($package->directory);
    $writer->acquire();
    $writer->release();
}
Check::check(serialize($first) === $before, 'Rejected native preparation preserves accepted compiler snapshots');
echo "family types ok: source demands, shared preparation, generic forwarding, native lifecycle, final leases, joins and one new-specialization increment\n";

// A native family result uses the same caller-storage source ABI as ordinary runtime types.
Files::write($root . '/returned.phs', 'function returned(): bag<int> { $v bag<int>; return $v; } $a bag<int> = returned(); return 0;');
$returned = (new \compile\Compiler_Session(family_declarations: $declarations, family_preparer: $bridge))
    ->compile($root . '/returned.phs', $root . '/returned');
Check::check(Family_Types_Test::execute($root . '/returned') === [0, 'CDD', ''],
    'Prepared family construction from T&& may select native copying and leaves both objects destructible');
