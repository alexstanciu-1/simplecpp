<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Source_Borrow_Test
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

$root = getcwd() . '/source-borrow-proof';
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
$config['definitions_directory'] = $root . '/definitions';
$config['include_directories'] = [$root];
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
$definitions = <<<'PHS'
struct packet { public int32 $value; }
struct other { public int32 $value; }
template<typename T>
struct sample { public T $value; }
function read(const packet &$record): int32 { return $record->value; }
function copy_read(const packet &$record): int32
{
    $copy packet = $record;
    $copy->value = seed() + seed();
    return read($record);
}
function write(packet &$record, $value int32): void { $record->value = $value; }
function forward(packet &$record, $value int32): void { write($record, $value); }
function replace(packet &$record, const packet &$other): void { $record = $other; }
function aliased(const packet &$view, packet &$edit, $value int32): int32
{
    $edit->value = $value;
    return $view->value;
}
function reset(packet &$record): int32
{
    $zero packet;
    $record = $zero;
    return $record->value;
}
function after_reset(const packet &$record, $ignored int32): int32 { return read($record); }
template<typename T>
function set(sample<T> &$record, $value T): void { $record->value = $value; }
template<typename T>
function get(const sample<T> &$record): T { return $record->value; }
template<typename T>
function set_forward(sample<T> &$record, $value T): void { set<T>($record, $value); }
PHS;
$body = <<<'PHS'
function exercise(): int
{
    $record packet;
    forward($record, seed());
    $copy packet = $record;
    $observed int = aliased($record, $record, seed() + seed());
    replace($record, $copy);
    $integer sample<int32>;
    set_forward<int32>($integer, seed());
    $byte sample<uint8>;
    set_forward<uint8>($byte, tag());
    $saved int = copy_read($record);
    $wide int = get<int32>($integer);
    $small int = 0;
    if (get<uint8>($byte)) {
        $small = 3;
    }
    $reset int = after_reset($record, reset($record));
    return $observed + $saved + $wide + $small + $reset;
}
PHS;
Files::write($root . '/project/src/types.phs', $definitions);
Files::write($root . '/project/src/body.phs', $body);
Files::write($root . '/project/src/main.phs', 'return exercise();');
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
Check::check(Source_Borrow_Test::run([$root . '/program']) === [71, '', ''],
    'Caller mutation, shared aliases, independent copies, whole-record assignment and two template specializations execute');
$before = serialize($first);

// Incoming borrows bind storage instead of making an alloca/copy. Signatures own the shared contract.


$read_id = $first->symbols->current->find_symbol('read', '', \collect_symbols\symbol_kind::function_symbol);
$write_id = $first->symbols->current->find_symbol('write', '', \collect_symbols\symbol_kind::function_symbol);
$read_shape = $first->types->signature_for($read_id);
Check::check($read_shape->parameter_passing === [\type_model\argument_passing::borrow_const], 'Const borrow is part of semantic signature');
$lowered = $first->lowered->for_symbol($read_id);
Check::check((count($lowered->slots) === 1) && ($lowered->slots[0]->incoming_parameter === 1), 'Read parameter aliases incoming storage');
Check::check($first->types->signature_for($write_id)->parameter_passing[0] === \type_model\argument_passing::borrow_mutable,
    'Mutable borrow survives signature resolution');
$store = clone $first->types->types;
$type = $first->types->parameter_type_for($read_id, 1);
Check::check($store->intern_signature($read_shape->return_type, [$type], [\type_model\argument_passing::borrow_const])
    !== $store->intern_signature($read_shape->return_type, [$type], [\type_model\argument_passing::borrow_mutable]),
    'Passing changes cannot reuse the same interned signature');

// Fixed workers produce private results; joins reject altered passing contracts before materializing anything.
$symbols = $first->symbols->current;
$view = new \resolve_types\Definition_View($first->types->catalog, $first->types->types);
$instances = $first->types->instances;
$tasks = \resolve_types\Signature_Resolver::select($symbols, null, $first->types->types, true, $first->types->entry, $instances);
$requests = array_map(static fn($task) => \resolve_types\Signature_Resolver::resolve($symbols, $view, $task,
    $first->types->entry, $first->resolutions, $instances), array_reverse($tasks));
