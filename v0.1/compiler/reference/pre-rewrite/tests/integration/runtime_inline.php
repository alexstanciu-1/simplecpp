<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Inline_Runtime_Test
{
    public static function status(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        Check::check(is_resource($process), 'Start inline consumer');
        return proc_close($process);
    }
}

$root = getcwd() . '/inline-runtime';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
Files::write($root . '/objects.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdlib>
namespace sample {
class alignas(64) stable {
    const stable *identity;
    std::int64_t value;
public:
    explicit stable(std::int64_t v) : identity(this), value(v) {}
    stable(const stable &) = delete;
    std::int64_t read() const {
        if (identity != this || reinterpret_cast<std::uintptr_t>(this) % alignof(stable)) std::abort();
        return value;
    }
};
struct pair {
    std::int64_t first, second;
    pair(std::int64_t a, std::int64_t b) : first(a), second(b) {}
    std::int64_t sum() const { return first + second; }
};
}
CPP);
$types = [['id' => 'integer', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
    'language_type' => ['name' => 'int', 'namespace' => '']]];
$operations = [];
foreach ([['stable', ['integer'], 'read'], ['pair', ['integer', 'integer'], 'sum']] as [$name, $parameters, $member])
{
    $types[] = ['id' => $name, 'kind' => 'runtime_value', 'cpp_name' => 'sample::' . $name,
        'header' => 'objects.hpp', 'storage' => 'inline', 'lifecycle' => ['construct' => $name . '.make', 'cleanup' => 'none'],
        'language_type' => ['name' => $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.make', 'kind' => 'construct', 'type' => $name, 'parameters' => $parameters,
        'error_policy' => 'terminate', 'expose_as' => ['name' => 'make_' . $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.read', 'kind' => 'const_method', 'type' => $name, 'member' => $member,
        'result_type' => 'integer', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
        'expose_as' => ['name' => 'read_' . $name, 'namespace' => '']];
}
$definitions = ['schema_version' => 1, 'types' => $types, 'operations' => $operations];
Files::write_json($root . '/definitions/objects.json', $definitions);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['provider'] = 'inline_fixture';
$config['include_directories'] = [$root];
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/generated';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$catalog = Files::json(dirname(__DIR__, 2) . '/language/named_types.json');
$catalog['literal_types']['integer']['name'] = 'int32';
Files::write_json($root . '/types.json', $catalog);
Files::write_json($root . '/project/project.json', ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
$main = $root . '/project/src/main.phs';
$helper = $root . '/project/src/helper.phs';
Files::write($helper, 'function seed(): int32 { return 7; }');
$source = <<<'PHS'
$a stable = make_stable(seed());
$b pair = make_pair(read_stable($a), read_stable(make_stable(8)));
$sum int = read_pair($b);
$i int32 = 2;
$again int32 = 1;
while ($i) {
    $inner stable = make_stable($i);
    $sum = $sum + read_stable($inner);
    if ($again) {
        $again = 0;
    }
    else {
        $i = 0;
    }
}
if ($sum) {
    $early stable = make_stable(9);
    return $sum + read_stable($early);
}
else {
    return 0;
}
PHS;
Files::write($main, $source);
$session = new \compile\Compiler_Session(type_catalog_path: $root . '/types.json', runtime_package_path: $config['output_directory']);
$manifest = $root . '/project/project.json';
$output = $root . '/program';
$first = $session->compile($manifest, $output);
Check::check(($first->completed) && (Inline_Runtime_Test::status($output) === 28), 'Constructed locals/temporaries, nested borrows, repeated loop construction, branch and early return execute');
$entry = $first->types->entry->symbol->symbol_id;
$plan = $first->lowered->for_symbol($entry);
$ir = $first->llvm->function_for($entry)->ir;
Check::check(str_contains($ir, 'alloca [64 x i8], align 64') && str_contains($ir, 'sext i32'), 'Measured over-alignment and normal scalar widening');
Check::check(!preg_match('/(?:load|store) \[\d+ x i8\]/', $ir), 'Opaque objects are not copied by aggregate load/store');
Check::check(count(array_filter($plan->slots, static fn($slot) => $slot->source_local_id === 0)) === 1, 'One constructed temporary has its own storage slot');
Check::check(count(array_filter($plan->instructions, static fn($op) => $op->kind === \lower\instruction_kind::borrow)) === 4, 'Local accesses are explicit borrows');
$lifetime = $first->lifetimes->for_symbol($entry);
Check::check(count(array_filter($lifetime->lifetimes, static fn($row) => $row->end === \analyze_lifetimes\lifetime_end::local_construct)) === 4,
    'Lifetime analysis distinguishes four local constructions from copies');
$exports = json_decode($first->to_json(), true, 512, JSON_THROW_ON_ERROR);
Check::check(str_contains(json_encode($exports, JSON_THROW_ON_ERROR), 'caller_storage'), 'Debug exports expose semantic/ABI result distinction');

// Workers receive the same fixed inputs irrespective of execution/completion order.
$before = serialize($first);
$tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->lowered->to_json()) && (serialize($first) === $before), 'Reversed lowering workers join deterministically without mutating inputs');
Check::edit($helper, 'function seed(): int32 { return 8; }');
$second = $session->compile($manifest, $output);
Check::check((!$second->inputs->context->full_rebuild) && (Inline_Runtime_Test::status($output) === 29)
    && ($second->backend->runtime === $first->backend->runtime)
    && ($second->lowered->for_symbol($entry) === $plan)
    && ($second->native->object_for($plan->source_file_id()) === $first->native->object_for($plan->source_file_id()))
    && (serialize($first) === $before), 'One body increment retains provider types, caller plans/objects and old snapshots');

// Unsupported ownership actions fail before publication; no implicit byte-copy fallback.
$published = $session->published;
foreach ([
    '$a stable = make_stable(1); $b stable = $a; return 0;' => 'copy construction is unavailable',
    '$a stable = make_stable(1); $a = make_stable(2); return 0;' => 'assignment is unavailable',
    'function consume($v stable): int { return read_stable($v); } return consume(make_stable(1));' => 'value argument',
    'return read_stable($not_initialized);' => 'Unknown local',
] as $bad => $message) {
    Check::edit($main, $bad);
    Check::rejects(static fn() => $session->compile($manifest, $output), $message);
    Check::check(($session->published === $published) && (Inline_Runtime_Test::status($output) === 29), 'Rejected ownership operation preserves native publication');
}

// Forwarding a fresh noncopyable value requires no copy or move bridge.
Files::write($root . '/returned.phs', 'function escape(): stable { return make_stable(9); } $value stable = escape(); return read_stable($value);');
(new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))->compile($root . '/returned.phs', $root . '/returned');
Check::check(Inline_Runtime_Test::status($root . '/returned') === 9, 'Fresh noncopyable result constructs directly in caller storage');

