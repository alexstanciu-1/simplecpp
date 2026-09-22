<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Struct_Test
{
    /** Capture subprocess output without interpreting bytes or truncating failure diagnostics. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start struct proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }
}

$root = getcwd() . '/struct-proof';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
Files::write($root . '/values.hpp', <<<'CPP'
#pragma once
#include <cstdint>
namespace proof {
inline std::int32_t seed() { return 17; }
inline std::uint8_t tag() { return 3; }
}
CPP);
Files::write_json($root . '/definitions/values.json', ['schema_version' => 1, 'types' => [
    ['id' => 'small', 'cpp_name' => 'std::uint8_t', 'header' => 'cstdint', 'kind' => 'integer', 'language_type' => ['name' => 'uint8', 'namespace' => '']],
    ['id' => 'wide', 'cpp_name' => 'std::int32_t', 'header' => 'cstdint', 'kind' => 'integer', 'language_type' => ['name' => 'int32', 'namespace' => '']],
], 'operations' => [
    ['id' => 'seed', 'kind' => 'free_function', 'cpp_name' => 'proof::seed', 'header' => 'values.hpp', 'parameters' => [],
        'result_type' => 'wide', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'seed', 'namespace' => '']],
    ['id' => 'tag', 'kind' => 'free_function', 'cpp_name' => 'proof::tag', 'header' => 'values.hpp', 'parameters' => [],
        'result_type' => 'small', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'tag', 'namespace' => '']],
]]);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$native_includes = [];
foreach ($config['include_directories'] as $include) {
    $native_includes[] = '-I' . realpath(dirname(__DIR__, 2) . '/src-runtime-preparation/' . $include);
}
$config['definitions_directory'] = $root . '/definitions';
$config['include_directories'] = [$root];
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'return exercise(seed(), tag());');
$declarations = <<<'PHS'
struct packet {
    public uint8 $tag;
    public int32 $count;
}
struct reverse_packet {
    public int32 $count;
    public uint8 $tag;
}
struct distinct_packet {
    public uint8 $tag;
    public int32 $count;
}
PHS;
Files::write($root . '/project/src/types.phs', $declarations);
$body = <<<'PHS'
/** Exercise initialization, independent copies and field writes through ordinary scalar calls. */
function exercise($seed int32, $tag uint8): int
{
    $a packet = new packet();
    if ($a->tag) {
        return 99;
    }
    $a->count = $seed;
    $a->tag = $tag;
    $copy packet = $a;
    $a->count = $seed + $seed;
    $reverse reverse_packet;
    if ($reverse->tag) {
        return 98;
    }
    $reverse->count = $a->count;
    $reverse->count + $copy->count;
    $zero packet = new packet();
    $zero = $copy;
    $zero = $zero;
    while ($zero->tag) {
        $zero->tag = $reverse->tag;
    }
    if ($zero->tag) {
        return 96;
    }
    new packet();
    if ($copy->tag) {
        return $zero->count + $reverse->count;
    }
    return 0;
}
PHS;
$path = $root . '/project/src/body.phs';
Files::write($path, $body);
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
Check::check(Struct_Test::run([$root . '/program']) === [51, '', ''], 'Nonzero field writes, copied values, scalar calls and early return execute');
$before = serialize($first);
$types = $first->types->types;
$packet = $types->find_type('packet');
$reverse = $types->find_type('reverse_packet');
$distinct = $types->find_type('distinct_packet');
Check::check(($packet !== $distinct) && ($types->representation_for_type($packet) === $types->representation_for_type($distinct)),
    'Nominal identity is independent of shared field shape');

// Compare LLVM target facts with the selected Clang's real native record layout.
$provider = Files::json($config['output_directory'] . '/package/manifest.json');
Files::write($root . '/layout.cpp', <<<'CPP'
#include <cstddef>
#include <cstdint>
#include <cstdio>
#include <scpp/lang/php.hpp>
struct packet { scpp::int_t<std::uint8_t> tag; scpp::int_t<std::int32_t> count; };
struct reverse_packet { scpp::int_t<std::int32_t> count; scpp::int_t<std::uint8_t> tag; };
int main() {
    std::printf("%zu %zu %zu %zu\n", sizeof(packet), alignof(packet), offsetof(packet, tag), offsetof(packet, count));
    std::printf("%zu %zu %zu %zu\n", sizeof(reverse_packet), alignof(reverse_packet), offsetof(reverse_packet, count), offsetof(reverse_packet, tag));
}
CPP);
[$status, , $error] = Struct_Test::run([$provider['link_driver']['executable'], ...$provider['link_driver']['arguments'],
    '-std=c++23', ...$native_includes, $root . '/layout.cpp', '-o', $root . '/layout']);
Check::check($status === 0, 'Compile target layout oracle: ' . $error);
$rows = explode("\n", trim(Struct_Test::run([$root . '/layout'])[1]));
foreach ([$packet, $reverse] as $index => $type) {
    $layout = $first->backend->layouts[$type];
    Check::check(implode(' ', [$layout->size, $layout->alignment, ...$layout->offsets]) === $rows[$index],
        'Prepared size, alignment, field offsets and tail padding match authoritative target');
}

// Definition extraction has the same selected/private/accepted protocol as later target work.
$record_tasks = \resolve_types\Record_Preparation::select($first->symbols->current, $first->types->catalog, $types, true, $first->resolutions);
$record_results = array_map(static fn($task) => \resolve_types\Record_Preparation::resolve($task), array_reverse($record_tasks));
$candidate = \resolve_types\Type_Cache::prepare(null, $types->context, true);
$record_join = new \resolve_types\Record_Join($candidate, $record_tasks, $first->symbols->current);
Check::rejects(static fn() => $record_join->join([]), 'Incomplete');
Check::rejects(static fn() => $record_join->join([...$record_results, $record_results[0]]), 'duplicate');
$foreign = new \resolve_types\record_task($record_tasks[0]->input, $record_tasks[0]->catalog, $record_tasks[0]->names);
Check::rejects(static fn() => $record_join->join([new \resolve_types\record_result($foreign, $record_results[0]->declaration)]), 'stale');
// Put the invalid output last: earlier valid results must not materialize before batch validation.
$invalid_index = array_key_last($record_results);
$valid_result = $record_results[$invalid_index];
$declaration = $valid_result->declaration;
$invalid_fields = $declaration->fields;
$invalid_fields[0] = new \type_model\field_declaration($invalid_fields[0]->name, $invalid_fields[0]->definition, false);
$changed_contract = new \type_model\record_declaration($declaration->name, $declaration->namespace_name,
    $invalid_fields, true, $declaration->layout_policy);
$malformed_contract = new \type_model\record_declaration($declaration->name, $declaration->namespace_name,
    [null], true, $declaration->layout_policy);
$candidate_before = $candidate->to_json();
foreach ([$changed_contract, $malformed_contract] as $invalid)
{
    $invalid_results = $record_results;
    $invalid_results[$invalid_index] = new \resolve_types\record_result($valid_result->task, $invalid);
    Check::rejects(static fn() => $record_join->join($invalid_results), 'stale record result');
    Check::check($candidate->to_json() === $candidate_before, 'Rejected field contracts do not partially materialize records');
}
$record_join->join($record_results);
Check::check(count($candidate->structures()) === 3, 'Reversed definition workers join into common canonical records');

// A future provider producer supplies normalized declarations, not foreign store ranges or ASTs.
$normalized = new \type_model\record_declaration('provided_record', '', $record_results[0]->declaration->fields, true, \type_model\record_layout_policy::target);
$provided = \resolve_types\Record_Definitions::materialize($candidate, $normalized);
Check::check($candidate->field_for($provided, 0)->name === $normalized->fields[0]->name,
    'Normalized producer inputs share canonical field materialization');
Check::rejects(static fn() => \resolve_types\Record_Definitions::materialize($candidate,
    new \type_model\record_declaration('unverified', '', $normalized->fields, false, \type_model\record_layout_policy::target)), 'Unsupported record layout');

// Reversed private workers and malformed joins preserve the fixed input snapshot.
$tools = new \prepare_backend\LLVM_Toolchain();
$configuration = $tools->configuration();
[$command, $launcher] = $tools->layout_tools($configuration);
$layout_input = \prepare_backend\Layout_Preparation::capture($types, array_keys(\prepare_backend\Layout_Preparation::definitions($types)));
$tasks = \prepare_backend\Layout_Preparation::select($layout_input, $configuration, [], true, $command, $launcher);
$results = array_map(static fn($task) => \prepare_backend\Layout_Preparation::prepare($task), array_reverse($tasks));
$join = new \prepare_backend\Layout_Join($layout_input, $configuration, [], $tasks);
$layouts = $join->join($results);
Check::check($layouts[$packet]->offsets === $first->backend->layouts[$packet]->offsets, 'Layout worker completion order is irrelevant');
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
$task = $tasks[0];
$foreign = new \prepare_backend\layout_task($task->type_id, $task->definition, $task->fields, $task->field_types,
    $task->configuration, $task->command, $task->launcher, $task->input);
Check::rejects(static fn() => $join->join([new \prepare_backend\layout_result($foreign, $results[0]->layout)]), 'stale');
$invalid_task = new \prepare_backend\layout_task($task->type_id, $task->definition, [], [],
    $task->configuration, $task->command, $task->launcher, $task->input);
Check::rejects(static fn() => (new \prepare_backend\Layout_Join($layout_input, $configuration, [], [$invalid_task]))->join([]),
    'Invalid selected layout fields');
$export = json_decode($first->backend->to_json(), true, flags: JSON_THROW_ON_ERROR);
Check::check($export['layouts'][$packet]['offsets'] === $layouts[$packet]->offsets, 'Backend export exposes accepted layout facts');
$body_tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$body_results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($body_tasks));
$joined = (new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), $body_tasks))->join($body_results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before), 'Field workers have deterministic private results');

