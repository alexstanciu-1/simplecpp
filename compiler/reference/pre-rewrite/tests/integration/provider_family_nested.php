<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/families/compiler_bridge.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use runtime_preparation\families as native;

final class Nested_Family_Test
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

$root = getcwd() . '/family-nested';
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

// A second, two-argument family owns an observable heap token and copies its fields.
// Its names and operations are fixture metadata, never compiler dispatch conditions.
Files::write($root . '/parcel.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstddef>
#include <cstdlib>
#include <utility>
#include <optional>
namespace proof {
inline std::int64_t live_tokens = 0;
inline std::int64_t created_tokens = 0;
inline std::int64_t released_tokens = 0;
inline int* acquire() { ++live_tokens; ++created_tokens; return new int(7); }
inline void release(int* p) { if (!p || *p != 7) std::abort(); delete p; --live_tokens; ++released_tokens; }
template<class A, class B> struct parcel {
    std::optional<A> first; std::optional<B> second; int* token;
    parcel(): token(acquire()) {}
    parcel(const parcel& p): first(p.first), second(p.second), token(acquire()) {}
    parcel& operator=(const parcel& p) { first = p.first; second = p.second; return *this; }
    ~parcel() { release(token); }
};
template<class P, class A> A first(const P& p) { if (!p.first) std::abort(); return *p.first; }
template<class P, class B> B second(const P& p) { if (!p.second) std::abort(); return *p.second; }
template<class P, class A> void set_first(P& p, const A& v) { p.first.emplace(v); }
template<class P, class B> void set_second(P& p, const B& v) { p.second.emplace(v); }
inline std::size_t index_of(std::int64_t v) { if (v < 0) std::abort(); return static_cast<std::size_t>(v); }
inline std::int64_t balanced() { return live_tokens == 0 && created_tokens > 0 && created_tokens == released_tokens; }
}
CPP);
$includes[] = $root;
$pair = ['id' => 'parcel', 'cpp_name' => 'proof::parcel', 'header' => 'parcel.hpp',
    'language_type' => ['name' => 'parcel', 'namespace' => ''],
    'parameters' => [['name' => 'first', 'contract' => 'copyable_value'], ['name' => 'second', 'contract' => 'copyable_value']],
    'lifecycle' => ['construct' => 'create', 'destroy' => 'destroy', 'copy_construct' => 'copy_construct', 'copy_assign' => 'copy_assign'],
    'operations' => [
        ['id' => 'create', 'kind' => 'construct', 'type' => '$self', 'error_policy' => 'terminate',
            'parameters' => []],
    ]];
foreach (['destroy', 'copy_construct', 'copy_assign'] as $role) {
    $pair['operations'][] = ['id' => $role, 'kind' => $role, 'type' => '$self', 'error_policy' => 'terminate',
        'requires' => [['parameter' => 'first', 'operation' => $role], ['parameter' => 'second', 'operation' => $role]]];
}
foreach (['first', 'second'] as $field)
{
    $pair['operations'][] = ['id' => 'set_' . $field, 'kind' => 'free_function', 'cpp_name' => 'proof::set_' . $field,
        'header' => 'parcel.hpp', 'cpp_template_arguments' => ['$self', '$' . $field],
        'parameters' => [['type' => '$self', 'passing' => 'mutable_address', 'borrow_scope' => 'call'],
            ['type' => '$' . $field, 'passing' => 'const_address', 'borrow_scope' => 'call']],
        'result_type' => 'nothing', 'receiver' => 0, 'error_policy' => 'terminate',
        'effects' => [['kind' => 'invalidate_elements', 'receiver' => 0]],
        'expose_as' => ['name' => 'set_' . $field, 'namespace' => ''],
        'requires' => [['parameter' => $field, 'operation' => 'copy_construct']]];
    $pair['operations'][] = ['id' => $field, 'kind' => 'free_function', 'cpp_name' => 'proof::' . $field,
        'header' => 'parcel.hpp', 'cpp_template_arguments' => ['$self', '$' . $field],
        'parameters' => [['type' => '$self', 'passing' => 'const_address', 'borrow_scope' => 'call']],
        'result_type' => '$' . $field, 'receiver' => 0, 'error_policy' => 'terminate',
        'expose_as' => ['name' => $field, 'namespace' => ''],
        'requires' => [['parameter' => $field, 'operation' => 'copy_construct']]];
}
$definition['families'][] = $pair;
$catalog = native\Catalog::parse($definition, 'nested_proof');
$declarations = \load_runtime\Family_Adapter::expose(array_map(static fn($f) => $f->semantic, array_values($catalog->families)),
    native\Catalog::language_bindings($catalog));
