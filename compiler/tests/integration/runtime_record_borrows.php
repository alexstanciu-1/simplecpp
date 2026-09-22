<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Record_Borrow_Test
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

$root = getcwd() . '/record-borrow-proof';
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
inline std::int32_t read(const pair &value) { return value.total + value.marker; }
inline std::int32_t read_reverse(const reverse &value) { return value.total + value.marker; }
inline std::int32_t same(const pair &left, const pair &right) { return &left == &right; }
inline std::int32_t add(const pair &value, std::int32_t bias) { return read(value) + bias; }
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
// Both record types use the same metadata parameter contract and compiler path.
foreach (['read' => ['pair'], 'read_reverse' => ['reverse'], 'same' => ['pair', 'pair'], 'add' => ['pair', 'wide']] as $name => $parameters)
{
    $definition['operations'][] = ['id' => $name, 'kind' => 'free_function', 'cpp_name' => 'native::' . $name,
        'header' => 'native.hpp', 'parameters' => array_map(static fn($type) => $type === 'wide' ? $type
            : ['type' => $type, 'passing' => 'const_address', 'borrow_scope' => 'call'], $parameters),
        'result_type' => 'wide', 'error_policy' => 'terminate', 'expose_as' => ['name' => $name, 'namespace' => '']];
}
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['include_directories'] = [$root];
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
Files::write_json($root . '/definitions/records.json', $definition);
$tool = new \runtime_preparation\Runtime_Preparation();
Check::check($tool->run($root . '/config.json')['status'] === 'built', 'Prepare record borrowing from JSON');
Check::check($tool->run($root . '/config.json')['status'] === 'reused', 'Reuse unchanged record borrow artifacts');
$metadata = Files::json($config['output_directory'] . '/package/metadata.json');
$operations = array_column($metadata['operations'], null, 'id');
foreach (['read', 'read_reverse', 'same', 'add'] as $name)
{
    $parameter = $operations[$name]['parameters'][0];
    Check::check(($parameter['passing'] === 'const_address') && ($parameter['ownership'] === 'borrowed')
        && ($parameter['borrow_scope'] === 'call') && ($parameter['abi_indices'] === [0])
        && ($operations[$name]['abi']['parameters'][0]['type'] === 'ptr'), 'Measured record ABI is one borrowed pointer');
}

$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'return exercise();');
$body = <<<'PHS'
function through_source(const provided_pair &$value): int32 { return read($value); }
function through_aliases(const provided_pair &$left, const provided_pair &$right): int32 { return same($left, $right); }
/** Borrow local storage, retain independent copies, then write after the call ends. */
function exercise(): int
{
    $value provided_pair;
    if (through_source($value)) {
        return 97;
    }
    $value->amount = seed();
    $value->tag = tag();
    $copy provided_pair = $value;
    if (through_aliases($value, $value)) {
        if (through_aliases($value, $copy)) {
            return 98;
        }
    }
    else {
        return 99;
    }
    $value->amount = seed() + seed();
    $reverse provided_reverse;
    $reverse->amount = seed();
    return add($value, through_source($copy)) + read_reverse($reverse);
}
PHS;
$body_path = $root . '/project/src/body.phs';
Files::write($body_path, $body);
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
Check::check(Record_Borrow_Test::run([$root . '/program']) === [74, '', ''], 'Native calls observe zero/initialized fields, alias identity, independent copies and nested borrows');
$before = serialize($first);
$types = $first->types->types;
$symbols = $first->symbols->current;
$exercise = $symbols->find_symbol('exercise', '', \collect_symbols\symbol_kind::function_symbol);
$checked = $first->bodies->for_symbol($exercise);
$lowered = $first->lowered->for_symbol($exercise);
$borrows = 0;
$copies = 0;
foreach ($lowered->instructions as $instruction)
{
    if ($instruction->kind === \lower\instruction_kind::borrow)
    {
        $value = $lowered->values[$instruction->result_value_id - 1];
        $slot = $lowered->slot_for($value->storage_slot_id);
        Check::check(($slot->source_local_id !== 0) && ($instruction->payload->slot_id === $value->storage_slot_id)
            && ($instruction->payload->projections === []), 'Each borrow directly uses its existing root local slot');
        ++$borrows;
    }
    elseif (($instruction->kind === \lower\instruction_kind::load)
        && ($lowered->definition_for($lowered->values[$instruction->result_value_id - 1]->type_id)->representation->kind
            === \type_model\representation_kind::structure)) {
        ++$copies;
    }
}
Check::check(($borrows === 8) && ($copies === 1) && (count($lowered->slots) === 3),
    'Borrow calls add no temporary record slots or aggregate loads; ordinary copy still loads by value');

