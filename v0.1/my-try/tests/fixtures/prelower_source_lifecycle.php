<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';
require __DIR__ . '/prelower_source_lifecycle_cases.php';

// Host-only instrumentation around production-generated source bodies. No alternate emitter.
[$script, $source, $output, $mode] = $argv;
$concrete = $mode === 'concrete';
$revision = $argv[4] ?? '';
if ($revision === 'method-edit') {
	$program = str_replace('function __destruct() { $this->touch(); }',
		'function __destruct() { $this->touch(); $this->touch(); }', $program);
}
if ($revision === 'layout-edit')
{
	// Cross the instrumentation's 64-byte alignment boundary, not just spare padding.
	$extra = '';
	for ($index = 0; $index < 9; $index++) {
		$extra .= ' public int $extra' . $index . ' = 9;';
	}
	$program = str_replace('public int $amount = 7;',
		'public int $amount = 7;' . $extra, $program);
}
if (!is_dir($source)) {
	mkdir($source, 0777, true);
}
file_put_contents($source . '/main.phs', $program);
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->cpp_lowering->prelower_templates = $concrete;
$compiler->init([$source]);
$compiler->exec_cpp();

/** Snapshot the complete artifact set for retained replay and recovery checks. */
function lifecycle_snapshot(): array
{
	$result = [];
	foreach (Model::$cpp_files as $artifact) {
		$result[$artifact->file_name] = $artifact->text;
	}
	ksort($result);
	return $result;
}
$before = lifecycle_snapshot();
$compiler->cpp();
if (lifecycle_snapshot() !== $before) {
	throw new \LogicException('Source lifecycle replay changed artifacts');
}
if ($concrete)
{
	file_put_contents($source . '/main.phs', '$f function<int()> = fn(): int => 1; return $f();');
	$compiler->update_cpp([$source . '/main.phs']);
	if (Model::$cpp_files->is_empty()) {
		throw new \LogicException('Supported owned callable storage produced no artifacts');
	}
	file_put_contents($source . '/main.phs', '$f function<int()> = fn(): int => "wrong"; return $f();');
	$rejected = false;
	try {
		$compiler->update_cpp([$source . '/main.phs']);
	}
	catch (\RuntimeException $error) {
		$rejected = true;
	}
	if ((!$rejected) || (!Model::$cpp_files->is_empty())) {
		throw new \LogicException('Invalid callable return survived source preparation');
	}
	file_put_contents($source . '/main.phs', $program);
	$compiler->update_cpp([$source . '/main.phs']);
	if (lifecycle_snapshot() !== $before) {
		throw new \LogicException('Source lifecycle recovery changed artifacts');
	}
}

// Keep the application instrumentation header template-free. Definitions and standard
// library dependencies belong to proof support, alongside native middleware code.
$original = file_get_contents(__DIR__ . '/prelower_boundary/instrument.hpp');
$split = strpos($original, 'inline void require');
$header = preg_replace('/#include [^\n]+\n/', '', substr($original, 0, $split));
$header .= "void require(bool);\nvoid begin();\nvoid report(const char*);\n";
$support = "#include <cstddef>\n#include <cstdio>\n#include <cstdlib>\n#include <new>\n";
$support .= file_get_contents(__DIR__ . '/prelower_boundary/instrument.cpp');
$support .= str_replace('inline void ', 'void ', substr($original, $split));

$artifacts = new Storage();
foreach (Model::$cpp_files as $artifact)
{
	if ($artifact->file_name === 'entry.cpp') {
		continue;
	}
	$text = $artifact->text;
	foreach (['Invoice', 'Row', 'Constructed'] as $name)
	{
		if ($artifact->file_name !== CPP_Artifacts::type_header($name)) {
			continue;
		}
		$opening = 'struct ' . CPP_Generator::source_name('record', $name) . "\n{\n";
		if (!str_contains($text, $opening)) {
			throw new \LogicException('Missing source record instrumentation site');
		}
		$text = '#include "instrument.hpp"' . "\n" . str_replace($opening, $opening . "Tracked proof_payload;\n", $text);
	}
	$artifacts->append(new cpp_artifact($artifact->identity, $artifact->kind(), $artifact->file_name, $text));
}
$names = [];
$proof_context = new cpp_generation_context($concrete ? new CPP_Specializations() : null);
foreach (['work', 'failure', 'constructor_failure', 'make_shared', 'make_unique', 'make_value'] as $name)
{
	$support .= '#include "' . CPP_Artifacts::callable_header($name) . '"' . "\n";
	$function = Model::$global_scope->functions_named($name)[0];
	$names[$name] = CPP_Callables::source_reference($function);
	$result_mapping = CPP_Types::representation($function->signature()->return_type, $proof_context);
	$support .= CPP_Artifacts::includes($result_mapping->headers, '');
}
$support .= '#include <type_traits>' . "\n#include <utility>\n";
$support .= 'using Shared = decltype(' . $names['make_shared'] . '());' . "\n";
$support .= 'using Unique = decltype(' . $names['make_unique'] . '());' . "\n";
$support .= 'using Value = decltype(' . $names['make_value'] . '());' . "\n";
$unique_type = Model::$global_scope->functions_named('make_unique')[0]->signature()->return_type;
$unique_native = $concrete
	? CPP_Types::implementation_representation($unique_type, $proof_context->specialization_engine)
	: CPP_Types::representation($unique_type);
