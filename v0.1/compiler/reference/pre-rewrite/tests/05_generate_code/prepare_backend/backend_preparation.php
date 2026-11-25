<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/bootstrap.php';

use prepare_backend\LLVM_Backend;
use prepare_backend\LLVM_Toolchain;
use prepare_backend\LLVM_Types;
use collect_symbols\symbol_kind;

class Backend_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    /** Require rejection with the expected diagnostic fragment. */
    public static function rejects(callable $action, string $message): void
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $message);
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    public static function baseline(\compile\Compiler_Session $session): array
    {
        return [$session->observed];
    }

    /** Compare prepared callable facts in stable source-name order. */
    public static function binding_facts(\compile\Compile_Result $result): array
    {
        $facts = [];
        foreach ($result->bodies->bodies() as $body) {
            $binding = $result->backend->callable_for($body, $body->owner->symbol_id);
            $facts[$body->owner->name] = [$binding->linkage, $binding->calling_convention, $binding->return_definition];
        }
        ksort($facts);
        return $facts;
    }
}

$path = getcwd() . '/backend.json';
$settings = ['clang' => 'clang', 'target' => null];
file_put_contents($path, json_encode($settings, JSON_THROW_ON_ERROR));
$toolchain = new LLVM_Toolchain($path);
$config = $toolchain->configuration();
Backend_Test::check(($config->target_triple !== '') && ($config->data_layout !== '') && ($config->cpu !== '') && ($toolchain->invocation_count() === 2),
    'Clang supplies target/layout/CPU/features through real version and IR probes');
Backend_Test::check(($toolchain->configuration() === $config) && ($toolchain->invocation_count() === 2), 'Unchanged toolchain requests reuse the verified target');
$toolchain->verify_return('i23', $config);
$toolchain->verify_return('i23', $config);
Backend_Test::check($toolchain->invocation_count() === 3, 'One real object-code probe per scalar representation and target');
Backend_Test::rejects(static fn() => $toolchain->verify_return('not_a_type', $config), 'backend operation failed');
Backend_Test::rejects(static fn() => $toolchain->verify_return('not_a_type', $config), 'backend operation failed');
Backend_Test::check($toolchain->invocation_count() === 5, 'Failed capabilities are never cached as verified');
Backend_Test::rejects(static fn() => LLVM_Types::scalar(new \type_model\representation_record(\type_model\representation_kind::pointer,
            new \type_model\pointer_representation(1))), 'Unsupported LLVM scalar representation');

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$main = $root . '/src/main.phs';
$value_path = $root . '/src/nested/value.phs';
$answer_path = $root . '/src/answer.phs';
$main_source = 'answer(); quiet(); return 42; function spare(): float { return spare(); } function quiet(): void {}';
Backend_Test::edit($main, $main_source);
$session = new \compile\Compiler_Session(backend_toolchain_path: $path);
$first = $session->compile($manifest);
$value = $first->symbols->current->find_symbol('value', '', symbol_kind::function_symbol);
$answer = $first->symbols->current->find_symbol('answer', '', symbol_kind::function_symbol);
$spare = $first->symbols->current->find_symbol('spare', '', symbol_kind::function_symbol);
$entry = $first->types->entry->symbol->symbol_id;
$names = [];
foreach ($first->bodies->bodies() as $body)
{
    $id = $body->owner->symbol_id;
    $binding = $first->backend->callable_for($body, $id);
    $first->lowering_input_for($id)->require_callables();
    Backend_Test::check(($binding->linkage === 'external') && ($binding->calling_convention === 'ccc')
        && ($binding->signature === $body->signature_for($id)), 'Real signatures produce shared LLVM contracts, including void and floating returns');
    Backend_Test::check((!isset($names[$binding->link_name])) && (preg_match('/^[a-zA-Z_][a-zA-Z_0-9]*$/', $binding->link_name) === 1),
        'Backend link names are collision-free legal identifiers');
    $names[$binding->link_name] = true;
}
$before = serialize($first);
$warm = $session->compile($manifest);
Backend_Test::check(($warm->backend === $first->backend) && (\Step_Test::select(LLVM_Backend::class, $warm->types, $config, $first->backend, false) === []),
    'Unchanged contracts select zero binding work and retain the whole context');