// Borrow access ends at the consuming call, without becoming an object destruction obligation.
$analysis = $first->lifetimes->for_symbol($exercise);
$ends = [];
foreach ($analysis->lifetimes as $lifetime) {
    $ends[$lifetime->value_id] = $lifetime;
}
foreach ($checked->values as $index => $value) {
    if ($value->kind === \check_bodies\value_kind::local_borrow) {
        Check::check(($ends[$index + 1]->end === \analyze_lifetimes\lifetime_end::argument_borrow)
            && ($ends[$index + 1]->consumer_id > 0), 'Each record borrow ends at its one consuming call');
    }
}
Check::check($analysis->cleanups === [], 'No-cleanup records create no destruction obligations');

// Signature workers resolve exact names against accepted records without changing shared inputs.
$view = new \resolve_types\Definition_View($first->types->catalog, $types);
$tasks = \resolve_types\Signature_Resolver::select($symbols, null, $types, true, $first->types->entry);
$requests = array_map(static fn($task) => \resolve_types\Signature_Resolver::resolve($symbols, $view, $task, $first->types->entry, $first->resolutions), array_reverse($tasks));
$candidate = clone $types;
$join = new \resolve_types\Signature_Join($symbols, $view, $candidate, null, $tasks, $first->types->entry, $first->resolutions);
$unchanged = serialize($candidate);
$bad = $requests;
foreach ($bad as $index => $request)
{
    if ($request->symbol->name === 'read')
    {
        $parameters = $request->parameter_definitions;
        $parameters[0] = clone $parameters[0];
        $forged = new \resolve_types\signature_request($request->symbol, 0, $request->definition, $parameters);
        array_splice($bad, $index, 1);
        $bad[] = $forged;
        break;
    }
}
Check::rejects(static fn() => $join->join($bad), 'different language catalog');
Check::check(serialize($candidate) === $unchanged, 'Last invalid signature rejects before any candidate materialization');
$wrong = $requests;
foreach ($wrong as $index => $request) {
    if ($request->symbol->name === 'read') {
        $wrong[$index] = new \resolve_types\signature_request($request->symbol, 0, $request->definition,
            [$types->definition_for_type($types->find_type('provided_reverse'))]);
        break;
    }
}
Check::rejects(static fn() => $join->join($wrong), 'different language catalog');
Check::check(serialize($candidate) === $unchanged, 'A genuine but differently named definition cannot satisfy a reference');
$accepted = $join->join($requests);
Check::check((count($accepted) === count($tasks)) && (serialize($first) === $before), 'Reversed signature completion preserves retained inputs');

$body_tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $symbols, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$body_results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($body_tasks));
$joined = (new \check_bodies\Body_Join($symbols, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), $body_tasks))->join($body_results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before),
    'Contextual borrowing is confined to private checked output');

// One supported body edit reuses the exact provider signatures and target layouts.
Check::edit($body_path, str_replace('seed() + seed();', 'seed() + seed() + seed();', $body));
$second = $session->compile($manifest, $root . '/program');
$read = $symbols->find_symbol('read', '', \collect_symbols\symbol_kind::function_symbol);
Check::check((Record_Borrow_Test::run([$root . '/program']) === [91, '', ''])
    && (!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->types->for_symbol($read) === $first->types->for_symbol($read))
    && ($second->bodies->for_symbol($exercise) !== $checked) && (serialize($first) === $before),
    'Body replacement changes native behavior, shares contracts/layouts and preserves the previous snapshot');

// Equal layouts never authorize a borrow across nominal type boundaries.
foreach ([
    ['$p provided_reverse; return read($p);', 'Unsupported'],
    ['struct other { public uint8 $tag; public int32 $amount; } $p other; return read($p);', 'Unsupported'],
    ['return read(new provided_pair());', 'existing local'],
    ['$p provided_pair; return read($p->amount);', 'Unsupported'],
    ['{ $p provided_pair; } return read($p);', 'Unknown local'],
] as [$source, $reason]) {
    Files::write($root . '/bad.phs', $source);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
        ->compile($root . '/bad.phs', $root . '/bad'), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Invalid borrowing reports a source diagnostic');
}

// Reject retained borrowing, a mutable contract over a const-only native function, and direct aggregate passing.
$published = Files::read($config['output_directory'] . '/current.json');
foreach ([
    [['type' => 'pair', 'passing' => 'const_address', 'borrow_scope' => 'retained'], 'call-scoped const or mutable borrowing'],
    [['type' => 'pair', 'passing' => 'mutable_address', 'borrow_scope' => 'call'], 'static_cast'],
    ['pair', 'Direct function parameter'],
] as [$parameter, $reason]) {
    $bad = $definition;
    $bad['operations'][2]['parameters'][0] = $parameter;
    Files::write_json($root . '/definitions/records.json', $bad);
    Check::rejects(static fn() => $tool->run($root . '/config.json'), $reason);
    Check::check(Files::read($config['output_directory'] . '/current.json') === $published, 'Unsupported contracts preserve prepared artifacts');
}

echo "record borrowing ok: JSON to native, local address identity, value copies, signature joins, worker purity and body replacement\n";
