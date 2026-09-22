<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use runtime_preparation\Files;
use runtime_preparation\Runtime_Preparation;
use load_runtime\Package_Adapter;
use load_runtime\runtime_module_kind;
use Body_Test_Stages as Check;

final class Runtime_ABI_Test
{
    public static function status(string $executable): int
    {
        $process = proc_open([$executable], [0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        Check::check(is_resource($process), 'Start native runtime consumer');
        return proc_close($process);
    }

    /** Edit fixture package data with matching integrity checks; production consumers use the adapter. */
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

    /** Link compiler output through both LTO modes and verify cross-module provider-call elimination. */
    public static function lto(\compile\Compile_Result $result, \runtime_preparation\Clang_Toolchain $tools,
        string $workspace, int $expected, bool $prepare): void
    {
        $runtime = $result->backend->runtime;
        foreach (['full' => runtime_module_kind::full_lto, 'thin' => runtime_module_kind::thin_lto] as $mode => $kind)
        {
            $inputs = [];
            foreach ($result->llvm->modules as $i => $module)
            {
                $caller = $workspace . '/caller-' . $mode . '-' . $i . '.bc';
                if ($prepare) {
                    $source = $workspace . '/caller-' . $i . '.ll';
                    Files::write($source, $module->ir);
                    $tools->run([$runtime->link_driver, ...$runtime->link_arguments,
                        '-O2', '-flto=' . $mode, '-c', $source, '-o', $caller]);
                }
                $inputs[] = $caller;
            }
            $before = array_map(static fn($path) => hash_file('sha256', $path), $inputs);
            $provider = $workspace . '/provider-' . $mode . '.bc';
            Files::write($provider, Files::read($runtime->package_for('fixture_provider')->module_for($kind)));
            $output = $workspace . '/lto-' . $mode . '-' . $expected;
            $command = [$runtime->link_driver, ...$runtime->link_arguments,
                '-O2', '-flto=' . $mode, '--ld-path=' . Files::executable('/', 'ld.lld-18'), '-Wl,--save-temps'];
            foreach ([...$inputs, $provider] as $input) {
                $command[] = '-Xlinker';
                $command[] = $input;
            }
            $tools->run([...$command, '-o', $output]);
            Check::check(self::status($output) === $expected, $mode . ' LTO executes compiler-generated caller');
            Check::check($before === array_map(static fn($path) => hash_file('sha256', $path), $inputs), 'Retained caller bitcode is unchanged');
            $optimized = $mode === 'full' ? glob($output . '*.opt.bc') : glob($workspace . '/caller-thin-*.opt.bc');
            Check::check($optimized !== [], 'LTO saved optimized caller modules');
            $ir = implode('', array_map(static fn($path) => $tools->inspect_bitcode($path), $optimized));
            $operation = array_values(array_filter($runtime->callables(), static fn($callable) => $callable->id === 'step'))[0];
            $call_pattern = '/\b(?:call|invoke)\b[^\n]*@"?' . preg_quote($operation->abi->link_name, '/') . '"?\(/';
            Check::check(preg_match($call_pattern, implode('', $result->llvm->ir_by_file())) === 1, 'Original compiler output calls the tested provider operation');
            Check::check(str_contains($ir, '@main(') && !preg_match($call_pattern, $ir),
                'LTO eliminates the provider call across module boundaries');
        }
    }
}

$workspace = getcwd() . '/runtime-abi';
Files::directory($workspace . '/definitions');
Files::directory($workspace . '/project');
$base = dirname(__DIR__, 2) . '/src-runtime-preparation';
$configuration = Files::json($base . '/config.json');
$configuration['provider'] = 'fixture_provider';
$configuration['definitions_directory'] = $workspace . '/definitions';
$configuration['include_directories'] = [$workspace];
$configuration['output_directory'] = $workspace . '/generated';
Files::write_json($workspace . '/config.json', $configuration);
$header = '#include <cstdint>' . "\n" . 'namespace fixture {
inline std::int64_t step(std::int64_t value) { return value + 1; }
inline std::uint8_t byte() { return 255; }
inline std::int64_t extend(std::uint8_t value) { return value; }
}';
Files::write($workspace . '/fixture.hpp', $header);
$types = [
    ['id' => 'wide', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
        'language_type' => ['name' => 'int', 'namespace' => '']],
    ['id' => 'byte', 'kind' => 'integer', 'cpp_name' => 'std::uint8_t', 'header' => 'cstdint',
        'language_type' => ['name' => 'uint8', 'namespace' => '']],
];
$operations = [];
foreach ([['step', ['wide'], 'wide'], ['byte', [], 'byte'], ['extend', ['byte'], 'wide']] as [$name, $parameters, $result]) {
    $operations[] = ['id' => $name, 'kind' => 'free_function', 'cpp_name' => 'fixture::' . $name,
        'header' => 'fixture.hpp', 'parameters' => $parameters, 'result_type' => $result, 'error_policy' => 'terminate',
        'expose_as' => ['name' => 'runtime_' . $name, 'namespace' => '']];
}
Files::write_json($workspace . '/definitions/scalars.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
(new Runtime_Preparation())->run($workspace . '/config.json');
$catalog = Files::json(dirname(__DIR__, 2) . '/language/named_types.json');
$catalog['literal_types']['integer']['name'] = 'int32';
Files::write_json($workspace . '/types.json', $catalog);
$source = $workspace . '/project/main.phs';
$output = $workspace . '/consumer';
Files::write($source, '$value int32 = 41; return runtime_step($value);');
$session = new \compile\Compiler_Session(type_catalog_path: $workspace . '/types.json', runtime_package_path: $configuration['output_directory']);
$first = $session->compile($source, $output);
Check::check(($first->completed) && (Runtime_ABI_Test::status($output) === 42), 'Source argument/result crosses the real prepared ABI');
$runtime = $first->backend->runtime;
Check::check(($runtime->package_for('fixture_provider')->storage_for('wide')->size_bytes === 8) && ($runtime->package_for('fixture_provider')->type_for('wide')->language_type === $first->types->catalog->find_type('int', '')),
    'Adapter returns measured storage and canonical language definition');
$id = $first->symbols->current->find_symbol('runtime_step', '', \collect_symbols\symbol_kind::function_symbol);
$symbol = $first->symbols->current->symbol_by_id($id);
Check::check(($symbol->frontend === null) && (!$symbol->has_executable_body()) && ($first->types->for_symbol($id)->syntax === null)
    && ($first->bodies->for_symbol($id) === null), 'External declaration/signature participates without synthetic source or body');
Check::check(str_contains(implode('', $first->llvm->ir_by_file()), 'sext i32'), 'Common argument conversion widens before the external call');
Check::check(json_decode($first->to_json(), true, 512, JSON_THROW_ON_ERROR)['backend']['runtime']['packages'][0]['provider'] === 'fixture_provider', 'Debug export includes normalized provider facts');
$before = serialize($first);
$warm = $session->compile($source, $output);
Check::check(($warm->backend->runtime === $runtime) && ($warm->native === $first->native), 'Verified unchanged package/native reuse');
Check::edit($source, '$value int32 = 42; return runtime_step($value);');
$edited = $session->compile($source, $output);
Check::check((!$edited->inputs->context->full_rebuild) && ($edited->backend->runtime === $runtime)
    && ($edited->types->for_symbol($id) === $first->types->for_symbol($id)) && (Runtime_ABI_Test::status($output) === 43)
    && (serialize($first) === $before), 'One body increment retains provider contracts and prior snapshots');
$tools = new \runtime_preparation\Clang_Toolchain($configuration, $workspace);
Files::write($workspace . '/project/cli.phs', 'return runtime_step(41);');
$tools->run([PHP_BINARY, dirname(__DIR__, 2) . '/src/main.php', '--runtime-package',
    $configuration['output_directory'], '--check', $workspace . '/project/cli.phs']);
Runtime_ABI_Test::lto($edited, $tools, $workspace, 43, true);

// A provider implementation edit must invalidate the application link, even with unchanged source.
$lease = Package_Adapter::open($configuration['output_directory'], $runtime->base_catalog, $runtime->package_for('fixture_provider'));
Check::rejects(static fn() => (new Runtime_Preparation())->run($workspace . '/config.json'), 'lock');
$lease->release();
Files::write($workspace . '/fixture.hpp', str_replace('value + 1', 'value + 2', $header));
(new Runtime_Preparation())->run($workspace . '/config.json');
$changed = $session->compile($source, $output);
Check::check(($changed->inputs->context->full_rebuild) && ($changed->backend->runtime !== $runtime)
    && (Runtime_ABI_Test::status($output) === 44), 'Changed provider forces same-path full selection and a new native result');
Runtime_ABI_Test::lto($changed, $tools, $workspace, 44, false);

Check::edit($source, 'return runtime_extend(runtime_byte());');
$narrow = $session->compile($source, $output);
$ir = implode('', $narrow->llvm->ir_by_file());
Check::check((Runtime_ABI_Test::status($output) === 255) && str_contains($ir, 'zeroext i8') && str_contains($ir, 'i8 zeroext'),
    'Definition-only additions support zero-argument calls and narrow result/parameter ABI attributes');
$published = $session->published;
$fingerprint = hash_file('sha256', $output);
Check::edit($source, 'return runtime_step();');
Check::rejects(static fn() => $session->compile($source, $output), 'argument');
Check::edit($source, 'return runtime_step(runtime_byte());');
Check::rejects(static fn() => $session->compile($source, $output), 'conversion');
Check::edit($source, 'function runtime_step($v int): int { return $v; } return 1;');
Check::rejects(static fn() => $session->compile($source, $output), 'Duplicate');
Check::check(($session->published === $published) && (hash_file('sha256', $output) === $fingerprint), 'Rejected calls preserve accepted state and executable');
Check::edit($source, 'return runtime_extend(runtime_byte());');
Check::rejects(static fn() => $session->compile($source, $configuration['output_directory'] . '/package/runtime.bc'), 'replace compiler input');

$metadata = Files::json($configuration['output_directory'] . '/package/metadata.json');
$step_index = array_search('step', array_column($metadata['operations'], 'id'), true);
$bad = $metadata;
$bad['operations'][$step_index]['parameters'][0]['passing'] = 'borrow';
Runtime_ABI_Test::metadata($configuration['output_directory'], $bad);
Check::rejects(static fn() => $session->compile($source, $output), 'Unsupported runtime parameter');
$bad = $metadata;
$bad['operations'][$step_index]['abi']['parameters'][0]['type'] = 'i32';
Runtime_ABI_Test::metadata($configuration['output_directory'], $bad);
Check::rejects(static fn() => $session->compile($source, $output), 'ABI does not match');
Runtime_ABI_Test::metadata($configuration['output_directory'], $metadata);

// A self-consistent metadata target still must match the compiler's prepared target.
$manifest = Files::json($configuration['output_directory'] . '/package/manifest.json');
$bad = $metadata;
$bad['target']['data_layout'] .= '-n999';
$manifest['target'] = $bad['target'];
Files::write_json($configuration['output_directory'] . '/package/manifest.json', $manifest);
Runtime_ABI_Test::metadata($configuration['output_directory'], $bad);
Check::rejects(static fn() => $session->compile($source, $output), 'incompatible');
$manifest['target'] = $metadata['target'];
Files::write_json($configuration['output_directory'] . '/package/manifest.json', $manifest);
Runtime_ABI_Test::metadata($configuration['output_directory'], $metadata);
file_put_contents($configuration['output_directory'] . '/package/runtime.bc', 'damaged');
Check::rejects(static fn() => $session->compile($source, $output), 'content mismatch');
Check::check(($session->published === $published) && (Runtime_ABI_Test::status($output) === 255), 'Invalid provider preserves publication');

// Query the real string layout without constructing a compiler-side string value.
$strings = Files::json($base . '/config.json');
$strings['definitions_directory'] = $base . '/definitions';
$strings['include_directories'] = array_map(static fn($path) => realpath(Files::path($base, $path)), $strings['include_directories']);
$strings['output_directory'] = $workspace . '/strings';
Files::write_json($workspace . '/strings.json', $strings);
(new Runtime_Preparation())->run($workspace . '/strings.json');
$lease = Package_Adapter::open($strings['output_directory'], $changed->types->catalog);
$storage = $lease->package->storage_for('string');
Check::check(($storage->kind === \load_runtime\runtime_storage_kind::opaque_inline)
    && ($storage->size_bytes > 0) && ($storage->alignment_bytes > 0)
    && ($lease->package->type_for('string')->language_type?->lifetime->copy === \type_model\copy_kind::construct),
    'Real scpp::string_t exposes measured storage and the explicitly prepared copy contract');
$lease->release();
echo "runtime ABI ok: adapter, canonical types, shared declarations, integer widening/attributes, native execution, incremental reuse, provider invalidation, locking, rejected contracts, full/ThinLTO\n";
