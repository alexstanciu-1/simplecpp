<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use type_model\conversion_purpose;
use check_bodies\conversion_request;
use check_bodies\conversion_form;
use check_bodies\Conversion_Resolver;

final class Runtime_Conversion_Test
{
    /** Capture native output and lifecycle diagnostics without interpreting their bytes. */
    public static function execute(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start conversion probe');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

    /** Refresh integrity fields so malformed-package probes reach semantic validation. */
    public static function metadata(string $directory, array $metadata): void
    {
        $manifest = Files::json($directory . '/package/manifest.json');
        Files::write_json($directory . '/package/metadata.json', $metadata);
        $manifest['artifacts']['metadata.json'] = hash_file('sha256', $directory . '/package/metadata.json');
        Files::write_json($directory . '/package/manifest.json', $manifest);
        $pointer = Files::json($directory . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $directory . '/package/manifest.json');
        Files::write_json($directory . '/current.json', $pointer);
    }
}

$root = getcwd() . '/conversion-runtime';
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
foreach (glob($preparation . '/definitions/*.json') as $path) {
    Files::write($root . '/definitions/' . basename($path), Files::read($path));
}
Files::write($root . '/sealed.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <string>
#include "scpp/string_t.hpp"
namespace probe {
inline int live = 0;
class alignas(64) sealed {
    const sealed *identity;
    std::int64_t *owned;
public:
    explicit sealed(std::int64_t value) : identity(this), owned(new std::int64_t(value)) {
        ++live; std::fprintf(stderr, "C:%lld\n", static_cast<long long>(value));
    }
    sealed(const sealed&) = delete;
    sealed(sealed&&) = delete;
    ~sealed() {
        if (identity != this) std::abort();
        std::fprintf(stderr, "D:%lld\n", static_cast<long long>(*owned)); delete owned; --live;
    }
    std::int64_t read() const { if (identity != this) std::abort(); return *owned; }
};
inline sealed make(std::int64_t value) { return sealed(value); }
inline scpp::string_t format(std::int64_t value) { return scpp::string_t("[" + std::to_string(value) + "]"); }
inline sealed plus(const sealed& value, std::int64_t delta) { return sealed(value.read() + delta); }
inline void display(const sealed& value) { std::printf("[%lld]", static_cast<long long>(value.read())); }
inline std::int64_t count() { return live; }
inline std::int64_t minimum() { return std::numeric_limits<std::int64_t>::min(); }
inline std::int64_t maximum() { return std::numeric_limits<std::int64_t>::max(); }
}
CPP);
$definitions = ['schema_version' => 1, 'types' => [
    ['id' => 'sealed', 'kind' => 'runtime_value', 'cpp_name' => 'probe::sealed', 'header' => 'sealed.hpp', 'storage' => 'inline',
        'language_type' => ['name' => 'sealed', 'namespace' => ''], 'lifecycle' => ['destroy' => 'sealed.destroy']],
], 'operations' => [
    ['id' => 'sealed.destroy', 'kind' => 'destroy', 'type' => 'sealed', 'error_policy' => 'terminate'],
]];
$definitions['operations'][] = ['id' => 'sealed.make', 'kind' => 'free_function', 'cpp_name' => 'probe::make',
    'header' => 'sealed.hpp', 'parameters' => ['native_int'], 'result_type' => 'sealed', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'sealed_make', 'namespace' => ''], 'conversion_purpose' => 'explicit_cast'];
$definitions['operations'][] = ['id' => 'integer.format', 'kind' => 'free_function', 'cpp_name' => 'probe::format',
    'header' => 'sealed.hpp', 'parameters' => ['native_int'], 'result_type' => 'string', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'format_integer', 'namespace' => ''], 'conversion_purpose' => 'text'];
$borrow = ['type' => 'sealed', 'passing' => 'const_address', 'borrow_scope' => 'call'];
$definitions['operations'][] = ['id' => 'sealed.plus', 'kind' => 'free_function', 'cpp_name' => 'probe::plus', 'header' => 'sealed.hpp',
    'parameters' => [$borrow, 'native_int'], 'result_type' => 'sealed', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'sealed_plus', 'namespace' => '']];
