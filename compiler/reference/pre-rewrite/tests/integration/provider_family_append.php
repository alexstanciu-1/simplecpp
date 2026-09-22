<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/families/compiler_bridge.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use runtime_preparation\families as native;

final class Family_Append_Test
{
    /** Observe the real native program, including how often scalar-producing calls execute. */
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

$root = getcwd() . '/family-append';
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
$catalog = native\Catalog::parse($definition, 'simple_cpp');
$declarations = \load_runtime\Family_Adapter::expose([$catalog->families['sequence']->semantic], native\Catalog::language_bindings($catalog));
$bridge = new native\Compiler_Bridge([new native\compiler_provider($catalog, $root . '/generated', 'append-proof',
    ['clang' => $config['clang'], 'target' => $config['target'], 'standard' => $config['standard'], 'include_directories' => $includes])]);

// An ordinary provider exercises the same scalar-address importer without any family declarations.
Files::directory($root . '/ordinary-definitions');
Files::write($root . '/ordinary.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdio>
#include <cstddef>
#include <cstdlib>
inline std::int32_t small_value() { return 7; }
inline std::size_t index_of(std::int64_t value) { if (value < 0) std::abort(); return static_cast<std::size_t>(value); }
inline std::int64_t next_value() { std::putchar('N'); return 41; }
inline std::int64_t same_address(const std::int64_t& a, const std::int64_t& b) { return &a == &b; }
CPP);
Files::write_json($root . '/ordinary-definitions/defs.json', ['schema_version' => 1,
    'types' => [$definition['types'][0], $definition['types'][1], $definition['types'][3]],
    'operations' => [
        ['id' => 'small', 'kind' => 'free_function', 'cpp_name' => 'small_value', 'header' => 'ordinary.hpp',
            'parameters' => [], 'result_type' => 'signed32', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'small_value', 'namespace' => '']],
        ['id' => 'index', 'kind' => 'free_function', 'cpp_name' => 'index_of', 'header' => 'ordinary.hpp',
            'parameters' => ['signed64'], 'result_type' => 'size', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'index_of', 'namespace' => '']],
        ['id' => 'next', 'kind' => 'free_function', 'cpp_name' => 'next_value', 'header' => 'ordinary.hpp',
            'parameters' => [], 'result_type' => 'signed64', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'next_value', 'namespace' => '']],
        ['id' => 'same', 'kind' => 'free_function', 'cpp_name' => 'same_address', 'header' => 'ordinary.hpp',
            'parameters' => [
                ['type' => 'signed64', 'passing' => 'const_address', 'borrow_scope' => 'call'],
                ['type' => 'signed64', 'passing' => 'const_address', 'borrow_scope' => 'call']],
            'result_type' => 'signed64', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'same_address', 'namespace' => '']],
    ]]);
$ordinary = $config;
$ordinary['provider'] = 'ordinary';
$ordinary['definitions_directory'] = $root . '/ordinary-definitions';
$ordinary['include_directories'] = [$root];
$ordinary['output_directory'] = $root . '/ordinary';
Files::write_json($root . '/ordinary.json', $ordinary);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/ordinary.json');
Files::write($root . '/project/functions.phs', 'struct sample { public int $value; } template<typename T> function build($value T): T { $v vector<T>; $v->append($value); return $v->at(index_of(0)); }');
$source = <<<'PHS'
$x int = 5; $small int32 = small_value(); $r sample; $r->value = 4; $v vector<int>;
$v->append($x); $v->append(9); $v->append($x + 2); $v->append(next_value()); $v->append($small); $v->append($r->value);
$copied vector<int> = $v;
$target vector<int>;
$target->append($x);
$target = $copied;
$target = $target;
$copied->append($x);
if ($target->length() < $v->length()) { return 101; }
if ($v->length() < $target->length()) { return 102; }
if ($copied->length() < index_of(7)) { return 103; }
if (index_of(7) < $copied->length()) { return 104; }
$built int = build<int32>($small);
return $target->at(index_of(0)) + $v->at(index_of(1)) + $v->at(index_of(2)) + $v->at(index_of(3)) + $v->at(index_of(4)) + $v->at(index_of(5)) + same_address($x, $x) + same_address(1, 1) + $built;
PHS;
$path = $root . '/project/main.phs';
Files::write($path, $source);
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$session = new \compile\Compiler_Session(type_catalog_path: $root . '/language.json', runtime_package_path: $root . '/ordinary', family_declarations: $declarations, family_preparer: $bridge);
$first = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Family_Append_Test::execute($root . '/program') === [81, 'N', ''],
    'Real Simple C++ vector copies local, literal, expression, call, widened scalar and field arguments; generic forwarding works');