// Contract support is checked on import; const C++ syntax alone does not promise non-retention.
$no_scope = $definitions;
unset($no_scope['operations'][1]['borrow_scope']);
Files::write_json($root . '/definitions/objects.json', $no_scope);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
Check::edit($main, $source);
Check::rejects(static fn() => $session->compile($manifest, $output), 'Unsupported runtime parameter');
Check::check($session->published === $published, 'Unspecified borrow lifetime cannot enter accepted compiler state');
Files::write_json($root . '/definitions/objects.json', $definitions);

// Explicit cleanup:none must agree with the actual C++ type, not just a boolean in JSON.
$header = Files::read($root . '/objects.hpp');
Files::write($root . '/objects.hpp', str_replace('stable(const stable &) = delete;',
    'stable(const stable &) = delete; ~stable() {}', $header));
$accepted_pointer = Files::read($config['output_directory'] . '/current.json');
Check::rejects(static fn() => (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json'), 'cleanup none requires trivial destruction');
Check::check(Files::read($config['output_directory'] . '/current.json') === $accepted_pointer, 'Failed cleanup contract verification preserves prepared publication');
Files::write($root . '/objects.hpp', $header);
Files::write_json($root . '/definitions/objects.json', $definitions);
echo "inline runtime ok: generic opaque types, destination construction, call-scoped borrowing, aligned identity, flow, fixed workers, one increment and unsupported ownership rejection\n";
