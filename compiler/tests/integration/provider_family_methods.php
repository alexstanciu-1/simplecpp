<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/families/compiler_bridge.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;
use runtime_preparation\families as native;

final class Family_Methods_Test
{
    /** Capture observable construction/destruction across the real generated bridge. */
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

/** Replay prepared packages while deliberately omitting previously accepted method coverage. */
final class Incomplete_Family_Preparer implements \load_runtime\Family_Preparer
{
    public array $batches = [];

    public function __construct(private readonly array $initial, private readonly array $extended)
    {
    }

    /** Return authentic package members so only the incomplete coverage contract can reject this result. */
    public function prepare(array $tasks, \type_model\Type_Catalog $catalog, array $previous, array $packages = []): array
    {
        $this->batches[] = $tasks;
        $results = [];
        foreach ($tasks as $task)
        {
            $id = $task->context->instance_id;
            $prepared = ($task->operations === []) ? $this->initial[$id] : $this->extended[$id];
            $operations = $prepared->operations;
            if ($task->operations !== []) {
                unset($operations['count']);
            }
            $results[] = new \load_runtime\family_preparation_result($task, $prepared->package, $prepared->type_id, $operations);
        }
        return $results;
    }
}

$root = getcwd() . '/family-methods';
Files::directory($root . '/project');
Files::write($root . '/native.hpp', <<<'CPP'
#pragma once
#include <vector>
#include <cstdio>
#include <cstdint>
namespace fixture {
template<class T> struct list {
    std::vector<T> data;
    list() { std::putchar('C'); }
    ~list() { std::putchar('D'); }
};
template<class A, class B> struct holder {
    A first{}; B second{5};
    holder() { std::putchar('H'); }
    ~holder() { std::putchar('h'); }
};
inline std::int64_t answer() { return 41; }
template<class A, class B> B second(const holder<A, B>& value) { return value.second; }
template<class T> std::int64_t count(const list<T>& value) { return 10; }
template<class T> std::int64_t offset(std::int64_t first, const list<T>& value, std::int64_t last) { return first + last; }
}
CPP);
$definition = ['schema_version' => 1, 'types' => [], 'families' => [[
    'id' => 'list', 'cpp_name' => 'fixture::list', 'header' => 'native.hpp',
    'language_type' => ['name' => 'bag', 'namespace' => ''],
    'parameters' => [['name' => 'element', 'contract' => 'copyable_value']],
    'lifecycle' => ['construct' => 'create', 'destroy' => 'release'],
    'operations' => [
        ['id' => 'create', 'kind' => 'construct', 'type' => '$self', 'parameters' => [], 'error_policy' => 'terminate'],
        ['id' => 'release', 'kind' => 'destroy', 'type' => '$self', 'error_policy' => 'terminate'],
    ],
]]];
foreach (['int' => 'std::int64_t', 'int32' => 'std::int32_t', 'uint8' => 'std::uint8_t'] as $name => $cpp) {
    $definition['types'][] = ['id' => $name, 'kind' => 'integer', 'cpp_name' => $cpp, 'header' => 'cstdint',
        'language_type' => ['name' => $name, 'namespace' => '']];
}
$definition['types'][] = ['id' => 'nothing', 'kind' => 'void', 'cpp_name' => 'void', 'header' => 'cstddef',
    'language_type' => ['name' => 'void', 'namespace' => '']];
$definition['families'][0]['operations'][] = ['id' => 'count', 'kind' => 'free_function', 'cpp_name' => 'fixture::count', 'header' => 'native.hpp',
    'cpp_template_arguments' => ['$element'], 'parameters' => [['type' => '$self', 'passing' => 'const_address', 'borrow_scope' => 'call']],
    'result_type' => 'int', 'receiver' => 0, 'error_policy' => 'terminate', 'expose_as' => ['name' => 'count', 'namespace' => '']];
$definition['families'][0]['operations'][] = ['id' => 'offset', 'kind' => 'free_function', 'cpp_name' => 'fixture::offset', 'header' => 'native.hpp',
    'cpp_template_arguments' => ['$element'], 'parameters' => ['int', ['type' => '$self', 'passing' => 'const_address', 'borrow_scope' => 'call'], 'int'],
    'result_type' => 'int', 'receiver' => 1, 'error_policy' => 'terminate', 'expose_as' => ['name' => 'offset', 'namespace' => '']];
$pair = $definition['families'][0];
$pair['id'] = 'pair';
$pair['cpp_name'] = 'fixture::holder';
$pair['language_type']['name'] = 'duo';
$pair['parameters'][] = ['name' => 'other', 'contract' => 'copyable_value'];
$pair['operations'] = array_slice($pair['operations'], 0, 2);
$pair['operations'][] = ['id' => 'second', 'kind' => 'free_function', 'cpp_name' => 'fixture::second', 'header' => 'native.hpp',
    'cpp_template_arguments' => ['$element', '$other'], 'parameters' => [['type' => '$self', 'passing' => 'const_address', 'borrow_scope' => 'call']],
    'result_type' => '$other', 'receiver' => 0, 'error_policy' => 'terminate', 'expose_as' => ['name' => 'second', 'namespace' => '']];
$definition['families'][] = $pair;
$catalog = native\Catalog::parse($definition, 'fixture');
$declarations = \load_runtime\Family_Adapter::expose(array_map(static fn($family) => $family->semantic, array_values($catalog->families)), native\Catalog::language_bindings($catalog));
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$provider = new native\compiler_provider($catalog, $root . '/generated', 'fixture-runtime-v1',
    ['clang' => $config['clang'], 'target' => $config['target'], 'standard' => $config['standard'], 'include_directories' => [$root]]);
$bridge = new native\Compiler_Bridge([$provider]);
// Calls from explicit arguments still share the ordinary package composition path.
Files::directory($root . '/ordinary-definitions');
Files::write_json($root . '/ordinary-definitions/types.json', ['schema_version' => 1,
    'types' => [$definition['types'][0]],
    'operations' => [
        ['id' => 'answer', 'kind' => 'free_function', 'cpp_name' => 'fixture::answer', 'header' => 'native.hpp',
            'parameters' => [], 'result_type' => 'int', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'native_answer', 'namespace' => '']],
    ]]);