$entry = $first->lowered->for_callable($first->types->entry->symbol->symbol_id);
$scalar_temporaries = [];
foreach ($entry->slots as $slot) {
    if (($slot->source_local_id === 0) && ($entry->definition_for($slot->type_id)->representation->kind === \type_model\representation_kind::integer)) {
        $scalar_temporaries[] = $entry->input->analysis->body->values[$slot->source_value_id - 1]->kind->value;
    }
}
sort($scalar_temporaries);
$expected_temporaries = ['integer_literal', 'integer_literal', 'integer_literal', 'operation', 'call_result', 'conversion'];
sort($expected_temporaries);
Check::check($scalar_temporaries === $expected_temporaries,
    'Only scalar rvalues spill: local and projected scalar places retain their own addresses');
$before = serialize($first);
Check::edit($path, str_replace('append(9)', 'append(10)', $source));
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(!$second->inputs->context->full_rebuild && (Family_Append_Test::execute($root . '/program') === [82, 'N', ''])
    && (serialize($first) === $before), 'One scalar-borrow body increment executes and preserves the earlier snapshot');
foreach ($first->types->families as $id => $prepared) {
    Check::check($second->types->families[$id]->package === $prepared->package, 'Unchanged native coverage reuses its exact package on the body increment');
}

$fixed_inputs = serialize($second);
// Fixed lowering tasks use the same scalar-storage path and accept results in arbitrary completion order.
$tasks = array_map(static fn($body) => new \lower\lowering_input($body, $second->backend), $second->lifetimes->bodies());
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), $tasks);
$joined = (new \lower\Lowering_Join($second->lifetimes, $second->backend, new \lower\Lowered_Set(), $tasks))->join(array_reverse($results));
Check::check(($joined->to_json() === $second->lowered->to_json()) && (serialize($first) === $before) && (serialize($second) === $fixed_inputs),
    'Private scalar-storage output joins deterministically without changing retained inputs');
// LLVM optimizations and LTO consume the same scalar-address and native lifecycle contracts.
$modules = [];
foreach ($second->llvm->modules as $index => $module) {
    $file = $root . '/caller-' . $index . '.ll';
    Files::write($file, $module->ir);
    $modules[] = $file;
}
$tools = new \runtime_preparation\Clang_Toolchain($config, $base);
foreach (['ordinary' => \load_runtime\runtime_module_kind::ordinary,
    'full' => \load_runtime\runtime_module_kind::full_lto, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
{
    $runtime = $second->backend->runtime;
    $flags = $mode === 'ordinary' ? [] : ['-flto=' . $mode, '--ld-path=' . Files::executable('/', 'ld.lld-18')];
    $output = $root . '/optimized-' . $mode;
    $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O1', ...$flags, ...$modules,
        ...$runtime->modules_for($kind), '-o', $output]);
    Check::check(Family_Append_Test::execute($output) === [82, 'N', ''], 'Optimized scalar borrowing: ' . $mode);
}

// New failing requests do not widen source references or authorize incompatible implicit conversions.
foreach ([
    ['$v vector<int>; $v->append(true); return 0;', 'Unsupported implicit argument conversion'],
    ['function rejected(const int &$x): int { return $x; } return 0;', 'Source reference parameters require'],
] as [$invalid, $message]) {
    Files::write($root . '/negative.phs', $invalid);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(type_catalog_path: $root . '/language.json',
        runtime_package_path: $root . '/ordinary', family_declarations: $declarations, family_preparer: $bridge))
        ->compile($root . '/negative.phs'), $message);
    Check::check($error instanceof \diagnostics\Source_Error, 'Invalid scalar-borrow use remains a source diagnostic');
}

// Even an otherwise authenticated package must not broaden this slice to mutable scalar addresses.
$package = $root . '/ordinary/package';
$metadata_source = Files::read($package . '/metadata.json');
$manifest_source = Files::read($package . '/manifest.json');
$pointer_source = Files::read($root . '/ordinary/current.json');
try
{
    $metadata = json_decode($metadata_source, true, flags: JSON_THROW_ON_ERROR);
    foreach ($metadata['operations'] as &$row) {
        if ($row['id'] === 'same') {
            $row['parameters'][0]['passing'] = 'mutable_address';
        }
    }
    unset($row);
    Files::write_json($package . '/metadata.json', $metadata);
    $manifest = json_decode($manifest_source, true, flags: JSON_THROW_ON_ERROR);
    $manifest['artifacts']['metadata.json'] = hash_file('sha256', $package . '/metadata.json');
    Files::write_json($package . '/manifest.json', $manifest);
    $pointer = json_decode($pointer_source, true, flags: JSON_THROW_ON_ERROR);
    $pointer['manifest_sha256'] = hash_file('sha256', $package . '/manifest.json');
    Files::write_json($root . '/ordinary/current.json', $pointer);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($root . '/ordinary', $second->inputs->runtime->base_catalog),
        'Unsupported runtime parameter passing or language type');
}
finally {
    Files::write($package . '/metadata.json', $metadata_source);
    Files::write($package . '/manifest.json', $manifest_source);
    Files::write($root . '/ordinary/current.json', $pointer_source);
}
echo "family append ok: real runtime vector, scalar places and temporaries, once-only evaluation, conversion, generic forwarding, copy/assignment/self-assignment and one increment\n";