$support .= CPP_Artifacts::includes($unique_native->headers, '');
$support .= 'using NativeUnique = ' . $unique_native->spelling . ";\n";
$support .= 'NativeUnique& inspect_unique(Unique& value) { return '
	. ($concrete ? '*std::launder(static_cast<NativeUnique*>(value.address()))' : 'value') . "; }\n";
$support .= <<<'CPP'
static_assert(std::is_copy_constructible_v<Shared>);
static_assert(!std::is_copy_constructible_v<Unique>);
static_assert(std::is_copy_constructible_v<Value>);
static_assert(std::is_const_v<std::remove_pointer_t<decltype(std::declval<const Value&>().operator->())>>);
static_assert(!std::is_const_v<std::remove_pointer_t<decltype(std::declval<Value&>().operator->())>>);
static_assert(!std::is_const_v<std::remove_pointer_t<decltype(std::declval<const Shared&>().operator->())>>);
int source_lifecycle_proof() {
 begin();
 { require(@WORK@().native_value()==0); }
 require(live_values==0); report("source-methods-constructors");
 for(int limit=0;limit<2;++limit) {
  begin(); copies_before_throw=limit; bool caught=false;
  try { @FAILURE@(); } catch(const CopyFailure&) { caught=true; }
  copies_before_throw=-1; require(caught && live_values==0);
  report(limit==0 ? "method-argument-failure" : "method-result-failure");
 }
 for(int limit=0;limit<3;++limit) {
  begin(); copies_before_throw=limit; bool caught=false;
  try { @CONSTRUCTOR_FAILURE@(); } catch(const CopyFailure&) { caught=true; }
  copies_before_throw=-1; require(caught && live_values==0);
  report(limit==0 ? "constructor-argument-failure" : (limit==1 ? "constructor-parameter-failure" : "constructor-body-failure"));
 }
 begin();
 {
  auto owner=@MAKE_SHARED@(); auto alias=owner;
  require(owner.operator->()==alias.operator->()); require(live_values==1 && counts.copied==0);
  auto unique=@MAKE_UNIQUE@(); auto* identity=unique.operator->(); auto moved=std::move(unique);
  require(inspect_unique(unique).get()==nullptr && moved.operator->()==identity);
  require(live_values==2 && counts.copied==0);
  auto value=@MAKE_VALUE@(); auto copy=value;
  require(value.operator->()!=copy.operator->());
  copy->field_amount=decltype(copy->field_amount){91};
  require(value->field_amount.native_value()==7);
 }
 require(live_values==0); report("source-identities");
 return 0;
}
CPP;
foreach ($names as $name => $reference) {
	$support = str_replace('@' . strtoupper($name) . '@()', $reference . '()', $support);
}
$artifacts->append(new cpp_artifact('proof:header', cpp_artifact_kind::header, 'instrument.hpp', $header));
$artifacts->append(new cpp_artifact('proof:support', cpp_artifact_kind::translation_unit, 'specializations/source_lifecycle_proof.cpp', $support));
$artifacts->append(new cpp_artifact('proof:entry', cpp_artifact_kind::translation_unit, 'entry.cpp', "int source_lifecycle_proof();\nint main() { return source_lifecycle_proof(); }\n"));
$publisher = new Output_Publication($output);
try {
	$publisher->publish($artifacts, CPP_Runtime_Build::requirements(Model::require_runtime_metadata()), $concrete);
}
finally {
	$publisher->close();
}
echo "source lifecycle replay and strict recovery passed\n";
