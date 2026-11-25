<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use load_runtime\Runtime_Import;
use load_runtime\Input_Join;

final class Runtime_Inputs_Test
{
    /** Execute the native consumer, retaining its exit status. */
    public static function status(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        Check::check(is_resource($process), 'Start runtime input consumer');
        return proc_close($process);
    }

    /** Modify independently sealed package metadata to exercise composition conflicts. */
    public static function metadata(string $directory, array $metadata): void
    {
        $manifest = Files::json($directory . '/package/manifest.json');
        $manifest['target'] = $metadata['target'];
        Files::write_json($directory . '/package/metadata.json', $metadata);
        $manifest['artifacts']['metadata.json'] = hash_file('sha256', $directory . '/package/metadata.json');
        Files::write_json($directory . '/package/manifest.json', $manifest);
        $pointer = Files::json($directory . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $directory . '/package/manifest.json');
        Files::write_json($directory . '/current.json', $pointer);
    }
}

$root = getcwd() . '/runtime-inputs';
$paths = [];
foreach (['left' => 1, 'right' => 2] as $provider => $increment)
{
    $directory = $root . '/' . $provider;
    Files::directory($directory . '/definitions');
    Files::write($directory . '/native.hpp', '#include <cstdint>' . "\nnamespace " . $provider . " {\n"
        . 'struct record { std::int32_t value; };' . "\n"
        . 'inline std::int32_t step(std::int32_t v) { return v + ' . $increment . '; }' . "\n"
        . 'inline std::int32_t read(const record &v) { return v.value; }' . "\n}\n");
    $types = [
        ['id' => 'integer', 'kind' => 'integer', 'cpp_name' => 'std::int32_t', 'header' => 'cstdint',
            'language_type' => ['name' => 'int32', 'namespace' => '']],
        ['id' => 'record', 'kind' => 'value_record', 'cpp_name' => $provider . '::record', 'header' => 'native.hpp',
            'storage' => 'inline', 'construction' => 'zero', 'copy' => 'value', 'cleanup' => 'none',
            'fields' => [['name' => 'value', 'member' => 'value', 'type' => 'integer', 'writable' => true]],
            'language_type' => ['name' => $provider . '_record', 'namespace' => '']],
    ];
    $operations = [];
    foreach (['step' => ['integer'], 'read' => [['type' => 'record', 'passing' => 'const_address', 'borrow_scope' => 'call']]] as $name => $parameters) {
        $operations[] = ['id' => $name, 'kind' => 'free_function', 'cpp_name' => $provider . '::' . $name, 'header' => 'native.hpp',
            'parameters' => $parameters, 'result_type' => 'integer', 'error_policy' => 'terminate',
            'expose_as' => ['name' => $provider . '_' . $name, 'namespace' => '']];
    }
    Files::write_json($directory . '/definitions/input.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
    $config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
    $config['provider'] = $provider;
    $config['definitions_directory'] = $directory . '/definitions';
    $config['include_directories'] = [$directory];
    $config['output_directory'] = $directory . '/generated';
    Files::write_json($directory . '/config.json', $config);
    (new \runtime_preparation\Runtime_Preparation())->run($directory . '/config.json');
    $paths[] = $config['output_directory'];
}

// Equal native spelling/layout does not identify types across independent providers.
Files::directory($root . '/project');
$source = $root . '/project/main.phs';
$program = '$a left_record; $b right_record; $a->value = left_step(10); $b->value = right_step(20); return left_read($a) + right_read($b);';
Files::write($source, $program);
symlink($paths[0], $root . '/alias');
$catalog_data = Files::json(dirname(__DIR__, 2) . '/language/named_types.json');
$catalog_data['literal_types']['integer']['name'] = 'int32';
Files::write_json($root . '/types.json', $catalog_data);
$session = new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', runtime_package_path: [$paths[1], $root . '/alias', $paths[0]]);
$first = $session->compile($source, $root . '/consumer');
Check::check($first->completed && (Runtime_Inputs_Test::status($root . '/consumer') === 33), 'Both packages execute in one native program');
$inputs = $first->backend->runtime;
Check::check((count($inputs->modules_for(\load_runtime\runtime_module_kind::ordinary)) === 2)
    && (array_keys($inputs->packages()) === ['left', 'right']), 'Aliases deduplicate and package order is deterministic');
Check::check(($inputs->callable_for('left', 'step') !== $inputs->callable_for('right', 'step'))
    && ($inputs->package_for('left')->type_for('integer')->language_type === $inputs->package_for('right')->type_for('integer')->language_type)
    && ($inputs->catalog->find_record('left_record', '') !== $inputs->catalog->find_record('right_record', '')),
    'Scoped operation IDs and unrelated record identities coexist with shared scalar mappings');
$before = serialize($first);
Check::edit($source, str_replace('left_step(10)', 'left_step(11)', $program));
$second = $session->compile($source, $root . '/consumer');
Check::check(!$second->inputs->context->full_rebuild && ($second->backend->runtime === $inputs)
    && (Runtime_Inputs_Test::status($root . '/consumer') === 34) && (serialize($first) === $before),
    'One body increment reuses the complete input set without mutating prior snapshots');
$tools = new \runtime_preparation\Clang_Toolchain($config, $root);
Files::write($root . '/project/cli.phs', '$a left_record; $b right_record; return left_read($a) + right_read($b);');
$tools->run([PHP_BINARY, dirname(__DIR__, 2) . '/src/main.php', '--runtime-package', $paths[0],
    '--runtime-package', $paths[1], '--check', $root . '/project/cli.phs']);

// The same complete module set works for external full/ThinLTO linking.
foreach (['full' => \load_runtime\runtime_module_kind::full_lto, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
{
    $modules = [];
    foreach ($second->llvm->modules as $index => $module) {
        $path = $root . '/caller-' . $index . '.ll';
        Files::write($path, $module->ir);
        $modules[] = $path;
    }
    $output = $root . '/' . $mode;
    $tools->run([$inputs->link_driver, ...$inputs->link_arguments, '-O1', '-flto=' . $mode,
        '--ld-path=' . Files::executable('/', 'ld.lld-18'), ...$modules, ...$inputs->modules_for($kind), '-o', $output]);
    Check::check(Runtime_Inputs_Test::status($output) === 34, $mode . ' LTO accepts both packages');
}

$published = $session->published;
Check::rejects(static fn() => $session->compile($source, $paths[1] . '/package/runtime.bc'), 'replace compiler input');

// Selection fixes provenance; joins adopt only complete private read results.
$catalog = $inputs->base_catalog;
$tasks = Runtime_Import::select($paths, $catalog, $inputs);
$results = array_map([Runtime_Import::class, 'read'], $tasks);
$join = new Input_Join($catalog, $inputs, $tasks);
Check::check($join->join(array_reverse($results)) === $inputs, 'Completion order preserves input-set reuse');
Check::rejects(static fn() => $join->join([$results[0]]), 'Incomplete');
Check::rejects(static fn() => $join->join([$results[0], $results[0]]), 'duplicate');
$stale = new \load_runtime\runtime_package_result(clone $tasks[0], $results[0]->lease);
Check::rejects(static fn() => $join->join([$stale, $results[1]]), 'stale');
foreach (['left', 'right'] as $provider) {
    Check::rejects(static fn() => (new \runtime_preparation\Runtime_Preparation())->run($root . '/' . $provider . '/config.json'), 'lock');
}
foreach ($results as $result) {
    $result->lease->release();
}
Check::rejects(static fn() => $join->join($results), 'stale');

// Rejections release every reservation and preserve the published executable.
$metadata = Files::json($paths[1] . '/package/metadata.json');
$bad = $metadata;
$bad['operations'][0]['expose_as'] = ['name' => 'left_step', 'namespace' => ''];
Runtime_Inputs_Test::metadata($paths[1], $bad);
Check::rejects(static fn() => Runtime_Import::open($paths, $catalog), 'Conflicting runtime callable');
$bad = $metadata;
$left_metadata = Files::json($paths[0] . '/package/metadata.json');
$bad['operations'][0]['symbol'] = $left_metadata['operations'][0]['symbol'];
Runtime_Inputs_Test::metadata($paths[1], $bad);
Check::rejects(static fn() => Runtime_Import::open($paths, $catalog), 'link identity');
$bad = $metadata;
$bad['target']['data_layout'] .= '-n999';
Runtime_Inputs_Test::metadata($paths[1], $bad);
Check::rejects(static fn() => Runtime_Import::open($paths, $catalog), 'target or link');
Runtime_Inputs_Test::metadata($paths[1], $metadata);

// A second directory claiming the same provider is ambiguous even with no shared source exports.
$tools->run(['cp', '-a', $paths[0], $root . '/duplicate']);
Check::rejects(static fn() => Runtime_Import::open([$paths[0], $root . '/duplicate'], $catalog), 'provider namespace');
Files::directory($root . '/z-invalid');
Check::rejects(static fn() => Runtime_Import::open([$paths[0], $root . '/z-invalid'], $catalog), 'package');
foreach (['left', 'right'] as $provider) {
    Check::check((new \runtime_preparation\Runtime_Preparation())->run($root . '/' . $provider . '/config.json')['status'] === 'reused',
        'Success and failure paths released all package reservations');
}
Check::edit($source, '$a left_record; return right_read($a);');
Check::rejects(static fn() => $session->compile($source, $root . '/consumer'), 'conversion');
Check::check(($session->published === $published) && (Runtime_Inputs_Test::status($root . '/consumer') === 34), 'Rejected equal-layout substitution preserves publication');
echo "runtime inputs ok: scoped identities, composed records, shared scalars, leases, joins, conflicts, one increment, full/ThinLTO\n";
