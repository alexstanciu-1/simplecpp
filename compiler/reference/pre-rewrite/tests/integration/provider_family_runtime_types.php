<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/families/compiler_bridge.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use runtime_preparation\families as native;

final class Runtime_Argument_Test
{
    /** Observe the real native program, including how often scalar-producing calls execute. */
    public static function execute(string $binary, array $environment = []): array
    {
        $process = proc_open([$binary], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, env_vars: $environment === [] ? null : array_replace(getenv(), $environment));
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $err];
    }
}

$root = getcwd() . '/family-runtime-types';
$base = dirname(__DIR__, 2) . '/src-runtime-preparation';
Files::directory($root . '/project');
$config = Files::json($base . '/config.json');
$language = Files::json(dirname(__DIR__, 2) . '/language/named_types.json');
$language['types'][1]['struct_field'] = true;
$language['types'][] = ['name' => 'size_count', 'namespace' => '', 'kind' => 'integer', 'bit_width' => 64, 'signed' => false,
    'lifetime' => ['copy' => 'value', 'cleanup' => 'none'], 'integer_family' => 'simple_cpp.integer', 'comparison' => 'ordered'];
Files::write_json($root . '/language.json', $language);
$includes = array_map(static fn($path) => realpath(Files::path($base, $path)), $config['include_directories']);
$definition = Files::json($base . '/tests/families/catalog.json');
$definition['families'] = [$definition['families'][0]];
$definition['families'][0]['language_type'] = ['name' => 'vector', 'namespace' => ''];
foreach ($definition['types'] as &$type) {
    $type['language_type'] = ['name' => match ($type['id']) {
        'signed32' => 'int32', 'signed64' => 'int', 'byte' => 'uint8', 'size' => 'size_count', 'nothing' => 'void',
    }, 'namespace' => ''];
}
unset($type);
foreach ($definition['families'][0]['operations'] as &$operation) {
    $name = ['append_copy' => 'append', 'length' => 'length', 'read_copy' => 'at'][$operation['id']] ?? null;
    if ($name !== null) {
        $operation['expose_as'] = ['name' => $name, 'namespace' => ''];
    }
}
unset($operation);
// Lifecycle requirements are declared before specialization, independently of method coverage.
foreach (['copy_construct', 'copy_assign'] as $role) {
    $definition['families'][0]['lifecycle'][$role] = $role;
    $definition['families'][0]['operations'][] = ['id' => $role, 'kind' => $role, 'type' => '$self',
        'error_policy' => 'terminate', 'requires' => [['parameter' => 'element', 'operation' => $role]]];
}

$definition['families'][0]['lifecycle']['move_construct'] = 'move_construct';
$definition['families'][0]['operations'][] = ['id' => 'move_construct', 'kind' => 'move_construct',
    'type' => '$self', 'error_policy' => 'terminate'];


Files::write($root . '/parcel.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstddef>
#include <cstdlib>
#include <string>
#include <string_view>
namespace proof {
inline std::int64_t live = 0, created = 0, released = 0;
struct parcel {
    std::string* data;
    explicit parcel(std::string_view text): data(new std::string(text)) { ++live; ++created; }
    parcel(const parcel& other): data(new std::string(*other.data)) { ++live; ++created; }
    parcel& operator=(const parcel& other) { *data = *other.data; return *this; }
    ~parcel() { delete data; --live; ++released; }
};
inline std::int64_t length(const parcel& p) { return p.data->size(); }
inline std::int64_t balanced() { return live == 0 && created > 0 && created == released; }
inline std::size_t index_of(std::int64_t v) { if (v < 0) std::abort(); return static_cast<std::size_t>(v); }
}
CPP);
$includes[] = $root;
$catalog = native\Catalog::parse($definition, 'runtime_arguments');
$declarations = \load_runtime\Family_Adapter::expose(array_map(static fn($f) => $f->semantic, array_values($catalog->families)),
    native\Catalog::language_bindings($catalog));
