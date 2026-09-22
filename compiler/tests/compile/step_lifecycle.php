<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/bootstrap.php';

// Exercise every scheduled owner through real source-to-native inputs. Existing
// feature proofs separately cover changed selection, worker order and stale joins.
$manifest_path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$baseline = $session->compile($manifest_path);
$before = $baseline->to_json();
$toolchain = new \prepare_backend\LLVM_Toolchain();
$manifest = Step_Test::run(new \read_manifest\Manifest_Reader($manifest_path));
$catalog = Step_Test::run(new \load_runtime\Language_Types(null, $baseline->types->catalog));
$sources = Step_Test::run(new \read_sources\Source_Discovery($manifest, $baseline->inputs->sources));
$sources = Step_Test::run(new \read_sources\Source_Reader($sources, false));
$tokens = Step_Test::run(new \tokenize\Tokenizer($sources, $baseline->inputs->tokens, false));
$frontends = Step_Test::run(new \parse\Parser($sources, $tokens, $baseline->inputs->frontends, false));
$symbols = Step_Test::run(new \collect_symbols\Declaration_Collector($baseline->symbols->current, $sources, $frontends, false));
$entry = Step_Test::run(new \resolve_types\Entry_Resolver($sources, $symbols->current, $catalog));
$names = Step_Test::run(new \resolve_symbols\Symbol_Resolver($symbols->current, $baseline->resolutions, false, $catalog));
$symbols = Step_Test::run(new \collect_symbols\Symbol_Comparer($symbols, false));
$templates = Step_Test::run(new \check_templates\Template_Checker($symbols->current, $names, $catalog,
    $baseline->types->instances->template_checks(), false));
Step_Test::check($templates->definitions === [], 'Template phase preserves empty current membership');
$cache = \resolve_types\Type_Cache::prepare($baseline->types->types, $baseline->types->types->context, false);
$instances = Step_Test::run(new \resolve_types\Concrete_Preparation($symbols->current, $names, $catalog,
    clone $cache, $baseline->types->instances, false));
Step_Test::check($instances->to_array() === $baseline->types->instances->to_array(), 'Instance lifecycle retains equivalent demands');
$types = Step_Test::run(new \resolve_types\Type_Resolver($symbols->current, $catalog, $cache, $baseline->types, false, $entry, $names));
$bodies = Step_Test::run(new \check_bodies\Body_Checker($symbols->current, $names, $types, $baseline->bodies, false));
$lifetimes = Step_Test::run(new \analyze_lifetimes\Lifetime_Analyzer($bodies, $baseline->lifetimes, false));
$backend = Step_Test::run(new \prepare_backend\LLVM_Backend($toolchain, $types, $baseline->backend, false));
$lowered = Step_Test::run(new \lower\Lowerer($lifetimes, $backend, $baseline->lowered, false));
$native_entry = Step_Test::run(new \lower\Native_Entry($backend->binding_for($entry->symbol->symbol_id),
    $toolchain->native_return_bits($backend->configuration), $baseline->llvm->entry));
$functions = Step_Test::run(new \emit_llvm\LLVM_Emitter($lowered, $backend, $native_entry, $baseline->llvm, false));
$program = Step_Test::run(new \emit_llvm\Module_Assembler($functions, $baseline->llvm, false));
Step_Test::check($before === $baseline->to_json(), 'All phases preserve retained input exports');
Step_Test::check($program->to_array() === $baseline->llvm->to_array(), 'Warm pipeline produces equivalent LLVM');

// Finishing a real native step must leave publication to the session. Abandoning
// the finished step and candidate must clean staging and newly compiled objects.
$output = getcwd() . '/candidate-only';
file_put_contents($output, 'existing destination');
$builder = new \build_native\Native_Builder($program, $output, $toolchain, null, true);
$candidate = Step_Test::run($builder);
$objects = array_map(static fn($object) => $object->path, $candidate->artifact->objects);
Step_Test::check(file_get_contents($output) === 'existing destination', 'finalize does not publish');
Step_Test::check(count(glob(getcwd() . '/.scpp-native-*')) === 1, 'Completed candidate owns private staging');
unset($candidate, $builder);
foreach ($objects as $object) {
    Step_Test::check(!file_exists($object), 'Abandoned candidate cleans newly owned objects');
}
Step_Test::check(glob(getcwd() . '/.scpp-native-*') === [], 'Abandoned candidate cleans staging');
unlink($output);

// A constructor accepts arguments without touching disk. Each failure boundary
// becomes terminal and never unlocks partial results or resets the same instance.
$missing = getcwd() . '/missing-manifest.json';
$reader = new \read_manifest\Manifest_Reader($missing);
Step_Test::state($reader, \compile\step_status::created, ['result']);
try {
    $reader->init();
}
catch (Throwable) {
}
Step_Test::state($reader, \compile\step_status::failed, ['init', 'run', 'finalize', 'result']);

file_put_contents($missing, file_get_contents($manifest_path));
$reader = new \read_manifest\Manifest_Reader($missing);
$reader->init();
unlink($missing);
try {
    $reader->run();
}
catch (Throwable) {
}
Step_Test::state($reader, \compile\step_status::failed, ['init', 'run', 'finalize', 'result']);

$bad_manifest = clone $manifest;
$bad_manifest->entry_path = 'src/missing-entry.phs';
$discovery = new \read_sources\Source_Discovery($bad_manifest, $sources);
$discovery->init();
$discovery->run();
try {
    $discovery->finalize();
}
catch (Throwable) {
}
Step_Test::state($discovery, \compile\step_status::failed, ['init', 'run', 'finalize', 'result', 'store']);
echo "all step lifecycles ok: 21 owners, transitions, typed identity, input purity, warm output, terminal failures and unpublished native cleanup\n";
