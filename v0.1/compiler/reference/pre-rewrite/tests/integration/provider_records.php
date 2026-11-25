<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Provider_Record_Test
{
    /** Execute a produced native artifact and retain exact status/output for the proof. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start native record proof');
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $stdout, $stderr];
    }
}

$root = getcwd() . '/provider-record-proof';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
$header = <<<'CPP'
#pragma once
#include <cstdint>
namespace native {
struct pair { std::uint8_t marker; std::int32_t total; };
struct reverse { std::int32_t total; std::uint8_t marker; };
inline std::int32_t seed() { return 17; }
inline std::uint8_t tag() { return 3; }
}
CPP;
Files::write($root . '/native.hpp', $header);
$fields = [
    ['name' => 'tag', 'member' => 'marker', 'type' => 'small', 'writable' => true],
    ['name' => 'amount', 'member' => 'total', 'type' => 'wide', 'writable' => true],
];
$definition = ['schema_version' => 1, 'types' => [
    ['id' => 'small', 'cpp_name' => 'std::uint8_t', 'header' => 'cstdint', 'kind' => 'integer',
        'language_type' => ['name' => 'uint8', 'namespace' => '']],
    ['id' => 'wide', 'cpp_name' => 'std::int32_t', 'header' => 'cstdint', 'kind' => 'integer',
        'language_type' => ['name' => 'int32', 'namespace' => '']],
    ['id' => 'pair', 'cpp_name' => 'native::pair', 'header' => 'native.hpp', 'kind' => 'value_record', 'storage' => 'inline',
        'construction' => 'zero', 'copy' => 'value', 'cleanup' => 'none', 'fields' => $fields,
        'language_type' => ['name' => 'provided_pair', 'namespace' => '']],
    ['id' => 'reverse', 'cpp_name' => 'native::reverse', 'header' => 'native.hpp', 'kind' => 'value_record', 'storage' => 'inline',
        'construction' => 'zero', 'copy' => 'value', 'cleanup' => 'none',
        'fields' => [$fields[1], array_replace($fields[0], ['writable' => false])],
        'language_type' => ['name' => 'provided_reverse', 'namespace' => '']],
], 'operations' => [
    ['id' => 'seed', 'kind' => 'free_function', 'cpp_name' => 'native::seed', 'header' => 'native.hpp', 'parameters' => [],
        'result_type' => 'wide', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'seed', 'namespace' => '']],
    ['id' => 'tag', 'kind' => 'free_function', 'cpp_name' => 'native::tag', 'header' => 'native.hpp', 'parameters' => [],
        'result_type' => 'small', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'tag', 'namespace' => '']],
]];
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['include_directories'] = [$root];
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
Files::write_json($root . '/definitions/records.json', $definition);
$tool = new \runtime_preparation\Runtime_Preparation();
$built = $tool->run($root . '/config.json');
Check::check($built['status'] === 'built', 'Native record package prepared through JSON');
$metadata = Files::json($config['output_directory'] . '/package/metadata.json');
$measured = array_column($metadata['types'], null, 'id');
Check::check(($measured['pair']['validation']['complete_public_fields'] === true)
    && ($measured['pair']['fields'][1]['offset_bytes'] > 0), 'Preparation exports authoritative complete field measurements');
Check::check($tool->run($root . '/config.json')['status'] === 'reused', 'Unchanged definitions reuse prepared record artifacts');

$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'return exercise();');
$source_record = <<<'PHS'
struct source_pair {
    public uint8 $tag;
    public int32 $amount;
}
PHS;
Files::write($root . '/project/src/types.phs', $source_record);
$body = <<<'PHS'
/** Source and provider fields share construction, storage, reads/writes and copying. */
function exercise(): int
{
    $value provided_pair = new provided_pair();
    if ($value->tag) {
        return 97;
    }
    $value->amount = seed();
    $value->tag = tag();
    $copy provided_pair = $value;
    $value->amount = seed() + seed();
    $source source_pair;
    $source->amount = $copy->amount;
    $reverse provided_reverse;
    if ($reverse->tag) {
        return 98;
    }
    $reverse->amount = $source->amount;
    $reverse_copy provided_reverse = $reverse;
    $reverse_copy = $reverse;
    if ($reverse_copy->tag) {
        return 96;
    }
    $assigned provided_pair;
    $assigned = $copy;
    if ($assigned->tag) {
        return $reverse_copy->amount + $value->amount;
    }
    return 99;
}
PHS;
$body_path = $root . '/project/src/body.phs';
Files::write($body_path, $body);
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
Check::check(Provider_Record_Test::run([$root . '/program']) === [51, '', ''], 'Provider fields execute through the common value pipeline');
$before = serialize($first);
$types = $first->types->types;
$provided = $types->find_type('provided_pair');
$source = $types->find_type('source_pair');
Check::check(($provided !== $source) && ($types->representation_for_type($provided) === $types->representation_for_type($source)),
    'Source/provider nominal types share the same semantic field representation');
$native = $types->definition_for_type($provided)->native_layout;
$layout = $first->backend->layouts[$provided];
Check::check(($layout->size === $native->size) && ($layout->alignment === $native->alignment)
    && ($layout->offsets === $native->offsets), 'Common target layout satisfies imported native measurements');

// Selected definition/layout/body workers remain pure and accept any completion order.
$tasks = \resolve_types\Record_Preparation::select($first->symbols->current, $first->types->catalog, $types, true, $first->resolutions);
$results = array_map(static fn($task) => \resolve_types\Record_Preparation::resolve($task), array_reverse($tasks));
$candidate = \resolve_types\Type_Cache::prepare(null, $types->context, true);
$join = new \resolve_types\Record_Join($candidate, $tasks, $first->symbols->current);
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
foreach ($tasks as $task) {
    if ($task->input instanceof \type_model\record_declaration) {
        $stale = new \resolve_types\record_task(clone $task->input, $task->catalog, $task->names);
        Check::rejects(static fn() => (new \resolve_types\Record_Join($candidate, [$stale], $first->symbols->current))->join([]), 'Stale');
        break;
    }
}
$join->join($results);
Check::check(count($candidate->structures()) === 3, 'One definition join accepts both producer paths');
$body_tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$body_results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($body_tasks));
$joined = (new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), $body_tasks))->join($body_results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before), 'Provider field checking preserves fixed inputs');
Check::edit($body_path, str_replace('seed() + seed();', 'seed() + seed() + seed();', $body));
$second = $session->compile($manifest, $root . '/program');
Check::check((Provider_Record_Test::run([$root . '/program']) === [68, '', ''])
    && (!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->types->types->definition_for_type($provided) === $types->definition_for_type($provided))
    && (serialize($first) === $before), 'One body increment reuses provider definitions/layout and preserves retained snapshots');