$provider = new native\compiler_provider($catalog, $root . '/generated', 'nested_proof',
    ['clang' => $config['clang'], 'target' => $config['target'], 'standard' => $config['standard'], 'include_directories' => $includes]);
$bridge = new native\Compiler_Bridge([$provider]);

Files::directory($root . '/ordinary-definitions');
Files::write_json($root . '/ordinary-definitions/defs.json', ['schema_version' => 1,
    'types' => [$definition['types'][1], $definition['types'][3]],
    'operations' => [
        ['id' => 'index', 'kind' => 'free_function', 'cpp_name' => 'proof::index_of', 'header' => 'parcel.hpp',
            'parameters' => ['signed64'], 'result_type' => 'size', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'index_of', 'namespace' => '']],
        ['id' => 'balanced', 'kind' => 'free_function', 'cpp_name' => 'proof::balanced', 'header' => 'parcel.hpp',
            'parameters' => [], 'result_type' => 'signed64', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'balanced', 'namespace' => '']],
    ]]);
$ordinary = $config;
$ordinary['provider'] = 'nested_observer';
$ordinary['definitions_directory'] = $root . '/ordinary-definitions';
$ordinary['include_directories'] = $includes;
$ordinary['output_directory'] = $root . '/ordinary';
Files::write_json($root . '/ordinary.json', $ordinary);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/ordinary.json');
$source = <<<'PHS'
function nested($early bool): vector<vector<int>> {
    $inner vector<int>;
    $inner->append(31);
    $outer vector<vector<int>>;
    $outer->append($inner);
    $inner->append(99);
    $copy vector<vector<int>> = $outer;
    $outer = $copy;
    $outer = $outer;
    if ($early) { return $outer; }
    $outer->append($inner);
    return $outer;
}
function parcels(): vector<parcel<vector<int>, int>> {
    $inner vector<int>;
    $inner->append(7);
    $item parcel<vector<int>, int>;
    $item->set_first($inner);
    $item->set_second(4);
    $list vector<parcel<vector<int>, int>>;
    $list->append($item);
    $inner->append(99);
    $copy vector<parcel<vector<int>, int>> = $list;
    $list = $copy;
    return $list;
}
function exercise(): int {
    $early vector<vector<int>> = nested(true);
    $normal vector<vector<int>> = nested(false);
    $one vector<int> = $early->at(index_of(0));
    if (index_of(1) < $one->length()) { return 101; }
    $two vector<int> = $normal->at(index_of(1));
    if ($two->length() < index_of(2)) { return 102; }
    $list vector<parcel<vector<int>, int>> = parcels();
    $item parcel<vector<int>, int> = $list->at(index_of(0));
    $inner vector<int> = $item->first();
    if (index_of(1) < $inner->length()) { return 103; }
    return $one->at(index_of(0)) + $two->at(index_of(0)) + $inner->at(index_of(0)) + $item->second();
}
PHS;
$path = $root . '/project/functions.phs';
Files::write($path, $source);
Files::write($root . '/project/main.phs', '$answer int = exercise(); if (balanced() < 1) { return 104; } return $answer;');
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$session = new \compile\Compiler_Session(type_catalog_path: $root . '/language.json', runtime_package_path: $root . '/ordinary',
    family_declarations: $declarations, family_preparer: $bridge);