$candidate = clone $first->types->types;
$join = new \resolve_types\Signature_Join($symbols, $view, $candidate, null, $tasks, $first->types->entry, $first->resolutions, $instances);
$unchanged = serialize($candidate);
Check::rejects(static fn() => $join->join([]), 'Incomplete');
Check::rejects(static fn() => $join->join([...$requests, $requests[0]]), 'duplicate');
$invalid = $requests;
foreach ($invalid as $index => $request)
{
    if ($request->callable_id === $read_id)
    {
        $invalid[$index] = new \resolve_types\signature_request($request->symbol, $request->return_annotation_id,
            $request->definition, $request->parameter_definitions, $request->instance, [\type_model\argument_passing::borrow_mutable]);
        break;
    }
}
Check::rejects(static fn() => $join->join($invalid), 'passing contract');
Check::check(serialize($candidate) === $unchanged, 'Rejected signature work cannot partially write the candidate');
Check::check(count($join->join($requests)) === count($tasks), 'Reversed source/provider/template signature workers join');
$body_tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $symbols, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($body_tasks));
$joined = (new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), $body_tasks))->join($results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before),
    'Reversed body workers preserve checked contracts and all retained inputs');
$export = json_decode($first->types->to_json(), true, flags: JSON_THROW_ON_ERROR);
Check::check(str_contains(json_encode($export, JSON_THROW_ON_ERROR), 'borrow_mutable'), 'Type export includes semantic passing modes');

// One body edit retains fixed declarations, layouts and untouched callers; accepted results stay immutable.
Check::edit($root . '/project/src/body.phs', str_replace('seed() + seed()', 'seed() + seed() + seed()', $body));
$second = $session->compile($manifest, $root . '/program');
Check::check(Source_Borrow_Test::run([$root . '/program']) === [88, '', ''], 'One ordinary body increment changes observable mutation');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->lowered->for_symbol($read_id) === $lowered) && (serialize($first) === $before),
    'Increment reuses unchanged borrowed functions and backend contracts without mutating the baseline');

// A separate full + one declaration edit proves that mode changes invalidate signature and backend identities.
Check::edit($root . '/project/src/body.phs', $body);
$contract_session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$baseline = $contract_session->compile($manifest, $root . '/contract-program');
$baseline_bytes = serialize($baseline);
$alias_id = $baseline->symbols->current->find_symbol('aliased', '', \collect_symbols\symbol_kind::function_symbol);
Check::edit($root . '/project/src/types.phs', str_replace('aliased(const packet &$view', 'aliased(packet &$view', $definitions));
$changed = $contract_session->compile($manifest, $root . '/contract-program');
Check::check($changed->inputs->context->full_rebuild
    && ($changed->types->signature_for($alias_id) !== $baseline->types->signature_for($alias_id))
    && ($changed->types->signature_for($alias_id)->parameter_passing[0] === \type_model\argument_passing::borrow_mutable)
    && ($changed->backend->binding_for($alias_id) !== $baseline->backend->binding_for($alias_id))
    && (Source_Borrow_Test::run([$root . '/contract-program']) === [71, '', ''])
    && (serialize($baseline) === $baseline_bytes), 'Passing-mode edits rebuild through common stages and preserve the previous snapshot');
Check::edit($root . '/project/src/types.phs', $definitions);

// Each rejection is a separate full build; recovery after failure is outside this proof.
foreach ([
    ['function bad(const packet &$p): void { $p->value = seed(); }', 'Cannot write through a const'],
    ['function bad(const packet &$p): void { $q packet; $p = $q; }', 'Cannot write through a const'],
    ['function bad(const packet &$p): void { write($p, seed()); }', 'cannot be passed as a mutable'],
    ['function bad(): void { write(new packet(), seed()); }', 'temporary record borrowing'],
    ['function bad(): void { $p other; write($p, seed()); }', 'conversion'],
    ['function bad($p packet): void {}', 'Struct function parameters by value'],
    ['function bad(int32 &$p): void {}', 'require a plain record'],
] as [$source, $reason])
{
    Check::edit($root . '/project/src/body.phs', $source);
    Check::edit($root . '/project/src/main.phs', 'return 0;');
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))->compile($manifest), $reason);
}
echo "source record borrowing: ok\n";