foreach ([
    ['$p provided_reverse; $p->tag = tag(); return 0;', 'read-only'],
    ['$p provided_pair; return $p->missing;', 'Unknown or unavailable field'],
    ['$p provided_pair; $s source_pair = $p; return 0;', 'Unsupported'],
    ['function bad($p provided_pair): int { return 0; } return 0;', 'Struct function parameters'],
    ['struct provided_pair { public int32 $amount; } return 0;', 'Duplicate source/provider'],
] as [$text, $reason])
{
    Files::write($root . '/bad.phs', $source_record . "\n" . $text);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
        ->compile($root . '/bad.phs', $root . '/bad'), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Rejected record use reports a source diagnostic');
    if ($reason === 'Duplicate source/provider') {
        Check::check(substr($source_record . "\n" . $text, $error->start, $error->length) === 'provided_pair',
            'Source/provider collision points at the source declaration name');
    }
}

// A separate full + one-attempt proof exercises actual provider regeneration and conservative invalidation.
Check::edit($body_path, $body);
$refresh = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$baseline = $refresh->compile($manifest, $root . '/refresh');
$old = serialize($baseline);
Files::write($root . '/native.hpp', str_replace('struct pair { std::uint8_t marker; std::int32_t total; };',
    'struct pair { std::int32_t total; std::uint8_t marker; };', $header));
$changed = $definition;
$changed['types'][2]['fields'] = array_reverse($fields);
Files::write_json($root . '/definitions/records.json', $changed);
Check::check($tool->run($root . '/config.json')['status'] === 'built', 'Provider record edit replaces the package');
$updated = $refresh->compile($manifest, $root . '/refresh');
Check::check($updated->inputs->context->full_rebuild && ($updated->types->types->find_type('provided_pair') === $provided)
    && ($updated->backend->layouts[$provided] !== $baseline->backend->layouts[$provided])
    && (Provider_Record_Test::run([$root . '/refresh']) === [51, '', '']) && (serialize($baseline) === $old),
    'Provider changes replace contracts/layout with full selection and stable nominal IDs');
Check::rejects(static fn() => $baseline->backend->validate($updated->types), 'Stale');

// Complete native declarations are required; omissions and incompatible scalar claims cannot publish a package.
Files::write($root . '/native.hpp', $header);
$pointer = Files::read($config['output_directory'] . '/current.json');
$missing = $definition;
array_pop($missing['types'][2]['fields']);
Files::write_json($root . '/definitions/records.json', $missing);
Check::rejects(static fn() => $tool->run($root . '/config.json'), 'all native fields');
$wrong_type = $definition;
$wrong_type['types'][2]['fields'][1]['type'] = 'small';
Files::write_json($root . '/definitions/records.json', $wrong_type);
Check::rejects(static fn() => $tool->run($root . '/config.json'), 'record field type mismatch');
Files::write_json($root . '/definitions/records.json', $definition);
Files::write($root . '/native.hpp', str_replace('std::int32_t total;', 'std::int32_t total = 9;', $header));
Check::rejects(static fn() => $tool->run($root . '/config.json'), 'unsupported value-record capability');
Check::check(Files::read($config['output_directory'] . '/current.json') === $pointer, 'Rejected preparation preserves the published package');

Files::write($root . '/native.hpp', $header);
$abi = $definition;
$abi['operations'][0]['result_type'] = 'pair';
Files::write_json($root . '/definitions/records.json', $abi);
// Record results now have a caller-storage adapter; this native function still returns an integer.
Check::rejects(static fn() => $tool->run($root . '/config.json'), 'static_cast');

// A valid native over-aligned record must be rejected if generated value storage cannot match it.
Files::write_json($root . '/definitions/records.json', $definition);
Files::write($root . '/native.hpp', str_replace('struct pair {', 'struct alignas(32) pair {', $header));
$tool->run($root . '/config.json');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
    ->compile($manifest, $root . '/incompatible'), 'Native record layout is incompatible');

echo "provider records ok: JSON preparation, native field measurements, shared source/provider definitions and locations, worker purity, reuse, provider replacement and rejected unsupported contracts\n";
