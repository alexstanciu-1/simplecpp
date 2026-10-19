<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

$source = $argv[1];
$output = $argv[2];
$opaque = $argv[3] === 'opaque';
$source_proof = ($argv[4] ?? '') === 'source-types';
$initializer_proof = ($argv[4] ?? '') === 'initializers';
if ($initializer_proof) { require __DIR__ . '/prelower_initializer_cases.php'; }
$metadata = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) { $metadata->modules->add($module->name, $module); }
$module_data = json_decode(file_get_contents(dirname(__DIR__) . '/runtime_types.json'), true);
foreach (['measure_begin', 'measure_end'] as $name) {
    $module_data['functions'][] = ['id' => 'proof.' . $name, 'name' => $name, 'parameters' => [], 'returns' => 'void'];
    $module_data['cpp'][] = ['id' => 'proof.' . $name, 'symbol' => $name, 'header' => 'runtime_type_support.hpp'];
}
if ($initializer_proof) {
    foreach (['init_step' => 'receipt', 'init_allocations' => 'int', 'init_number' => 'int'] as $name => $returns) {
        $module_data['functions'][] = ['id' => 'proof.' . $name, 'name' => $name, 'parameters' => [], 'returns' => $returns];
        $module_data['cpp'][] = ['id' => 'proof.' . $name, 'symbol' => $name, 'header' => 'runtime_type_support.hpp'];
    }
}
$metadata->modules->add('type_proof', Runtime_Catalog_Reader::decode(json_encode($module_data), 'type_proof.json'));
Compiler_Lifecycle::configure_runtime($metadata);
if (!is_dir($source)) { mkdir($source, 0777, true); }
$program = <<<'PHS'
function replace(vector<vector<int>> &$root): int {
 $row vector<int>; $row[] = 11;
 $next vector<vector<int>>; $next[] = $row; $next[] = $row;
 $root = $next;
 return 9;
}
function failure(): void { $p parcel<receipt>; $copy = unpack($p); }
function work(): int {
 $row vector<int> = []; $row[] = 3; $row[] = 4;
 $root vector<vector<int>>; $root[] = $row;
 $root[0][0] = replace($root);
 if ($root[0][0] !== 9) { return 1; }
 if (count($root) !== 2) { return 2; }
 if (count($root[1]) !== 1) { return 3; }
 $table hash<int> = []; $table["key"] = 7;
 if ($table["key"] !== 7) { return 4; }
 $small int8 = (int8)7;
 if ((int)$small !== 7) { return 5; }
 $parcel parcel<receipt>;
 $payload = unpack($parcel);
 $items vector<receipt>; $items[] = $payload;
 $nested vector<vector<receipt>>; $nested[] = $items;
 $nested[0][0] = $payload;
 $copied = $nested[0][0];
 if (receipt_number($copied) !== 0) { return 6; }
 return 0;
}
measure_begin();
$answer = work();
measure_end();
return $answer;
PHS;
if ($initializer_proof) { $program = $initializer_program; }
if ($source_proof) {
    require __DIR__ . '/prelower_source_cases.php';
    $program = $source_program;
    if (($argv[5] ?? '') === 'method-edit') { $program = str_replace('$this->amount + 1', '$this->amount + 2', $program); }
    if (($argv[5] ?? '') === 'layout-edit') {
        $extra = '';
        for ($i = 0; $i < 9; $i++) { $extra .= ' public int $extra' . $i . ' = 9;'; }
        $program = str_replace('public int $amount = 7;', 'public int $amount = 7;' . $extra, $program);
    }
}
file_put_contents($source . '/main.phs', $program);
$compiler = new Compiler();
$compiler->cpp_lowering->prelower_templates = $opaque;
$compiler->cpp_lowering->runtime_template_abi = $opaque;
$compiler->init([$source]);
$compiler->exec_cpp();
function snapshot(): array {
    $result = [];
    foreach (Model::$cpp_files as $artifact) { $result[$artifact->file_name] = $artifact->text; }
    ksort($result);
    return $result;
}
$before = snapshot();
if ($source_proof && $opaque) {
    $requested = [];
    foreach (Model::$cpp_output_program->fragments as $owner) {
        foreach (Model::$cpp_output_program->fragments[$owner]->specializations as $type) {
            $requested[CPP_Opaque_Representation::identity($type)] = true;
        }
    }
    foreach (array_keys($requested) as $name) {
        if (str_contains($name, 'Unused') || str_contains($name, 'weak<')) {
            throw new \LogicException('Unrequested ownership representation: ' . $name);
        }
    }
    foreach (['Invoice', 'value<Invoice>', 'unique<Invoice>', 'vector<Invoice>'] as $name) {
        if (!isset($requested[$name])) { throw new \LogicException('Missing requested representation: ' . $name); }
    }
    $native_header = $before[CPP_Artifacts::type_header('Invoice')];
    if (str_contains($native_header, 'specializations/')) { throw new \LogicException('Native payload depends on facade layout'); }
    foreach ($before as $path => $text) {
        if (($path === 'entry.cpp') || str_starts_with($path, 'units/')) {
            if (str_contains($text, '#include "types/')) { throw new \LogicException('Application unit includes native payload definitions'); }
        }
    }
}

