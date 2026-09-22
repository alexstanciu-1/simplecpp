<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

require_once __DIR__ . '/../support/lifecycle_support.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Cleanup_Runtime_Test
{
    /** Run a native probe and retain status, stdout and stderr for exact oracle comparison. */
    public static function execute(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start cleanup probe');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

    /** Reverse two adjacent obligations at one boundary and require result validation to reject their order. */
    public static function reject_reordered(\analyze_lifetimes\Analyzed_Body $analysis,
        \analyze_lifetimes\cleanup_subject $first, \analyze_lifetimes\cleanup_subject $second): void
    {
        $cleanups = $analysis->cleanups;
        for ($index = 1; $index < count($cleanups); ++$index)
        {
            $left = $cleanups[$index - 1];
            $right = $cleanups[$index];
            if (($left->subject !== $first) || ($right->subject !== $second)
                || ($left->block_id !== $right->block_id) || ($left->after_statement !== $right->after_statement)) {
                continue;
            }

            // Membership and boundary facts stay valid; only destruction order changes.
            [$cleanups[$index - 1], $cleanups[$index]] = [$right, $left];
            Check::rejects(static fn() => new \analyze_lifetimes\Analyzed_Body($analysis->body, $analysis->lifetimes,
                $analysis->reachable_statement_count, $analysis->falls_through, $analysis->local_lifetimes,
                $analysis->reachable_blocks, $cleanups), 'cleanup destruction order');
            return;
        }
        throw new \Exception('Missing same-boundary cleanup pair: ' . $first->value . '/' . $second->value);
    }
}

$root = getcwd() . '/cleanup-runtime';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
Files::write($root . '/objects.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <cstdio>
#include <cstdlib>
namespace sample {
inline int live = 0;
struct ticket {
    std::int64_t *value;
    explicit ticket(std::int64_t v) : value(new std::int64_t(v)) { ++live; std::printf("C:%lld\n", (long long)v); }
    ticket(const ticket&) = delete;
    ~ticket() { std::printf("D:%lld\n", (long long)*value); delete value; --live; }
    std::int64_t read() const { std::printf("R:%lld:%d\n", (long long)*value, live); return *value; }
};
class alignas(64) first {
    const first *identity;
    ticket owned;
public:
    explicit first(std::int64_t v) : identity(this), owned(v) {}
    first(const first&) = delete;
    std::int64_t read() const { if (identity != this) std::abort(); return owned.read(); }
};
struct second {
    std::int64_t padding;
    ticket owned;
    second(std::int64_t a, std::int64_t b) : padding(a), owned(a + b) {}
    second(const second&) = delete;
    std::int64_t read() const { return owned.read(); }
};
inline std::int64_t live_count() { return live; }
}
CPP);
$types = [['id' => 'integer', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
    'language_type' => ['name' => 'int', 'namespace' => '']]];
$operations = [];
foreach ([['first', ['integer']], ['second', ['integer', 'integer']]] as [$name, $parameters])
{
    $types[] = ['id' => $name, 'kind' => 'runtime_value', 'cpp_name' => 'sample::' . $name, 'header' => 'objects.hpp',
        'storage' => 'inline', 'lifecycle' => ['construct' => $name . '.make', 'destroy' => $name . '.destroy'],
        'language_type' => ['name' => $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.make', 'kind' => 'construct', 'type' => $name, 'parameters' => $parameters,
        'error_policy' => 'terminate', 'expose_as' => ['name' => 'make_' . $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.read', 'kind' => 'const_method', 'type' => $name, 'member' => 'read',
        'result_type' => 'integer', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
        'expose_as' => ['name' => 'read_' . $name, 'namespace' => '']];
    $operations[] = ['id' => $name . '.destroy', 'kind' => 'destroy', 'type' => $name, 'error_policy' => 'terminate'];
}
$operations[] = ['id' => 'live', 'kind' => 'free_function', 'header' => 'objects.hpp', 'cpp_name' => 'sample::live_count',
    'parameters' => [], 'result_type' => 'integer', 'error_policy' => 'terminate',
    'expose_as' => ['name' => 'live_count', 'namespace' => '']];
Files::write_json($root . '/definitions/objects.json', ['schema_version' => 1, 'types' => $types, 'operations' => $operations]);
$config = Files::json(dirname(__DIR__, 2) . '/src-runtime-preparation/config.json');
$config['provider'] = 'cleanup_fixture';
$config['include_directories'] = [$root];
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/generated';
Files::write_json($root . '/config.json', $config);
(new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
Files::write_json($root . '/project/project.json', ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
$main = $root . '/project/src/main.phs';
$helper = $root . '/project/src/helper.phs';
Files::write($helper, 'function seed(): int { return 7; }');
Files::write($main, <<<'PHS'
function early($flag int): int {
    $outer first = make_first(40);
    if ($flag) {
        $inner second = make_second(20, 21);
        return read_first(make_first(42)) + read_second(make_second(20, 23));
    }
    return 0;
}
function discard(): void {
    $a first = make_first(50);
    {
        $b second = make_second(25, 26);
    }
    make_first(53);
}
function run(): int {
    $a first = make_first(seed());
    $b second = make_second(1, 1);
    $total int = read_first(make_first(3)) + read_second(make_second(4, 5));
    $x first = make_first(read_first(make_first(10)) + 1);
    $total = $total + read_first($x);
    $i int = 2;
    $again int = 1;
    while (read_first(make_first($i))) {
        $loop first = make_first(20);
        if ($again) {
            $branch second = make_second(10, 20);
            $again = 0;
        }
        else {
            $i = 0;
        }
    }
    make_first(31);
    discard();
    $total = $total + early(1) + early(0);
    return $total + read_first($a) + read_second($b);
}
$result int = run();
return $result + live_count();
PHS);

// Native C++ oracle for the scoped value/reference rules selected by Simple C++.
// It intentionally retains native full expressions so early argument destruction differs observably.
Files::write($root . '/oracle.cpp', <<<'CPP'
#include "objects.hpp"
#ifndef SEED
#define SEED 7
#endif
using sample::first; using sample::second;
static std::int64_t read_first(const first& v) { return v.read(); }
static std::int64_t read_second(const second& v) { return v.read(); }
static std::int64_t early(std::int64_t flag) {
    first outer(40);
    if (flag) {
        second inner(20, 21);
        return read_first(first(42)) + read_second(second(20, 23));
    }
    return 0;
}
static void discard() {
    first a(50);
    { second b(25, 26); }
    first(53);
}
static std::int64_t run() {
    first a(SEED);
    second b(1, 1);
    std::int64_t total = read_first(first(3)) + read_second(second(4, 5));
    first x(read_first(first(10)) + 1);
    total = total + read_first(x);
    std::int64_t i = 2, again = 1;
    while (read_first(first(i))) {
        first loop(20);
        if (again) { second branch(10, 20); again = 0; }
        else { i = 0; }
    }
    first(31);
    discard();
    total = total + early(1) + early(0);
    return total + read_first(a) + read_second(b);
}
int main() { auto result = run(); return result + sample::live_count(); }
CPP);
$package = Files::json($config['output_directory'] . '/package/manifest.json');
$clang = $package['link_driver']['executable'];
$oracle = $root . '/oracle';
[$status, , $error] = Cleanup_Runtime_Test::execute([$clang, '--driver-mode=g++', '--target=' . $package['target']['triple'],
    '-std=c++23', $root . '/oracle.cpp', '-o', $oracle]);
Check::check($status === 0, 'Compile native cleanup oracle: ' . $error);
$expected = Cleanup_Runtime_Test::execute([$oracle]);
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$manifest = $root . '/project/project.json';
$output = $root . '/program';
$first = $session->compile($manifest, $output);
Lifecycle_Test::preparation($first);
$actual = Cleanup_Runtime_Test::execute([$output]);
Check::check($actual === $expected, "Cleanup trace/status must match native scoped lifetimes\n" . json_encode([$actual, $expected]));
Check::check(substr_count($actual[1], 'C:') === substr_count($actual[1], 'D:'), 'Every reached construction has exactly one destruction');
Check::check(str_contains($actual[1], "C:3\nR:3:3\nC:9\nR:9:4\nD:9\nD:3\n"), 'Nested borrowed temporaries survive to the full-expression end and die in reverse construction order');
$run_id = $first->symbols->current->find_symbol('run', '', \collect_symbols\symbol_kind::function_symbol);
$plan = $first->lowered->for_symbol($run_id);
$analysis = $first->lifetimes->for_symbol($run_id);
Check::check(count($analysis->cleanups) === count(array_filter($plan->instructions,
    static fn($instruction) => $instruction->kind === \lower\instruction_kind::destroy)), 'Each analysis obligation lowers to one explicit destruction instruction');
Check::rejects(static fn() => new \analyze_lifetimes\Analyzed_Body($analysis->body, $analysis->lifetimes,
    $analysis->reachable_statement_count, $analysis->falls_through, $analysis->local_lifetimes,
    $analysis->reachable_blocks, array_slice($analysis->cleanups, 1)), 'Missing owned-object cleanup');

// The early return shares one boundary between two temporaries and two exited locals.
$early_id = $first->symbols->current->find_symbol('early', '', \collect_symbols\symbol_kind::function_symbol);
$early_analysis = $first->lifetimes->for_symbol($early_id);
Cleanup_Runtime_Test::reject_reordered($early_analysis, \analyze_lifetimes\cleanup_subject::temporary, \analyze_lifetimes\cleanup_subject::temporary);
Cleanup_Runtime_Test::reject_reordered($early_analysis, \analyze_lifetimes\cleanup_subject::local, \analyze_lifetimes\cleanup_subject::local);
Cleanup_Runtime_Test::reject_reordered($early_analysis, \analyze_lifetimes\cleanup_subject::temporary, \analyze_lifetimes\cleanup_subject::local);

$before = serialize($first);
$tasks = \Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $first->bodies, new \analyze_lifetimes\Lifetime_Set(), true);
$results = array_map(static fn($task) => (new \analyze_lifetimes\Lifetime_Worker($task))->analyze(), array_reverse($tasks));
$joined = (new \analyze_lifetimes\Lifetime_Join($first->bodies, new \analyze_lifetimes\Lifetime_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->lifetimes->to_json()) && (serialize($first) === $before), 'Cleanup analysis uses fixed inputs and deterministic joins');
Check::edit($helper, 'function seed(): int { return 8; }');
$second = $session->compile($manifest, $output);
$changed = Cleanup_Runtime_Test::execute([$output]);
Check::check(($changed[0] === ($expected[0] + 1)) && ($changed[1] === str_replace(['C:7', 'R:7', 'D:7'], ['C:8', 'R:8', 'D:8'], $expected[1]))
    && (!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->lowered->for_symbol($run_id) === $plan)
    && ($second->native->object_for($plan->source_file_id()) === $first->native->object_for($plan->source_file_id()))
    && (serialize($first) === $before), 'One body increment preserves cleanup plans, caller objects and retained snapshots');

// A separate session proves one full build and one edit inside a managed body.
// Its caller and an unrelated managed function live in separate unchanged files.
$managed_root = $root . '/managed-project';
Files::directory($managed_root . '/src');
$managed_manifest = $managed_root . '/project.json';
Files::write_json($managed_manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($managed_root . '/src/main.phs', 'return managed(1) + managed(0) + unrelated() + live_count();');
Files::write($managed_root . '/src/unrelated.phs', <<<'PHS'
function unrelated(): int
{
    $value first = make_first(20);
    return read_first($value);
}
PHS);
$managed_path = $managed_root . '/src/managed.phs';
$managed_source = <<<'PHS'
/** Exercise both exits while owning a local and a returned expression temporary. */
function managed($flag int): int
{
    $outer first = make_first(10);
    if ($flag) {
        return read_first(make_first(11));
    }
    return read_second(make_second(5, 7));
}
PHS;
Files::write($managed_path, $managed_source);

// Compile both native oracles independently of the compiler's cleanup plan.
$managed_oracle = $root . '/managed-oracle';
Files::write($managed_oracle . '.cpp', <<<'CPP'
#include "objects.hpp"
using sample::first; using sample::second;
static std::int64_t read_first(const first& value) { return value.read(); }
static std::int64_t read_second(const second& value) { return value.read(); }
static std::int64_t unrelated() {
    first value(20);
    return read_first(value);
}
static std::int64_t managed(std::int64_t flag) {
    first outer(10);
    if (flag) {
#if MANAGED_EDIT
        second inner(6, 7);
        return read_first(first(14)) + read_second(second(7, 8));
#else
        return read_first(first(11));
#endif
    }
    return read_second(second(5, 7));
}
int main() { return managed(1) + managed(0) + unrelated() + sample::live_count(); }
CPP);
$managed_expected = [];
foreach ([0, 1] as $edit) {
    [$status, , $error] = Cleanup_Runtime_Test::execute([$clang, '--driver-mode=g++', '--target=' . $package['target']['triple'],
        '-std=c++23', '-DMANAGED_EDIT=' . $edit, $managed_oracle . '.cpp', '-o', $managed_oracle]);
    Check::check($status === 0, 'Compile managed-body oracle: ' . $error);
    $managed_expected[] = Cleanup_Runtime_Test::execute([$managed_oracle]);
}

$managed_session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$managed_output = $managed_root . '/program';
$managed_baseline = $managed_session->compile($managed_manifest, $managed_output);
Check::check(Cleanup_Runtime_Test::execute([$managed_output]) === $managed_expected[0], 'Managed baseline matches native cleanup');
$managed_id = $managed_baseline->symbols->current->find_symbol('managed', '', \collect_symbols\symbol_kind::function_symbol);
$unrelated_id = $managed_baseline->symbols->current->find_symbol('unrelated', '', \collect_symbols\symbol_kind::function_symbol);
$managed_plan = $managed_baseline->lowered->for_symbol($managed_id);
$managed_analysis = $managed_baseline->lifetimes->for_symbol($managed_id);
$managed_before = serialize($managed_baseline);

// Add a nested local and a second temporary at the early return, keeping the callable contract fixed.
Check::edit($managed_path, str_replace('        return read_first(make_first(11));', <<<'PHS'
        $inner second = make_second(6, 7);
        return read_first(make_first(14)) + read_second(make_second(7, 8));
PHS, $managed_source));
$managed_updated = $managed_session->compile($managed_manifest, $managed_output);
Check::check(Cleanup_Runtime_Test::execute([$managed_output]) === $managed_expected[1], 'Managed-body increment matches edited native cleanup on both paths');
Check::check((!$managed_updated->inputs->context->full_rebuild) && ($managed_updated->backend === $managed_baseline->backend),
    'Managed-body edit retains the unchanged backend contract without a full rebuild');
Check::check(($managed_updated->lifetimes->for_symbol($managed_id) !== $managed_analysis)
    && ($managed_updated->lowered->for_symbol($managed_id) !== $managed_plan)
    && (count($managed_updated->lifetimes->for_symbol($managed_id)->cleanups) === (count($managed_analysis->cleanups) + 2))
    && ($managed_updated->llvm->function_for($managed_id) !== $managed_baseline->llvm->function_for($managed_id))
    && ($managed_updated->native->object_for($managed_plan->source_file_id()) !== $managed_baseline->native->object_for($managed_plan->source_file_id())),
    'Managed-body edit replaces its cleanup analysis, lowered plan, emission and native object');

// Every function in an unchanged file keeps its analysis, plan, emission and compiled object.
$unchanged_functions = 0;
foreach ($managed_baseline->llvm->modules as $module)
{
    if ($module->source_file_id === $managed_plan->source_file_id()) {
        continue;
    }
    Check::check($managed_updated->native->object_for($module->source_file_id) === $managed_baseline->native->object_for($module->source_file_id),
        'Managed-body edit preserves unchanged native objects');
    foreach ($module->functions as $function) {
        $id = $function->body->binding->callable_id;
        Check::check(($managed_updated->lifetimes->for_symbol($id) === $managed_baseline->lifetimes->for_symbol($id))
            && ($managed_updated->lowered->for_symbol($id) === $managed_baseline->lowered->for_symbol($id))
            && ($managed_updated->llvm->function_for($id) === $function), 'Managed-body edit preserves unchanged function stages');
        ++$unchanged_functions;
    }
}
Check::check(($unchanged_functions >= 2) && ($managed_baseline->lifetimes->for_symbol($unrelated_id)->cleanups !== [])
    && (serialize($managed_baseline) === $managed_before), 'Caller, unrelated managed cleanup and prior snapshot remain unchanged');

$published = $session->published;
Check::edit($main, '$a first = make_first(1); $b first = $a; return 0;');
Check::rejects(static fn() => $session->compile($manifest, $output), 'copy construction is unavailable');
Check::check($session->published === $published, 'Unsupported managed copying preserves publication');
// Artifact checksums are refreshed so this tests semantic validation, not damaged-file detection.
$manifest_path = $config['output_directory'] . '/package/manifest.json';
$metadata_path = $config['output_directory'] . '/package/' . $package['metadata'];
$metadata = Files::json($metadata_path);
foreach ($metadata['operations'] as &$operation) {
    if ($operation['kind'] === 'destroy') {
        $operation['parameters'][0]['ownership'] = 'borrowed';
        break;
    }
}
unset($operation);
Files::write_json($metadata_path, $metadata);
$package['artifacts'][$package['metadata']] = hash_file('sha256', $metadata_path);
Files::write_json($manifest_path, $package);
$pointer_path = $config['output_directory'] . '/current.json';
$pointer = Files::json($pointer_path);
$pointer['manifest_sha256'] = hash_file('sha256', $manifest_path);
Files::write_json($pointer_path, $pointer);
Check::rejects(static fn() => $session->compile($manifest, $output), 'Unsupported runtime destruction contract');
Check::check($session->published === $published, 'An invalid destroy contract cannot replace accepted compiler state');
echo "runtime cleanup ok: metadata destruction, locals/temporaries, boundary order validation, branches/loops/returns, native oracles, fixed workers, reuse and managed-body replacement increments\n";