$tasks = \Step_Test::select(LLVM_Backend::class, $first->types, $config, null, true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = \prepare_backend\Callable_Preparer::prepare_callable($task, $first->types, $config);
}
$joined = (new \prepare_backend\Backend_Join($first->types, $config, null, $tasks))->join($results);
Backend_Test::check(($joined->to_json() === $first->backend->to_json()) && (serialize($first) === $before),
    'Independent reversed workers join deterministically without changing inputs');
Backend_Test::rejects(static fn() => (new \prepare_backend\Backend_Join($first->types, $config, null, $tasks))->join([]), 'Incomplete');
Backend_Test::rejects(static fn() => (new \prepare_backend\Backend_Join($first->types, $config, null, $tasks))->join([...$results, $results[0]]), 'duplicate');
Backend_Test::rejects(static fn() => (new \prepare_backend\Backend_Join($first->types, $config, null, [$tasks[0], $tasks[0]]))->join([]), 'Duplicate');
Backend_Test::check((new \prepare_backend\Backend_Join(new \resolve_types\Type_Resolution($first->types->types, $first->types->catalog,
            $first->types->entry, [$first->types->for_symbol($entry)], [], $first->resolutions), $config, $joined, []))->join([])->binding_for($value) === null,
    'Removed contributions retire independently of selection');
Backend_Test::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest);
Backend_Test::check(($edited->backend === $first->backend) && ($edited->lifetimes->for_symbol($value) !== $first->lifetimes->for_symbol($value))
    && $first->lowering_input_for($entry)->is_current($edited->lifetimes->for_symbol($entry), $edited->backend),
    'Body-only updates preserve prepared ABI contracts and unaffected lowering inputs');
Backend_Test::edit($value_path, 'function value(): uint32 { return value(); }');
Backend_Test::edit($answer_path, 'function answer(): uint32 { return value(); }');
$contracts = $session->compile($manifest);
Backend_Test::check(($contracts->backend !== $first->backend) && ($contracts->backend->binding_for($value) !== $first->backend->binding_for($value))
    && ($contracts->inputs->context->full_rebuild)
    && ($contracts->backend->binding_for($entry)->signature === $contracts->types->signature_for($entry))
    && ($contracts->backend->binding_for($value)->link_name === $first->backend->binding_for($value)->link_name),
    'Signature edits select full work and prepare current contracts while preserving logical link names');
Backend_Test::rejects(static fn() => (new \prepare_backend\Backend_Join($contracts->types, $config, $first->backend, [$contracts->types->for_symbol($value)]))->join([$first->backend->binding_for($value)]), 'stale');
Backend_Test::rejects(static fn() => \prepare_backend\Callable_Preparer::prepare_callable($first->types->for_symbol($value),
        $contracts->types, $config), 'Stale backend callable task');

$baseline = Backend_Test::baseline($session);
$baseline_dump = serialize($baseline);
file_put_contents($path, json_encode(['clang' => 'clang', 'target' => 'scpp-not-a-real-target'], JSON_THROW_ON_ERROR));
Backend_Test::rejects(static fn() => $session->compile($manifest), 'backend operation failed');
Backend_Test::check((Backend_Test::baseline($session) === $baseline) && (serialize($baseline) === $baseline_dump),
    'Target failures preserve every accepted compiler stage');
