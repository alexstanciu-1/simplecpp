<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/bootstrap.php';

use emit_llvm\LLVM_Emitter;
use emit_llvm\Module_Assembler;
use emit_llvm\module_task;

final class Assembly_Test
{
    public static function check(bool $ok, string $reason): void
    {
        if (!$ok) {
            throw new RuntimeException($reason);
        }
    }

    public static function rejects(callable $call, string $text): void
    {
        try {
            $call();
        }
        catch (LogicException $error) {
            self::check(str_contains($error->getMessage(), $text), $error->getMessage());
            return;
        }
        throw new RuntimeException('Expected rejection: ' . $text);
    }

    public static function functions(\compile\Compile_Result $result): \emit_llvm\Emitted_Function_Set
    {
        // Read already accepted function rows; no emission or external invocation.
        return (new \emit_llvm\Emission_Join($result->lowered, $result->backend, $result->llvm->entry, $result->llvm, []))->join([]);
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
    }

    public static function run(string $path, int $expected): void
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($process) && (proc_close($process) === $expected), 'Executable result');
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$output = getcwd() . '/program';
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest, $output);
Assembly_Test::run($output, 42);
$before = $first->to_json();
$functions = Assembly_Test::functions($first);
$tool = (new ReflectionProperty($session, 'toolchain'))->getValue($session);
$invocations = $tool->invocation_count();
Assembly_Test::check(\Step_Test::select(Module_Assembler::class, $functions, $first->llvm, false) === [], 'Unchanged selects no assembly');
Assembly_Test::check((new \emit_llvm\Module_Join($functions, $first->llvm, []))->join([]) === $first->llvm, 'Reuse exact program');

// Full selects every file even when all current function rows and prior modules match.
$tasks = \Step_Test::select(Module_Assembler::class, $functions, $first->llvm, true);
Assembly_Test::check(count($tasks) === 3, 'Full selects every file assembly task');
$results = array_map([\emit_llvm\Module_Worker::class, 'assemble'], array_reverse($tasks));
$rebuilt = (new \emit_llvm\Module_Join($functions, $first->llvm, $tasks))->join($results);
Assembly_Test::check($rebuilt->to_array() === $first->llvm->to_array(), 'Reverse completion restores exact IR, order and exports');
foreach ($rebuilt->modules as $module) {
    $old = $first->llvm->module_for($module->source_file_id);
    Assembly_Test::check(($module !== $old) && ($module->functions === $old->functions), 'New modules share existing function rows');
}
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($functions, $first->llvm, $tasks))->join([]), 'Incomplete');
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($functions, $first->llvm, [...$tasks, $tasks[0]]))->join($results), 'Duplicate');
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($functions, $first->llvm, $tasks))->join([...$results, $results[0]]), 'duplicate');
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($functions, $first->llvm, []))->join([$results[0]]), 'Unexpected');
$task = $tasks[0];
$stale = new module_task($task->source_file_id, clone $task->backend, $task->functions, $task->entry);
Assembly_Test::rejects(static fn() => \emit_llvm\Module_Worker::assemble($stale), 'stale');
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($functions, $first->llvm, [$stale]))->join([]), 'stale');
Assembly_Test::check(($first->to_json() === $before) && ($tool->invocation_count() === $invocations),
    'Assembly and failed joins neither mutate inputs nor invoke native tools');

// Entry adaptation is a file dependency independent of function IR replacement.
$entry = \Step_Test::run(new \lower\Native_Entry($first->llvm->entry->entry, $first->llvm->entry->native_bits + 1, $first->llvm->entry));
$entry_functions = (new \emit_llvm\Emission_Join($first->lowered, $first->backend, $entry, $first->llvm, []))->join([]);
$entry_tasks = \Step_Test::select(Module_Assembler::class, $entry_functions, $first->llvm, false);
Assembly_Test::check((count($entry_tasks) === 1) && ($entry_tasks[0]->source_file_id === $functions->entry_file_id),
    'Entry-only change selects its file');
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($entry_functions, $first->llvm, $entry_tasks))->join([$first->llvm->module_for($functions->entry_file_id)]), 'stale');
$entry_module = \emit_llvm\Module_Worker::assemble($entry_tasks[0]);
$entry_program = (new \emit_llvm\Module_Join($entry_functions, $first->llvm, $entry_tasks))->join([$entry_module]);
Assembly_Test::check(($entry_program->entry === $entry) && ($entry_module->ir !== $first->llvm->module_for($functions->entry_file_id)->ir),
    'Entry adapter assembled from the fixed current plan');

Assembly_Test::edit($root . '/src/nested/value.phs', 'function value(): int { return 43; }');
$edited = $session->compile($manifest, $output);
Assembly_Test::run($output, 43);
$current = Assembly_Test::functions($edited);
$selected = \Step_Test::select(Module_Assembler::class, $current, $first->llvm, false);
Assembly_Test::check((!$edited->inputs->context->full_rebuild) && (count($selected) === 1), 'Body edit selects one file');
Assembly_Test::rejects(static fn() => (new \emit_llvm\Module_Join($current, $first->llvm, $selected))->join([$first->llvm->module_for($selected[0]->source_file_id)]), 'stale');
$assembled = (new \emit_llvm\Module_Join($current, $first->llvm, $selected))->join(array_map([\emit_llvm\Module_Worker::class, 'assemble'], $selected));
Assembly_Test::check($assembled->to_array() === $edited->llvm->to_array(), 'Independent assembly matches real pipeline');
foreach ($first->llvm->modules as $old) {
    if ($old->source_file_id === $selected[0]->source_file_id) {
        continue;
    }
    Assembly_Test::check($assembled->module_for($old->source_file_id) === $old, 'Unselected modules stay shared');
}
Assembly_Test::check($first->to_json() === $before, 'Retained snapshot remains intact after edit and repair');
echo "module assembly ok: independent files, reverse completion, full/selected reuse, entry invalidation, stale/incomplete rejection, purity and real execution\n";