Check::edit($path, str_replace('$seed + $seed;', '$seed + $seed + $seed;', $body));
$second = $session->compile($manifest, $root . '/program');
Check::check(Struct_Test::run([$root . '/program']) === [68, '', ''], 'One struct-body increment executes the changed field calculation');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->types->types->definition_for_type($packet) === $types->definition_for_type($packet)), 'Body increment reuses definitions and exact layout results');
$id = $first->symbols->current->find_symbol('exercise', '', \collect_symbols\symbol_kind::function_symbol);
$changed_file = $first->lowered->for_symbol($id)->source_file_id();
foreach ($first->llvm->modules as $module) {
    if ($module->source_file_id !== $changed_file) {
        Check::check(($second->llvm->module_for($module->source_file_id) === $module)
            && ($second->native->object_for($module->source_file_id) === $first->native->object_for($module->source_file_id)), 'Unchanged caller module/object reuse');
    }
}
Check::check(serialize($first) === $before, 'Increment leaves accepted snapshots unchanged');
Check::check(\prepare_backend\Layout_Preparation::select(\prepare_backend\Layout_Preparation::capture($second->types->types,
    array_keys($second->backend->layouts)), $second->backend->configuration,
    $first->backend->layouts, false, $command, $launcher) === [], 'Body edits select no layout probes');

