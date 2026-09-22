<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/../support/step_support.php';

use emit_llvm\LLVM_Emitter;
use compile\Compiler_Session;

class Native_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    /** Require the expected failure without swallowing an unrelated diagnostic. */
    public static function rejects(callable $operation, string $text): void
    {
        try {
            $operation();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $text), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $text);
    }

    public static function edit(string $path, string $source): void
    {
        clearstatcache(true, $path);
        $time = filemtime($path) + 2;
        file_put_contents($path, $source);
        touch($path, $time);
    }

    /** Execute the native proof with a deadline and release its process. */
    public static function run(string $path): int
    {
        $out = tmpfile();
        $err = tmpfile();
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => $out, 2 => $err], $pipes);
        self::check(is_resource($process), 'Start real executable');
        try
        {
            $deadline = hrtime(true) + 2_000_000_000;
            do
            {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    return $status['exitcode'];
                }
                self::check(hrtime(true) < $deadline, 'Executable timed out');
                usleep(10000);
            }
            while (true);
        }
        finally
        {
            if (proc_get_status($process)['running']) {
                proc_terminate($process, 9);
            }
            proc_close($process);
            fclose($out);
            fclose($err);
        }
    }

    public static function baseline(Compiler_Session $session): array
    {
        return [$session->generation, $session->published, $session->observed];
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$value_path = $root . '/src/nested/value.phs';
$main_path = $root . '/src/main.phs';
$output = getcwd() . '/program with spaces';
$configuration = getcwd() . '/backend.json';
$catalog_path = getcwd() . '/types.json';
$catalog = json_decode(file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
file_put_contents($configuration, json_encode(['clang' => 'clang', 'target' => null], JSON_THROW_ON_ERROR));
$session = new Compiler_Session(type_catalog_path: $catalog_path, backend_toolchain_path: $configuration);
$first = $session->compile($manifest, $output);
Native_Test::check(($first->completed) && ($first->stopped_before === null) && ($session->generation === 1)
    && (Native_Test::run($output) === 42), 'Real three-file native chain and publication');
Native_Test::check(str_contains(implode('', $first->llvm->ir_by_file()), 'trunc i64 %result to i' . $first->llvm->entry->native_bits), 'Explicit probed native return adaptation');
foreach ($session->published->inputs->sources->files as $file) {
    Native_Test::check(!$file->needs_recompile, 'Publication acknowledges file work');
}
$warm = $session->compile($manifest, $output);
Native_Test::check(($warm->llvm === $first->llvm) && ($warm->native === $first->native)
    && (\Step_Test::select(LLVM_Emitter::class, $warm->lowered, $first->llvm, false) === []), 'Warm emission and binary reuse');
$tasks = \Step_Test::select(LLVM_Emitter::class, $first->lowered, null, true);
$before = $first->to_json();
$results = array_map(static fn($task) => (new \emit_llvm\Emission_Worker($task))->emit(), array_reverse($tasks));
$functions = (new \emit_llvm\Emission_Join($first->lowered, $first->backend, $first->llvm->entry, null, $tasks))->join($results);
$module_tasks = \Step_Test::select(\emit_llvm\Module_Assembler::class, $functions, null, true);
$module_results = array_map([\emit_llvm\Module_Worker::class, 'assemble'], array_reverse($module_tasks));
$joined = (new \emit_llvm\Module_Join($functions, null, $module_tasks))->join($module_results);
foreach ($first->lowered->bodies() as $plan) {
    Native_Test::check($plan->backend_context() === $first->backend, 'Lowered plans expose their exact shared context');
}
Native_Test::rejects(static fn() => (new \emit_llvm\Emission_Join($first->lowered, clone $first->backend, $first->llvm->entry, null, $tasks))->join($results), 'stale emission');
Native_Test::check(($joined->ir_by_file() === $first->llvm->ir_by_file()) && ($before === $first->to_json()), 'Independent workers join deterministically without input mutation');
foreach ($joined->functions() as $function) {
    Native_Test::check($joined->function_for($function->body->binding->callable_id) === $function,
        'Module lookup shares the existing emitted function in deterministic order');
}
Native_Test::check($joined->function_for(0) === null, 'Missing emitted function is explicit');
Native_Test::rejects(static fn() => new \emit_llvm\Emitted_Program($joined->backend, $joined->entry,
        [...$joined->modules, $joined->modules[0]]), 'Duplicate');
Native_Test::rejects(static fn() => (new \emit_llvm\Emission_Join($first->lowered, $first->backend, $first->llvm->entry, null, $tasks))->join([]), 'Incomplete');
Native_Test::rejects(static fn() => (new \emit_llvm\Emission_Join($first->lowered, $first->backend, $first->llvm->entry, null, $tasks))->join([...$results, $results[0]]), 'duplicate');
Native_Test::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest, $output);
Native_Test::check((!$edited->inputs->context->full_rebuild) && (Native_Test::run($output) === 43)
    && (count(\Step_Test::select(LLVM_Emitter::class, $edited->lowered, $first->llvm, false)) === 1), 'One-function increment reaches a changed executable');
$shared = array_filter($edited->llvm->functions(), static fn($f) => in_array($f, $first->llvm->functions(), true));
Native_Test::check(count($shared) === 2, 'Unchanged callers share emitted function results');
foreach ($shared as $function) {
    Native_Test::check($edited->llvm->function_for($function->body->binding->callable_id)
        === $first->llvm->function_for($function->body->binding->callable_id), 'Lookup preserves shared function identity across updates');
}
Native_Test::rejects(static fn() => (new \emit_llvm\Emission_Join($edited->lowered, $edited->backend, $edited->llvm->entry, $first->llvm, $tasks))->join($results), 'stale');
$baseline = Native_Test::baseline($session);
$key = hash_file('sha256', $output);
Native_Test::edit($value_path, 'function value(): int { return missing(); }');
Native_Test::rejects(static fn() => $session->compile($manifest, $output), 'Unknown');
Native_Test::check(($baseline === Native_Test::baseline($session)) && (hash_file('sha256', $output) === $key)
    && (Native_Test::run($output) === 43), 'Source failure preserves accepted state and runnable output');
Native_Test::edit($value_path, 'function value(): int { return 44; }');
$repaired = $session->compile($manifest, $output);
Native_Test::check(Native_Test::run($output) === 44, 'Repair resumes from published state');

// Real Clang for probing/object compilation, deliberately failing linker driver.
$clang = trim((string)shell_exec('command -v clang'));
$wrapper = getcwd() . '/failing-linker';
file_put_contents($wrapper, "#!/bin/sh\nfor arg do\n if [ \"\$arg\" = '-c' ]; then exec " . escapeshellarg($clang) . " \"\$@\"; fi\ndone\nfor arg do\n case \"\$arg\" in *.o) echo 'deliberate link failure' >&2; exit 1;; esac\ndone\nexec " . escapeshellarg($clang) . " \"\$@\"\n");
chmod($wrapper, 0700);
file_put_contents($configuration, json_encode(['clang' => $wrapper, 'target' => null], JSON_THROW_ON_ERROR));
$baseline = Native_Test::baseline($session);
$key = hash_file('sha256', $output);
Native_Test::rejects(static fn() => $session->compile($manifest, $output), 'deliberate link failure');
Native_Test::check(Native_Test::baseline($session) === $baseline, 'Link failure preserves state');
Native_Test::check(hash_file('sha256', $output) === $key, 'Link failure preserves output');
Native_Test::check(glob(getcwd() . '/.scpp-native-*') === [], 'Link failure cleans staging');
file_put_contents($configuration, json_encode(['clang' => 'clang', 'target' => null], JSON_THROW_ON_ERROR));
$session->compile($manifest, $output);
Native_Test::check(Native_Test::run($output) === 44, 'Linker repair succeeds');

// Invalid real IR must fail object generation without publishing anything.
$toolchain = new \prepare_backend\LLVM_Toolchain($configuration);
$config = $toolchain->configuration();
$bad_modules = $repaired->llvm->modules;
$valid = $bad_modules[0];
$bad_modules[0] = new \emit_llvm\Emitted_Module($valid->source_file_id, $valid->backend, $valid->functions, $valid->entry, 'invalid llvm');
$bad_ir = new \emit_llvm\Emitted_Program($repaired->llvm->backend, $repaired->llvm->entry, $bad_modules);
$key = hash_file('sha256', $output);
Native_Test::rejects(static fn() => \Step_Test::run(new \build_native\Native_Builder($bad_ir, $output, $toolchain, null, true)), 'backend operation failed');
Native_Test::check((hash_file('sha256', $output) === $key) && (glob(getcwd() . '/.scpp-native-*') === []), 'IR rejection preserves output and cleans workspace');

// Void calls, fallthrough, bare return, and unreachable recursion compose in LLVM.
Native_Test::edit($main_path, 'warmup(); empty_call(); return answer(); loop();
function warmup(): void { value(); return; loop(); }
function empty_call(): void {}
function loop(): void { loop(); }
function unused(): int { return 5; }');
$added = $session->compile($manifest, $output);
$unused = $added->symbols->current->find_symbol('unused', '', \collect_symbols\symbol_kind::function_symbol);
Native_Test::check(($added->inputs->context->full_rebuild) && (Native_Test::run($output) === 44), 'Added declarations select the common full path; unreachable calls do not execute');
Native_Test::edit($main_path, str_replace('function unused(): int { return 5; }', '', file_get_contents($main_path)));
$removed = $session->compile($manifest, $output);
Native_Test::check(($removed->llvm->function_for($unused) === null) && ($added->llvm->function_for($unused) !== null),
    'Removal changes only the new module index, preserving older snapshots');
Native_Test::check(($removed->inputs->context->full_rebuild) && (count($removed->llvm->functions()) === (count($added->llvm->functions()) - 1))
    && (Native_Test::run($output) === 44), 'Removal retires emitted functions through full selection');

// Unknown names/widths follow representation and signedness contracts.
foreach ([[17, true, 'sext'], [17, false, 'zext'], [32, false, 'identity'], [64, true, 'trunc']] as [$bits, $signed, $operation])
{
    $custom = $catalog;
    foreach ($custom['types'] as &$type) {
        if ($type['name'] === 'int') {
            $type['name'] = 'Counter';
            $type['bit_width'] = $bits;
            $type['signed'] = $signed;
        }
    }
    unset($type);
    $custom['literal_types']['integer']['name'] = 'Counter';
    $custom['entry_return_type']['name'] = 'Counter';
    file_put_contents($catalog_path, json_encode($custom, JSON_THROW_ON_ERROR));
    Native_Test::edit($main_path, 'return answer();');
    Native_Test::edit($root . '/src/answer.phs', 'function answer(): Counter { return value(); }');
    Native_Test::edit($value_path, 'function value(): Counter { return 257; }');
    $counter = $session->compile($manifest, $output);
    Native_Test::check(($counter->llvm->entry->conversion->value === $operation) && (Native_Test::run($output) === 1),
        'Generic native integer adaptation and host exit-status observation');
}

// A changed/missing external artifact cannot reuse its previous descriptor.
file_put_contents($output, 'not an executable');
$restored = $session->compile($manifest, $output);
Native_Test::check(($restored->native !== $counter->native) && (Native_Test::run($output) === 1), 'Tampered artifact rebuilt');
$fresh = (new Compiler_Session(type_catalog_path: $catalog_path, backend_toolchain_path: $configuration))->compile($manifest, getcwd() . '/fresh');
Native_Test::check(Native_Test::run($fresh->native->path) === Native_Test::run($output), 'Fresh and incremental native behavior agree');
$export = json_decode($restored->to_json(), true, 512, JSON_THROW_ON_ERROR);
Native_Test::check(($export['completed']) && ($export['stopped_before'] === null) && ($export['native']['sha256'] === hash_file('sha256', $output))
    && str_contains(implode('', array_column($export['llvm']['modules'], 'ir')), '@"main"'), 'Debug export includes real IR and published artifact');
Native_Test::rejects(static fn() => $session->compile($manifest, $main_path), 'source folders');
Native_Test::rejects(static fn() => $session->compile($manifest, $manifest), 'would replace compiler input');
Native_Test::check(glob(getcwd() . '/.scpp-native-*') === [], 'Successful attempts clean staging');
echo "native executable ok: real execution, emission workers, body increment, full fallback, publication, rollback/repair, ABI adaptations, artifact reuse and exports\n";
