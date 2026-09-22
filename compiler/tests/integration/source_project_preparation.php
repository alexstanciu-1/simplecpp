<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/source_export_support.php';

use Body_Test_Stages as Check;
use runtime_preparation as native;
use runtime_preparation\families as families;
use runtime_preparation\project as project;
use prepare_backend as backend;

$root = getcwd() . '/project-modules';
[$session, $compiled, $scope, $body] = Source_Export_Test::project($root);
$roots = array_map($compiled->types->types->find_type(...), ['first', 'nested']);
$current = Source_Export_Test::capture($compiled, $scope, $roots);
$exports = (new backend\Source_Export_Join($current, [], $current))->join(array_map(backend\Source_Export_Preparation::prepare(...), $current));
$arguments = array_map(project\Source_Adapter::argument(...), $exports);
$base = dirname(__DIR__, 2) . '/src-runtime-preparation';
$data = native\Files::json($base . '/tests/families/catalog.json');
// Native capabilities and physical crossings belong to real family metadata, independent of source names.
foreach ($data['families'] as &$family)
{
    foreach ($family['parameters'] as &$parameter) {
        $parameter['source_profiles'] = [project\source_type::PROFILE];
    }
    unset($parameter);
    foreach ($family['operations'] as &$operation)
    {
        foreach ($operation['parameters'] ?? [] as $index => $parameter)
        {
            if (is_array($parameter) && ($parameter['passing'] === 'const_address') && ($parameter['type'] !== '$self')) {
                $operation['parameters'][$index]['source_payload'] = 'copy_in';
            }
        }
        if (($operation['result_type'] ?? null) === '$element') {
            $operation['source_payload_result'] = 'copy_out';
        }
    }
    unset($operation);
}
unset($family);
foreach (['copy_construct', 'copy_assign'] as $role) {
    $data['families'][0]['lifecycle'][$role] = $role;
    $data['families'][0]['operations'][] = ['id' => $role, 'kind' => $role, 'type' => '$self', 'error_policy' => 'terminate'];
}
$catalog = families\Catalog::parse($data, 'project.fixture');
$config = native\Files::json($base . '/config.json');
$config = ['clang' => $config['clang'], 'target' => $compiled->backend->configuration->target_triple,
    'standard' => $config['standard'], 'include_directories' => array_map(static fn($path) => realpath(native\Files::path($base, $path)), $config['include_directories'])];
$preparer = new families\Preparation(new families\Store($scope->output_root));
$tasks = [];
foreach ($arguments as $argument) {
    $tasks[] = $preparer->select(new families\specialization_request($catalog, 'sequence', [$argument],
        ['append_copy', 'read_copy', 'length'], $scope->project_key, $config));
}
$results = [];
try
{
    foreach (array_reverse($tasks) as $task) {
        $results[] = families\Preparation::execute($task);
    }
    $accepted = (new families\Join($tasks))->join($results);
    foreach ($accepted as $result)
    {
        $manifest = $result->manifest;
        Check::check(($manifest['module_kind'] === 'project') && !$manifest['validation']['native_link_no_undefined'],
            'Project module never claims ordinary runtime closure');
        $directory = $result->candidate->directory;
        $receipt = native\Files::json($directory . '/project.json');
        Check::check(count($receipt['required_imports']) === 4, 'Ordinary/full/ThinLTO modules report their source imports');
        $code = native\Files::read($directory . '/runtime.cpp');
        Check::check(str_contains($code, 'copy_payload_tag') && str_contains($code, 'native_result.payload'),
            'Borrowed payload copy-in and caller-storage copy-out use explicit adapter operations');
        Check::check(!str_contains($code, 'static_cast<const ' . $result->task->arguments[0]->definition['cpp_name'] . ' *>(arg1)'),
            'Source payload address is never cast to a native adapter reference');
        $operation = $result->operations['instance.append_copy'];
        Check::check($operation['parameters'][1]['payload_crossing'] === 'copy_in', 'Prepared metadata records the payload crossing');
        Check::check($result->operations['instance.read_copy']['result']['payload_crossing'] === 'copy_out', 'Owned result metadata records copy-out');
        $llvm = native\Files::read($directory . '/runtime.ll');
        Check::rejects(static fn() => project\Module::imports($result->task->project,
            $llvm . "\ndeclare void @scpp_source_unauthorized(ptr)\n"), 'Unauthorized source import');
        $symbol = array_key_first($receipt['required_imports']['runtime.ll']);
        Check::rejects(static fn() => project\Module::imports($result->task->project,
            str_replace('declare void @' . $symbol . '(', 'declare i64 @' . $symbol . '(', $llvm)), 'source import');
        $published = $result->candidate->publish();
        Check::check($published['status'] === 'built', 'Accepted project module publishes through the shared stable slot');
    }
    Check::rejects(static fn() => (new families\Join($tasks))->join([]), 'Missing selected');
}
finally {
    foreach ($results as $result) {
        $result->candidate->release();
    }
}
foreach ($tasks as $task) {
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($task->reservation->output, $compiled->backend->runtime->base_catalog), 'manifest');
}
// A body-only update reprojects the same portable source contract and reuses native artifacts.
Check::edit($root . '/project/body.phs', str_replace('return 7;', 'return 9;', $body));
$increment = $session->compile($root . '/project/project.json', $root . '/program');
$new_tasks = Source_Export_Test::capture($increment, $scope, [$roots[0]]);
$new_export = backend\Source_Export_Preparation::prepare($new_tasks[$roots[0]]);
$new_argument = project\Source_Adapter::argument($new_export);
Check::check($new_argument == array_values($arguments)[0], 'Body-only replacement preserves the portable preparation contract');
$again = $preparer->run(new families\specialization_request($catalog, 'sequence', [$new_argument],
    ['length'], $scope->project_key, $config));
Check::check($again['status'] === 'reused', 'Matching source contracts reuse the stable project module');
$no_profile = $data;
$no_profile['families'][0]['parameters'][0]['source_profiles'] = [];
$blocked = families\Catalog::parse($no_profile, 'project.fixture');
Check::rejects(static fn() => $preparer->select(new families\specialization_request($blocked, 'sequence', [$new_argument],
    ['append_copy'], $scope->project_key, $config)), 'does not authorize');
$no_crossing = $data;
unset($no_crossing['families'][0]['operations'][2]['parameters'][1]['source_payload']);
$blocked = families\Catalog::parse($no_crossing, 'project.fixture');
Check::rejects(static fn() => $preparer->select(new families\specialization_request($blocked, 'sequence', [$new_argument],
    ['append_copy'], $scope->project_key, $config)), 'copy-in');
Check::rejects(static fn() => $preparer->select(new families\specialization_request($catalog, 'sequence', [$new_argument],
    ['length'], 'other-project', $config)), 'foreign project');
echo "project source preparation ok: generated adapters, explicit crossings, source import acceptance and reuse\n";