$definitions['operations'][] = ['id' => 'sealed.display', 'kind' => 'free_function', 'cpp_name' => 'probe::display', 'header' => 'sealed.hpp',
    'parameters' => [$borrow], 'result_type' => 'void', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'sealed_display', 'namespace' => ''], 'language_binding' => 'echo'];
foreach (['count', 'minimum', 'maximum'] as $operation) {
    $definitions['operations'][] = ['id' => 'sealed.' . $operation, 'kind' => 'free_function', 'cpp_name' => 'probe::' . $operation,
        'header' => 'sealed.hpp', 'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate',
        'expose_as' => ['name' => 'sealed_' . $operation, 'namespace' => '']];
}
Files::write_json($root . '/definitions/sealed.json', $definitions);
$config = Files::json($preparation . '/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/generated';
$config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($preparation, $path)),
    $config['include_directories']), $root];
Files::write_json($root . '/config.json', $config);
$prepared = (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$manifest_data = Files::json($prepared['manifest']);
$metadata = Files::json(dirname($prepared['manifest']) . '/metadata.json');

// A noncopyable, nonmovable returned object proves direct owned-result construction.
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'run(1); run(0); stable(); return sealed_count();');
Files::write($root . '/project/src/stable.phs', 'function stable(): void { echo string_from_int(9); }');
$source = <<<'PHS'
function run($flag int): void
{
    $message string = string_from_int(42);
    $copy string = $message;
    echo $message, "/", $copy, "/", string_from_int(runtime_abs(7)), "\n";
    echo string_from_int(sealed_minimum()), "/", string_from_int(sealed_maximum()), "\n";
    $owned sealed = sealed_make(10);
    echo $owned, sealed_make(20), sealed_plus($owned, 5);
    if ($flag) {
        echo format_integer(30);
        return;
    }
    echo sealed_make(40);
}
PHS;
$path = $root . '/project/src/body.phs';
Files::write($path, $source);
Files::write($root . '/oracle.cpp', <<<'CPP'
#include "scpp/lang/php.hpp"
#include "sealed.hpp"
using scpp::string_t; using scpp::php::echo_one;
static string_t convert(std::int64_t value) { return scpp::cast<string_t>(value); }
static void run(std::int64_t flag) {
    string_t message = convert(CONVERSION_VALUE); string_t copy = message;
    echo_one(message); echo_one(string_t("/")); echo_one(copy); echo_one(string_t("/")); echo_one(convert(7)); echo_one(string_t("\n"));
    echo_one(convert(probe::minimum())); echo_one(string_t("/")); echo_one(convert(probe::maximum())); echo_one(string_t("\n"));
    probe::sealed owned = probe::make(10);
    probe::display(owned); probe::display(probe::make(20)); probe::display(probe::plus(owned, 5));
    if (flag) { echo_one(probe::format(30)); return; }
    probe::display(probe::make(40));
}
int main() { run(1); run(0); echo_one(convert(9)); return probe::count(); }
CPP);
$oracles = [];
foreach ([42, 43] as $value) {
    [$status, , $error] = Runtime_Conversion_Test::execute([$manifest_data['link_driver']['executable'], '--driver-mode=g++',
        '--target=' . $manifest_data['target']['triple'], '-std=c++23', '-I' . $config['include_directories'][0],
        '-DCONVERSION_VALUE=' . $value, $root . '/oracle.cpp', '-o', $root . '/oracle']);
    Check::check($status === 0, 'Compile conversion oracle: ' . $error);
    $oracles[] = Runtime_Conversion_Test::execute([$root . '/oracle']);
}
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
Check::check(Runtime_Conversion_Test::execute([$root . '/program']) === $oracles[0], 'Owned conversions match native values, address identity and destruction order');
Check::check($oracles[0][0] === 0, 'All reached owned results are destroyed');
$types = $first->types;
$integer = $types->types->find_type('int');
$string = $types->types->find_type('string');
$sealed = $types->types->find_type('sealed');
$before = serialize($first);
foreach ([$string, $sealed] as $destination) {
    $selection = Conversion_Resolver::resolve($types, new conversion_request($integer, $destination, conversion_purpose::explicit_cast));
    Check::check($selection?->form === conversion_form::provider_call, 'Explicit purpose selects a provider operation for each configured result type');
    Check::check(Conversion_Resolver::resolve($types, new conversion_request($integer, $destination, conversion_purpose::implicit_boundary)) === null,
        'Explicit conversion grants no implicit assignment permission');
    Check::check(Conversion_Resolver::resolve($types, new conversion_request($integer, $destination, conversion_purpose::condition)) === null,
        'No truthiness contract is inferred from convertibility');
}
$text = Conversion_Resolver::resolve($types, new conversion_request($integer, $string, conversion_purpose::text));
$cast = Conversion_Resolver::resolve($types, new conversion_request($integer, $string, conversion_purpose::explicit_cast));
Check::check(($text->callable_id !== $cast->callable_id)
    && (Conversion_Resolver::resolve($types, new conversion_request($integer, $sealed, conversion_purpose::text)) === null), 'Purpose selects independent contracts for identical type pairs');