file_put_contents($path, '{');
Backend_Test::rejects(static fn() => $session->compile($manifest), 'Invalid backend toolchain JSON');
file_put_contents($path, json_encode(['clang' => './missing-clang', 'target' => null], JSON_THROW_ON_ERROR));
Backend_Test::rejects(static fn() => $session->compile($manifest), 'Cannot locate');
file_put_contents($path, json_encode($settings, JSON_THROW_ON_ERROR));
$repair = $session->compile($manifest);
Backend_Test::check($repair->backend === $contracts->backend, 'Repair reuses the last accepted compatible bindings');
$explicit_target = ['clang' => 'clang', 'target' => $config->target_triple];
file_put_contents($path, json_encode($explicit_target, JSON_THROW_ON_ERROR));
$target_edit = $session->compile($manifest);
Backend_Test::check(($target_edit->backend !== $repair->backend)
    && ($target_edit->lifetimes->for_symbol($entry) === $repair->lifetimes->for_symbol($entry))
    && (!$repair->lowering_input_for($entry)->is_current($target_edit->lifetimes->for_symbol($entry), $target_edit->backend)),
    'Explicit verified target requests invalidate backend reuse without invalidating language-only stages');
Backend_Test::edit($main, 'answer(); quiet(); return 42; function quiet(): void {}');
$removed = $session->compile($manifest);
Backend_Test::check($removed->backend->binding_for($spare) === null, 'Source removal retires backend symbols');
file_put_contents($manifest, file_get_contents($manifest) . "\n");
$full = $session->compile($manifest);
Backend_Test::check(($full->inputs->context->full_rebuild) && ($full->backend !== $removed->backend), 'Full rebuild uses the same binding workers with fresh semantic lineage');
$fresh = (new \compile\Compiler_Session(backend_toolchain_path: $path))->compile($manifest);
Backend_Test::check(Backend_Test::binding_facts($fresh) == Backend_Test::binding_facts($full), 'Fresh and incremental binding facts agree independent of session-local IDs');
$export = json_decode($full->to_json(), true, 512, JSON_THROW_ON_ERROR);
Backend_Test::check((count($export['backend']['callables']) === count($export['bodies']))
    && ($export['backend']['native_entry_adapter'] === 'separate_module_plan') && (!$full->completed) && ($full->stopped_before === 'build_native')
    && (serialize($first) === $before), 'Exports describe real preparation without claiming program emission or native-entry readiness');

// A valid declaration can prepare a backend contract before its body is checked.
// Run the real frontend/type stages with a deliberately invalid body.
Backend_Test::edit($value_path, 'function value(): uint32 { return; }');
$update = new \compile\Update_Context();
$sources = \Step_Test::run(new \read_sources\Source_Discovery($full->inputs->manifest, $full->inputs->sources));
$lexical = \compile\Phases::run_tokenization($sources, $full->inputs->tokens, $update);
$frontends = \compile\Phases::run_parsing($lexical->sources, $lexical->tokens, $full->inputs->frontends, $update);
$symbols = \Step_Test::run(new \collect_symbols\Declaration_Collector($full->symbols->current, $lexical->sources, $frontends, false))->current;
$entry_contract = \Step_Test::run(new \resolve_types\Entry_Resolver($lexical->sources, $symbols, $full->types->catalog));
$names = \compile\Phases::run_symbols($symbols, $full->resolutions, $update, $full->types->catalog);
$types = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $full->types->catalog, clone $full->types->types, $full->types, $update->full_rebuild, $entry_contract, $names));
$type_before = serialize($types);
$prepared = \Step_Test::run(new LLVM_Backend(new LLVM_Toolchain($path), $types, $full->backend, false));
Backend_Test::check(($prepared === $full->backend) && (serialize($types) === $type_before),
    'Resolved declarations alone prepare and reuse contracts, without checked bodies or writes to type inputs');
Backend_Test::rejects(static fn() => \compile\Phases::run_bodies($symbols, $names, $types, $full->bodies, $update),
    'A value is required by the declared return type');