// A separate full + one-attempt proof exercises the existing declaration-change fallback.
Check::edit($path, $body);
$fallback_session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$baseline = $fallback_session->compile($manifest, $root . '/fallback');
$baseline_bytes = serialize($baseline);
$changed_declaration = str_replace("public uint8 \$tag;\n    public int32 \$count;",
    "public int32 \$count;\n    public uint8 \$tag;", $declarations);
Check::edit($root . '/project/src/types.phs', $changed_declaration);
$rebuilt = $fallback_session->compile($manifest, $root . '/fallback');
Check::check($rebuilt->inputs->context->full_rebuild && (Struct_Test::run([$root . '/fallback']) === [51, '', '']),
    'Record declaration changes use the existing full-selection fallback');
Check::check(($rebuilt->types->types->find_type('packet') === $packet)
    && ($rebuilt->backend->layouts[$packet] !== $baseline->backend->layouts[$packet])
    && ($rebuilt->backend->layouts[$packet]->offsets === $baseline->backend->layouts[$reverse]->offsets)
    && (serialize($baseline) === $baseline_bytes), 'Changed declarations retain nominal IDs and replace layout without changing old snapshots');
Check::rejects(static fn() => $baseline->backend->validate($rebuilt->types), 'Stale');

foreach ([
    ['$p packet; return $p->missing;', 'Unknown or unavailable field'],
    ['$p packet = new distinct_packet(); return 0;', 'Unsupported'],
    ['new missing(); return 0;', 'Unknown or unsupported construction'],
    ['$value int; return 0;', 'Default construction'],
    ['new int32(); return 0;', 'Default construction'],
    ['new packet(1); return 0;', 'constructor arguments'],
    ['$p packet; return $p->count->x;', 'Unknown or unavailable field'],
    ['return (new packet())->count;', 'local storage root'],
    ['$p packet; $p->count + 1 = 2; return 0;', 'local storage root'],
    ['$p packet; $q distinct_packet = $p; return 0;', 'Unsupported'],
    ['$p packet; $p->count = 1; return 0;', 'Unsupported'],
    ['function bad($p packet): int { return 0; } return 0;', 'Struct function parameters'],
    ['struct empty {} return 0;', 'Empty structs'],
    ['struct bad { public int $value; } return 0;', 'unsupported struct field'],
    ['struct bad { public bad $value; } return 0;', 'Unresolved concrete preparation prerequisite'],
    ['struct bad { public int32 $value; public int32 $value; } return 0;', 'Duplicate field'],
    ['struct int32 { public uint8 $value; } return 0;', 'Duplicate source/provider'],
] as [$source, $reason]) {
    Files::write($root . '/bad.phs', $declarations . "\n" . $source);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
        ->compile($root . '/bad.phs', $root . '/bad'), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Unsupported source structs produce source diagnostics');
}
echo "source structs ok: common definitions/places, target layout, zero construction, independent copying, field reads/writes, fixed workers and one body increment\n";