Check::check(serialize($first) === $before, 'Conversion selection does not mutate its fixed snapshot');
Lifecycle_Test::preparation($first);

// Reverse selected body workers; all provider results still use ordinary calls and cleanup.
$tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions, $types, new \check_bodies\Body_Set(), true);
$results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($tasks));
$joined = (new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $types, new \check_bodies\Body_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before), 'Reversed conversion checking has deterministic private outputs');
$id = $first->symbols->current->find_symbol('run', '', \collect_symbols\symbol_kind::function_symbol);
$old = $first->lowered->for_symbol($id);
Check::edit($path, str_replace('42', '43', $source));
$second = $session->compile($manifest, $root . '/program');
Check::check(Runtime_Conversion_Test::execute([$root . '/program']) === $oracles[1], 'One conversion-body increment matches native execution');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->lowered->for_symbol($id) !== $old), 'Changed conversion body replaces its plan under unchanged contracts');
foreach ($first->llvm->modules as $module) {
    if ($module->source_file_id !== $old->source_file_id()) {
        Check::check(($second->llvm->module_for($module->source_file_id) === $module)
            && ($second->native->object_for($module->source_file_id) === $first->native->object_for($module->source_file_id)), 'Unchanged conversion callers retain modules and native objects');
    }
}
Check::check(serialize($first) === $before, 'Increment preserves the prior snapshot');

Files::write($root . '/no-echo.phs', 'echo 7; return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
    ->compile($root . '/no-echo.phs', $root . '/no-echo'), 'No echo contract');

// Imported conversions cannot silently change purpose, ownership, physical positions or ambiguity.
foreach (['purpose', 'ownership', 'postcondition', 'position', 'duplicate'] as $fault)
{
    $bad = $metadata;
    foreach ($bad['operations'] as &$operation)
    {
        if (($operation['id'] ?? null) !== 'string.from_int') {
            continue;
        }
        if ($fault === 'purpose') {
            $operation['conversion_purpose'] = 'implicit';
        }
        elseif ($fault === 'ownership') {
            $operation['result']['ownership'] = 'value';
        }
        elseif ($fault === 'postcondition') {
            $operation['storage_after'] = 'uninitialized_caller_storage';
        }
        elseif ($fault === 'position') {
            $operation['parameters'][0]['abi_indices'] = [0];
        }
        else {
            $duplicate = $operation;
            $duplicate['id'] = 'duplicate';
            $duplicate['symbol'] .= '_duplicate';
            $duplicate['expose_as']['name'] .= '_duplicate';
        }
        break;
    }
    unset($operation);
    if ($fault === 'duplicate') {
        $bad['operations'][] = $duplicate;
    }
    Runtime_Conversion_Test::metadata($config['output_directory'], $bad);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($config['output_directory'], $first->backend->runtime->base_catalog),
        match ($fault) {
            'purpose' => 'conversion operation', 'ownership' => 'caller-storage result', 'postcondition' => 'postcondition',
            'position' => 'parameter passing', 'duplicate' => 'Duplicate conversion'
        });
}
Runtime_Conversion_Test::metadata($config['output_directory'], $metadata);
foreach (['$value string = 7; return 0;', '$value sealed = sealed_make(7); $copy sealed = $value; return 0;'] as $source) {
    Files::write($root . '/bad.phs', $source);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
        ->compile($root . '/bad.phs', $root . '/bad'), 'Unsupported');
}
echo "runtime conversions ok: purpose selection, real owned strings, nonmovable second type, physical result storage, native cleanup, fixed workers and incremental replacement\n";