$provider = new native\compiler_provider($catalog, $root . '/families', 'runtime_arguments',
    ['clang' => $config['clang'], 'target' => $config['target'], 'standard' => $config['standard'], 'include_directories' => $includes]);
$bridge = new native\Compiler_Bridge([$provider]);
Files::directory($root . '/definitions');
foreach (glob($base . '/definitions/*.json') as $path)
{
    $rows = Files::json($path);
    foreach ($rows['types'] as &$row) {
        if ($row['id'] === 'size') {
            $row['language_type'] = ['name' => 'size_count', 'namespace' => ''];
        }
    }
    unset($row);
    Files::write_json($root . '/definitions/' . basename($path), $rows);
}
$extra = ['schema_version' => 1, 'types' => [
    ['id' => 'parcel', 'cpp_name' => 'proof::parcel', 'header' => 'parcel.hpp', 'kind' => 'runtime_value', 'storage' => 'inline',
        'language_type' => ['name' => 'parcel', 'namespace' => ''],
        'lifecycle' => ['construct' => 'parcel.make', 'copy_construct' => 'parcel.copy', 'copy_assign' => 'parcel.assign', 'destroy' => 'parcel.destroy']],
], 'operations' => [
    ['id' => 'parcel.make', 'kind' => 'construct_from_bytes', 'type' => 'parcel', 'parameter_type' => 'bytes',
        'error_policy' => 'terminate', 'language_binding' => 'byte_literal', 'expose_as' => ['name' => 'parcel_from_bytes', 'namespace' => '']],
    ['id' => 'parcel.copy', 'kind' => 'copy_construct', 'type' => 'parcel', 'error_policy' => 'terminate'],
    ['id' => 'parcel.assign', 'kind' => 'copy_assign', 'type' => 'parcel', 'error_policy' => 'terminate'],
    ['id' => 'parcel.destroy', 'kind' => 'destroy', 'type' => 'parcel', 'error_policy' => 'terminate'],
    ['id' => 'parcel.length', 'kind' => 'free_function', 'cpp_name' => 'proof::length', 'header' => 'parcel.hpp',
        'parameters' => [['type' => 'parcel', 'passing' => 'const_address', 'borrow_scope' => 'call']],
        'result_type' => 'native_int', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'parcel_length', 'namespace' => '']],
    ['id' => 'balanced', 'kind' => 'free_function', 'cpp_name' => 'proof::balanced', 'header' => 'parcel.hpp',
        'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'balanced', 'namespace' => '']],
    ['id' => 'index', 'kind' => 'free_function', 'cpp_name' => 'proof::index_of', 'header' => 'parcel.hpp',
        'parameters' => ['native_int'], 'result_type' => 'size', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'index_of', 'namespace' => '']],
]];
Files::write_json($root . '/definitions/parcel.json', $extra);
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/ordinary';
$config['include_directories'] = $includes;
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');

// Managed ordinary types use the same import path as nested family instances.
$source = <<<'PHS'
function texts($early bool): vector<string> {
    $text string = "first";
    $list vector<string>;
    $list->append($text);
    $replacement string = "changed";
    $text = $replacement;
    $copy vector<string> = $list;
    $list = $copy;
    if ($early) { return $list; }
    $list->append($text);
    return $list;
}
function exercise(): int {
    $early vector<string> = texts(true);
    $normal vector<string> = texts(false);
    $first string = $early->at(index_of(0));
    $last string = $normal->at(index_of(1));
    echo $first, ":", $last;
    $value parcel = "abc";
    $list vector<parcel>;
    $list->append($value);
    $replacement parcel = "longer";
    $value = $replacement;
    $copy vector<parcel> = $list;
    $list = $copy;
    $read parcel = $list->at(index_of(0));
    return parcel_length($read);
}
PHS;
$path = $root . '/project/functions.phs';
Files::write($path, $source);
Files::write($root . '/project/main.phs', '$answer int = exercise(); if (balanced() < 1) { return 99; } return $answer;');
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$session = new \compile\Compiler_Session(type_catalog_path: $root . '/language.json', runtime_package_path: $root . '/ordinary',
    family_declarations: $declarations, family_preparer: $bridge);