$fragments = [];
foreach (Model::$cpp_output_program->fragments as $owner) { $fragments[] = Model::$cpp_output_program->fragments[$owner]; }
$compiler->cpp();
if (snapshot() !== $before) { throw new \LogicException('Cached operation replay changed output'); }
$index = 0;
foreach (Model::$cpp_output_program->fragments as $owner) {
    if (Model::$cpp_output_program->fragments[$owner] !== $fragments[$index++]) { throw new \LogicException('No-op regenerated a fragment'); }
}
$work_owner = Model::$global_scope->functions_named('work')[0]->syntax()->body->work();
$work_fragment = Model::$cpp_output_program->fragments[$work_owner];
// A changed entry body must replay requests from the unchanged function body.
$edited = str_replace('return $answer;', 'return $answer + 0;', $program);
file_put_contents($source . '/main.phs', $edited);
$compiler->update_cpp([$source . '/main.phs']);
if (Model::$cpp_output_program->fragments[$work_owner] !== $work_fragment) { throw new \LogicException('Entry edit regenerated unchanged work body'); }
$edited_snapshot = snapshot();
if ($edited_snapshot === $before) { throw new \LogicException('Entry edit did not change output'); }
if ($initializer_proof) {
    $short = str_replace('$list vector<receipt> = [$payload, $payload];', '$list vector<receipt> = [$payload];', $program);
    file_put_contents($source . '/main.phs', $short);
    $compiler->update_cpp([$source . '/main.phs']);
    $length_edit = snapshot();
    foreach ($before as $path => $text) {
        if (str_starts_with($path, 'specializations/') && ($length_edit[$path] ?? null) !== $text) {
            throw new \LogicException('Literal length changed specialization support');
        }
    }
}
if ($source_proof && $opaque) {
    // Remove the complete function before its body substrings.
    $reduced = preg_replace('/function make_unique\(\): unique<Invoice> \{.*?\}/', '', $program);
    $reduced = str_replace(['$owner unique<Invoice> = new Invoice();', '$moved = move($owner);'], '', $reduced);
    file_put_contents($source . '/main.phs', $reduced);
    $compiler->update_cpp([$source . '/main.phs']);
    foreach (Model::$cpp_output_program->fragments as $owner) {
        foreach (Model::$cpp_output_program->fragments[$owner]->specializations as $type) {
            if (CPP_Opaque_Representation::identity($type) === 'unique<Invoice>') {
                throw new \LogicException('Removed unique use retained a representation request');
            }
        }
    }
}
// Removing every call must drop every operation request, including dead support headers.
file_put_contents($source . '/main.phs', 'return 0;');
$compiler->update_cpp([$source . '/main.phs']);
foreach (array_keys(snapshot()) as $path) {
    if (str_starts_with($path, 'specializations/runtime_')) { throw new \LogicException('Removed body retained operation artifact'); }
}
if ($opaque) {
    file_put_contents($source . '/main.phs', '$r = make_receipt(1); return 0;');
    $failed = false;
    try { $compiler->update_cpp([$source . '/main.phs']); }
    catch (\RuntimeException $error) { $failed = true; }
    if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Unsupported call published incomplete ABI output'); }
}
if ($source_proof && $opaque) {
    $unsupported = [
        'selected source constructors' => 'class C { function __construct(int $x) {} } $c = new C(1);',
        'source method calls' => 'class C { function ping(): int { return 1; } } $c = new C(); $x = $c->ping();',
        'property access' => 'class C { public int $x = 1; } $c = new C(); $x = $c->x;',
        'static property access' => 'class C { public static int $x = 1; } $x = C::$x;',
        'native source calls' => 'function app(): int { return 1; } class C { function ping(): int { return app(); } }',
        'weak observation' => 'class C {} $c = new C(); $w = weak($c);',
    ];
    foreach ($unsupported as $expected => $input) {
        file_put_contents($source . '/main.phs', $input);
        $rejected = false;
        try { $compiler->update_cpp([$source . '/main.phs']); }
        catch (\RuntimeException $error) { $rejected = str_contains($error->getMessage(), $expected); }
        if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Missing ABI coverage rejection: ' . $expected); }
    }
}
file_put_contents($source . '/main.phs', $program);
$compiler->update_cpp([$source . '/main.phs']);
if (snapshot() !== $before) { throw new \LogicException('Remove/restore changed operation artifacts'); }
// Representation switches invalidate fragments without re-running semantic work.
$work_owner = Model::$global_scope->functions_named('work')[0]->syntax()->body->work();
$work_version = $work_owner->version;
$compiler->cpp_lowering->prelower_templates = !$opaque;
$compiler->cpp_lowering->runtime_template_abi = !$opaque;
$compiler->cpp();
if ($work_owner->version !== $work_version) { throw new \LogicException('Representation switch changed preparation'); }
$compiler->cpp_lowering->prelower_templates = $opaque;
$compiler->cpp_lowering->runtime_template_abi = $opaque;
$compiler->cpp();
if (snapshot() !== $before) { throw new \LogicException('Representation switch did not recover'); }
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->cpp_lowering->prelower_templates = $opaque;
$fresh->cpp_lowering->runtime_template_abi = $opaque;
$fresh->init([$source]);
$fresh->exec_cpp();
if (snapshot() !== $before) { throw new \LogicException('Fresh/incremental output differs'); }
$artifacts = Model::$cpp_files;
if ($source_proof) {
    // Test instrumentation only: both native payload definitions get identical tracked fields.
    $instrumented = new Storage();
    foreach ($artifacts as $artifact) {
        $text = $artifact->text;
        foreach (['Invoice', 'Row'] as $name) {
            if ($artifact->file_name !== CPP_Artifacts::type_header($name)) { continue; }
            $opening = 'struct ' . CPP_Generator::source_name('record', $name) . "\n{\n";
            $text = '#include "instrument.hpp"' . "\n" . str_replace($opening, $opening . "Tracked proof_payload;\n", $text);
        }
        $instrumented->append(new cpp_artifact($artifact->identity, $artifact->kind(), $artifact->file_name, $text));
    }
    $artifacts = $instrumented;
}
$header = '#include "instrument.hpp"' . "\n" . file_get_contents(dirname(__DIR__) . '/runtime_type_support.hpp');
$header = str_replace('struct receipt { scpp::int_t<> number; };', 'struct receipt { scpp::int_t<> number; Tracked payload{}; };', $header);
$header .= "\nvoid " . CPP_Generator::source_name('function', 'failure') . "();\ninline void measure_begin() { begin(); }\ninline void measure_end() { report(\"expressions\"); begin(); copies_before_throw=0; try { " . CPP_Generator::source_name('function', 'failure') . "(); require(false); } catch(const CopyFailure&) {} copies_before_throw=-1; require(live_values==0); report(\"failure\"); }\n";
if ($source_proof) {
    require __DIR__ . '/prelower_source_identity.php';
    $header = str_replace('inline void measure_begin()', 'void source_identity_proof(); inline void measure_begin()', $header);
    $header = str_replace('report("failure"); }', 'report("failure"); source_identity_proof(); }', $header);
}
if ($initializer_proof) {
    // Replace only instrumentation hooks; application initializer code is emitted from source.
    $header = substr($header, 0, strpos($header, "\nvoid " . CPP_Generator::source_name('function', 'failure')));
    $header .= $initializer_provider;
}
$artifacts->append(new cpp_artifact('proof:provider', cpp_artifact_kind::header, 'runtime_type_support.hpp', $header));
foreach (['instrument.hpp', 'instrument.cpp'] as $name) {
    $instrument = file_get_contents(__DIR__ . '/prelower_boundary/' . $name);
    if ($initializer_proof && $name === 'instrument.cpp') {
        // Test-only isolation of stack-trace allocation from initializer transport.
        // Unfiltered runs still call libc and retain their complete counters.
        $instrument .= <<<'CPP'

#include <dlfcn.h>
extern "C" int backtrace(void** frames, int count) noexcept {
    if (std::getenv("SCPP_PROOF_NO_TRACE") != nullptr) { return 0; }
    using capture = int (*)(void**, int);
    static auto original = reinterpret_cast<capture>(dlsym(RTLD_NEXT, "backtrace"));
    if (original == nullptr) { std::abort(); }
    return original(frames, count);
}
CPP;
        $instrument = str_replace('Counters counts;', 'bool init_fail_allocation = false; Counters counts;', $instrument);
        $instrument = str_replace('void* p = nullptr;', 'if(init_fail_allocation) { init_fail_allocation=false; throw std::bad_alloc(); } void* p = nullptr;', $instrument);
    }
    $artifacts->append(new cpp_artifact('proof:' . $name, $name === 'instrument.hpp' ? cpp_artifact_kind::header : cpp_artifact_kind::translation_unit,
        $name, $instrument));
}
(new Output_Publication($output))->publish($artifacts, CPP_Runtime_Build::requirements(Model::$runtime_metadata));
echo "expression emission and cached replay passed\n";