// Exercise source-fingerprint invalidation in a private copy, never editing the compiler checkout.
$policy_root = getcwd() . '/policy-copy';
mkdir($policy_root);
$prototype_root = dirname(__DIR__, 3);
$backend_path = '/src/05_generate_code/prepare_backend';
$policy_files = [
    $backend_path . '/main_prepare_backend.php', $backend_path . '/callable.php',
    $backend_path . '/backend_join.php', $backend_path . '/lifecycle_join.php',
    $backend_path . '/layout.php', $backend_path . '/layout_join.php',
    $backend_path . '/data/bindings.php', $backend_path . '/data/layout.php', $backend_path . '/data/context.php',
    $backend_path . '/utilities/callable_contract.php', $backend_path . '/utilities/llvm_types.php',
    '/src/05_generate_code/lower/main_native_entry.php', '/tool_process/process.php',
];
$copy_files = ['/src/05_generate_code/lower/main_native_entry.php', '/tool_process/process.php'];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($prototype_root . $backend_path,
    FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isFile() && ($file->getExtension() === 'php')) {
        $copy_files[] = substr($file->getPathname(), strlen($prototype_root));
    }
}
foreach ($copy_files as $file)
{
    if (!is_dir(dirname($policy_root . $file))) {
        mkdir(dirname($policy_root . $file), 0777, true);
    }
    copy($prototype_root . $file, $policy_root . $file);
}
$tool_file = $backend_path . '/tools/toolchain.php';

$policy_code = file_get_contents($prototype_root . $tool_file);
file_put_contents($policy_root . $tool_file,
    str_replace('class LLVM_Toolchain', 'class Policy_Test_Toolchain', $policy_code));
require $policy_root . $tool_file;
file_put_contents($policy_root . '/backend.json', json_encode(['clang' => 'clang', 'target' => null], JSON_THROW_ON_ERROR));
$policy_toolchain = new \prepare_backend\Policy_Test_Toolchain($policy_root . '/backend.json');
$policy_config = $policy_toolchain->configuration();
foreach ($policy_files as $file)
{
    $count = $policy_toolchain->invocation_count();
    Backend_Test::check(($policy_toolchain->configuration() === $policy_config)
        && ($policy_toolchain->invocation_count() === $count), 'Unchanged extracted policy sources reuse target probes');
    file_put_contents($policy_root . $file, "\n// Test policy revision.\n", FILE_APPEND);
    $changed = $policy_toolchain->configuration();
    Backend_Test::check(($changed !== $policy_config) && ($changed->backend_key !== $policy_config->backend_key)
        && (($changed->abi_key !== $policy_config->abi_key) === ($file !== '/tool_process/process.php'))
        && ($policy_toolchain->invocation_count() === ($count + 2)),
        'Moved source changes invalidate probes; process execution changes do not redefine ABI policy');
    $policy_config = $changed;
}
// Membership changes also invalidate policy; no new central filename entry is needed.
$added = $policy_root . $backend_path . '/utilities/extra/probe.php';
mkdir(dirname($added));
file_put_contents($added, "<?php\n// Future policy helper.\n");
$extended = $policy_toolchain->configuration();
Backend_Test::check(($extended !== $policy_config) && ($extended->abi_key !== $policy_config->abi_key),
    'A new nested policy source invalidates configuration automatically');
unlink($added);
$removed = $policy_toolchain->configuration();
Backend_Test::check(($removed !== $extended) && ($removed->abi_key === $policy_config->abi_key),
    'Removing the added policy source restores the exact prior policy fingerprint');
$count = $policy_toolchain->invocation_count();
Backend_Test::check(($policy_toolchain->configuration() === $removed) && ($policy_toolchain->invocation_count() === $count),
    'Unchanged policy membership and bytes preserve probe reuse');
echo "backend preparation ok: verified Clang target and scalar calls, signature-only inputs, real bindings, fixed workers, reuse, automatic contract refresh, rollback/repair, target edits, removals and exports\n";