$first = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Nested_Family_Test::execute($root . '/program') === [73, '', ''],
    'Nested native types copy independent contents, return through both exits and release every observed allocation');
Check::check(count($first->types->families) === 4, 'Four demanded specializations, independent of call count');
$before = serialize($first);
Check::edit($path, str_replace('append(31)', 'append(32)', $source));
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(!$second->inputs->context->full_rebuild && (Nested_Family_Test::execute($root . '/program') === [75, '', ''])
    && (serialize($first) === $before), 'One body increment preserves accepted snapshots and changes observable results');
foreach ($first->types->families as $id => $prepared) {
    Check::check($second->types->families[$id]->package === $prepared->package, 'Nested packages reuse exact contracts after a body edit');
}

// A referenced argument is exactly the canonical owner type, not a second imported definition.
$packages = $second->backend->runtime->packages();
$outer = null;
foreach ($second->types->families as $prepared)
{
    foreach ($prepared->package->bindings->imports as $local => $import) {
        Check::check($prepared->package->type_for($local)->language_type === $packages[$import->provider]->type_for($import->type_id)->language_type,
            'Nested signatures share their exact accepted argument definition');
        $outer = $prepared;
    }
}
Check::check($outer !== null, 'The proof crosses a prepared type import boundary');
$fixed = serialize($second);
$tasks = array_map(static fn($prepared) => $prepared->task, array_values($second->types->families));
$join = new \load_runtime\Family_Preparation_Join($tasks, $outer->package->base_catalog);
Check::check($join->join(array_reverse(array_values($second->types->families))) === $second->types->families,
    'Selected family results accept in reverse completion order');
Check::rejects(static fn() => $join->join([$outer]), 'Incomplete');
$lower_tasks = array_map(static fn($body) => new \lower\lowering_input($body, $second->backend), $second->lifetimes->bodies());
$lower_results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), $lower_tasks);
$lowered = (new \lower\Lowering_Join($second->lifetimes, $second->backend, new \lower\Lowered_Set(), $lower_tasks))->join(array_reverse($lower_results));
Check::check(($lowered->to_json() === $second->lowered->to_json()) && (serialize($second) === $fixed),
    'Nested ownership uses unchanged fixed-input lowering tasks and private joins');

// Native optimization and instrumentation use the accepted modules without another compiler path.
$modules = [];
foreach ($second->llvm->modules as $index => $module) {
    $file = $root . '/caller-' . $index . '.ll';
    Files::write($file, $module->ir);
    $modules[] = $file;
}
$tools = new \runtime_preparation\Clang_Toolchain($config, $base);
$runtime = $second->backend->runtime;
foreach (['ordinary' => \load_runtime\runtime_module_kind::ordinary, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
{
    $flags = $mode === 'ordinary' ? [] : ['-flto=thin', '--ld-path=' . Files::executable('/', 'ld.lld-18')];
    $output = $root . '/optimized-' . $mode;
    $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', ...$flags, ...$modules,
        ...$runtime->modules_for($kind), '-o', $output]);
    Check::check(Nested_Family_Test::execute($output) === [75, '', ''], 'Nested ownership and native symbol composition at O1: ' . $mode);
}
// LeakSanitizer cannot run under the development harness's ptrace boundary.
$tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', '-fsanitize=address', ...$modules,
    ...$runtime->modules_for(\load_runtime\runtime_module_kind::ordinary), '-o', $root . '/sanitized']);
Check::check(Nested_Family_Test::execute($root . '/sanitized', ['ASAN_OPTIONS' => 'detect_leaks=0']) === [75, '', ''],
    'Address sanitizer reports no invalid accesses; explicit token counters prove observed allocation balance');