$ordinary = $config;
$ordinary['provider'] = 'ordinary';
$ordinary['definitions_directory'] = $root . '/ordinary-definitions';
$ordinary['include_directories'] = [$root];
$ordinary['output_directory'] = $root . '/ordinary';
Files::write_json($root . '/ordinary.json', $ordinary);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/ordinary.json');
Files::write($root . '/project/functions.phs', 'template<typename T> function make(): int { $v bag<T>; return $v->count(); }');
$source = $root . '/project/main.phs';
Files::write($source, '$a bag<int>; $p duo<uint8, int>; return $a->count() + $a->count() + make<int32>() + $p->second();');
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
$session = new \compile\Compiler_Session(runtime_package_path: $root . '/ordinary', family_declarations: $declarations, family_preparer: $bridge);
$first = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Family_Methods_Test::execute($root . '/program') === [35, 'CHCDhD', ''], 'Family methods execute from concrete and generic source through ordinary signatures');
$before = serialize($first);
$old = array_values($first->types->families)[0];
$old_type = $old->package->type_for($old->type_id)->language_type;
$old_call = $old->operations['count'];
Check::check((count($first->types->families) === 3) && (count($old->operations) === 1), 'Repeated method occurrences coalesce by semantic instance and operation');
Check::edit($source, '$a bag<int>; $p duo<uint8, int>; return $a->offset(native_answer(), $a->count()) + make<int32>() + $p->second();');
$second = $session->compile($root . '/project/project.json', $root . '/program');
$new = array_values($second->types->families)[0];
Check::check(!$second->inputs->context->full_rebuild && (Family_Methods_Test::execute($root . '/program') === [66, 'CHCDhD', '']),
    'A body increment grows operation coverage; receiver may follow an explicit argument');
Check::check(($new->package !== $old->package) && ($new->package->directory === $old->package->directory)
    && ($new->package->type_for($new->type_id)->language_type === $old_type) && ($new->operations['count'] === $old_call)
    && (count($new->operations) === 2) && (serialize($first) === $before),
    'Coverage replacement retains type/callable identity and the previous immutable snapshot');
// Operation-result joins must preserve exact identity as well as compatible signatures.
$join = new \load_runtime\Family_Preparation_Join([$new->task], $new->package->base_catalog);
$missing = new \load_runtime\family_preparation_result($new->task, $new->package, $new->type_id);
Check::rejects(static fn() => $join->join([$missing]), 'Incomplete family operation coverage');
$wrong_calls = $new->operations;
$wrong_calls['offset'] = $new->operations['count'];
$wrong = new \load_runtime\family_preparation_result($new->task, $new->package, $new->type_id, $wrong_calls);
Check::rejects(static fn() => $join->join([$wrong]), 'Unexpected prepared family operation');

// Missing coverage selects work, but acceptance must cover every method still used by current callers.
$incomplete = new Incomplete_Family_Preparer($first->types->families, $second->types->families);
$preparation = new \load_runtime\Family_Preparation($incomplete, $new->package->base_catalog);
$tasks = array_map(static fn($prepared) => new \load_runtime\family_preparation_task($prepared->task->context),
    array_values($second->types->families));
$preparation->prepare_types($tasks);
$accepted_before = serialize($preparation->result());
Check::rejects(static fn() => $preparation->prepare_methods($second->types->instances), 'Incomplete family operation coverage');
Check::check(serialize($preparation->result()) === $accepted_before,
    'Incomplete coverage extension is rejected before replacing accepted preparation associations');
$required = $incomplete->batches[1][0]->operations;
sort($required);
Check::check((count($incomplete->batches[1]) === 1) && ($required === ['count', 'offset']),
    'Only the missing specialization is selected, with all methods needed by current callers');
$debug = json_decode($second->types->to_json(), true, flags: JSON_THROW_ON_ERROR);
Check::check(count(array_filter($debug['signatures'], static fn($row) => $row['receiver_index'] === 1)) === 1,
    'Concrete signature exports retain the declared receiver position');
echo "family methods ok: concrete and generic calls, receiver positions, coalesced coverage and one method-demand increment\n";