$first = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Runtime_Argument_Test::execute($root . '/program') === [3, 'first:changed', ''],
    'Ordinary managed types copy independent contents through native families and release observed allocations');
Check::check(count($first->types->families) === 2, 'Two ordinary argument types demand two family specializations');
$before = serialize($first);
Check::edit($path, str_replace('"abc"', '"abcd"', $source));
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(!$second->inputs->context->full_rebuild && (Runtime_Argument_Test::execute($root . '/program') === [4, 'first:changed', ''])
    && (serialize($first) === $before), 'One body increment changes behavior without mutating accepted snapshots');
foreach ($first->types->families as $id => $result) {
    Check::check($second->types->families[$id]->package === $result->package, 'Ordinary argument packages reuse unchanged native contracts');
}
$packages = $second->backend->runtime->packages();
foreach ($second->types->families as $result) {
    foreach ($result->package->bindings->imports as $local => $import) {
        Check::check($result->package->type_for($local)->language_type === $packages[$import->provider]->type_for($import->type_id)->language_type,
            'Ordinary argument identity remains owned by its accepted runtime package');
    }
}

// Runtime preparation produces the descriptions for every eligible configured type.
$ordinary = $packages[$config['provider']];
$recipe = \runtime_preparation\Native_Types::from_package($ordinary->directory, $ordinary->provider, 'parcel');
Check::check($recipe->baseline, 'Explicit copy and cleanup contracts establish argument eligibility');
$restricted = $extra['types'][0];
unset($restricted['lifecycle']['copy_assign']);
$ineligible = \runtime_preparation\Native_Types::export(['parcel' => $restricted], $ordinary->provider, $recipe->context)['parcel'];
Check::rejects(static fn() => native\Requests::arguments($catalog, $catalog->families['sequence'], [$ineligible]), 'copyable_value');
$foreign = json_decode(json_encode($recipe, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
$foreign['identity']['provider'] = 'another_provider';
Check::rejects(static fn() => \runtime_preparation\Native_Types::read($foreign), 'Invalid prepared native argument');
$context = $provider->configuration;
$context['standard'] = $context['standard'] === 'c++20' ? 'c++23' : 'c++20';
$preparation = new native\Preparation(new native\Store($root . '/context-proof'));
Check::rejects(static fn() => $preparation->select(new native\specialization_request($catalog, 'sequence', [$recipe], [],
    'context-proof', $context)), 'Native argument preparation context mismatch');
$tasks = array_map(static fn($r) => $r->task, array_values($second->types->families));
$header = Files::read($root . '/parcel.hpp');
try {
    Files::write($root . '/parcel.hpp', $header . "\n// changed after acceptance\n");
    Check::rejects(static fn() => $bridge->prepare($tasks, $ordinary->base_catalog, $second->types->families, [$ordinary]),
        'Accepted native argument headers changed');
}
finally {
    Files::write($root . '/parcel.hpp', $header);
}

// The same accepted bridge modules link under O1 and ThinLTO.
$modules = [];
foreach ($second->llvm->modules as $index => $module) {
    $file = $root . '/caller-' . $index . '.ll';
    Files::write($file, $module->ir);
    $modules[] = $file;
}
$runtime = $second->backend->runtime;
$tools = new \runtime_preparation\Clang_Toolchain($config, $root);
foreach (['ordinary' => \load_runtime\runtime_module_kind::ordinary, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
{
    $flags = $mode === 'ordinary' ? [] : ['-flto=thin', '--ld-path=' . Files::executable('/', 'ld.lld-18')];
    $output = $root . '/optimized-' . $mode;
    $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', ...$flags, ...$modules,
        ...$runtime->modules_for($kind), '-o', $output]);
    Check::check(Runtime_Argument_Test::execute($output) === [4, 'first:changed', ''], 'Managed native arguments at O1: ' . $mode);
}
echo "ok\n";