// Boundary failures must reject before a false concrete type reaches checking or lowering.
$package = $outer->package;
$local = array_key_first($package->bindings->imports);
$original = $package->bindings->imports[$local];
foreach (['identity', 'layout', 'target', 'missing'] as $failure)
{
    $imports = $package->bindings->imports;
    $type = $original->type;
    if ($failure === 'layout') {
        $storage = $type->storage;
        $type = new \load_runtime\runtime_type($type->id,
            new \load_runtime\runtime_storage($storage->kind, $storage->size_bytes + $storage->alignment_bytes, $storage->alignment_bytes),
            $type->integer_bits, $type->signed, $type->language_type);
    }
    $imports[$local] = new \load_runtime\runtime_type_import($failure === 'identity' ? 'wrong_owner' : $original->provider,
        $original->type_id, $type, $failure === 'target' ? 'wrong_target' : $original->target_triple, $original->data_layout);
    if ($failure === 'missing') {
        unset($imports[$local]);
    }
    $bindings = new \load_runtime\package_bindings($package->bindings->types, $package->bindings->callables, $imports);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($package->directory, $package->base_catalog, bindings: $bindings), 'Native type import');
}
$incomplete = array_values(array_filter($packages, static fn($p) => $p->provider !== $original->provider));
Check::rejects(static fn() => \load_runtime\Runtime_Import::reserve($incomplete), 'Missing or incompatible runtime type import owner');
Check::check((serialize($first) === $before) && (serialize($second) === $fixed), 'Rejected imports preserve accepted compiler snapshots');

// Exact identity survives dependency revisions; readiness/reuse follows the separate contract.
$inner_package = $packages[$original->provider];
$recipe = \runtime_preparation\Native_Types::from_package($inner_package->directory, $inner_package->provider, $original->type_id);
$twice = native\Arguments::resolve($catalog, [$recipe, $recipe]);
Check::check($twice[0] === $twice[1], 'Repeated argument positions share one local native import row');
$probe = new native\Preparation(new native\Store($root . '/dependency-selection'));
$request = new native\specialization_request($catalog, 'sequence', [$recipe], [], 'dependency-proof', $provider->configuration);
$selected = $probe->select($request);
$selected->reservation->release();
$changed = new \runtime_preparation\native_type($recipe->identity, $recipe->definition, $recipe->baseline, $recipe->headers,
    $recipe->declarations, $recipe->contract . ' revised', $recipe->context);
$replacement = $probe->select(new native\specialization_request($catalog, 'sequence', [$changed], [], 'dependency-proof', $provider->configuration));
try {
    Check::check(($replacement->key === $selected->key) && ($replacement->contract !== $selected->contract),
        'A changed dependency invalidates preparation without changing specialization identity');
}
finally {
    $replacement->reservation->release();
}
$ineligible = new \runtime_preparation\native_type($recipe->identity, $recipe->definition, false, $recipe->headers,
    $recipe->declarations, $recipe->contract, $recipe->context);
Check::rejects(static fn() => native\Requests::arguments($catalog, $catalog->families['sequence'], [$ineligible]), 'copyable_value');
Check::rejects(static fn() => native\Arguments::resolve($catalog, [$recipe, $ineligible]), 'Conflicting native argument contracts');

$header_path = $root . '/parcel.hpp';
$header_source = Files::read($header_path);
try {
    Files::write($header_path, $header_source . "\n// changed after accepting the inner native implementation\n");
    Check::rejects(static fn() => $bridge->prepare([$outer->task], $package->base_catalog, $second->types->families),
        'Accepted native argument headers changed before selection');
}
finally {
    Files::write($header_path, $header_source);
}
Check::check((serialize($first) === $before) && (serialize($second) === $fixed), 'Header race rejection preserves accepted snapshots');
echo "nested families ok: native vectors, two-argument family, exact imported types, owned results, balanced cleanup, joins, reuse and O1/ThinLTO\n";
