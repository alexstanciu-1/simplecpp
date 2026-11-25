<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function json_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Compile an isolated source document through the ordinary source and lifecycle owners. */
function json_source(string $text): Compiler
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/json-proof', '/json-proof', '/json-proof');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $text;
	Source_Registry::add($input_module, $file);
	Model::$modules->add($input_module->name, $input_module);
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->exec_cpp();
	return $compiler;
}

json_source('$d result<dynamic> = json_decode("null"); $m result<mixed> = json_decode_mixed("false"); '
	. '$a result<string> = json_encode(dynamic::{n: 1}); $b result<string> = json_encode(mixed::{n: 2});');
$body = Model::syntax_files()[0]->root->single_body()->body;
foreach (['json.decode.dynamic', 'json.decode.mixed', 'json.encode.dynamic', 'json.encode.mixed'] as $index => $key) {
	$facts = $body->statements[$index]->initializer->require_call_preparation();
	json_check(weakref_get($facts->target) === Model::$runtime_callables->function_definition($key), 'Wrong installed JSON target');
	json_check($facts->signature === $facts->target->signature(), 'Runtime signature was copied');
}
json_check(count(Model::$language_scope->functions_named('json_encode')) === 0
	&& count(Model::$language_scope->callables_named('json_encode')) === 2, 'Runtime functions entered source retirement inventory');
Preparation_Cleanup::tree(Model::syntax_files()[0]->root);
json_check($body->statements[0]->initializer->preparation() === null, 'Runtime call retained facts');

foreach ([
	'json_decode();', 'json_decode("null", true);', 'json_encode(1);', 'json_encode({n: 1});',
	'$m mixed = json_decode("{}");', '$d dynamic = json_decode_mixed("{}");',
	'json_encode(vector<int>::[1]);',
] as $source)
{
	$failed = false;
	try {
		json_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	json_check($failed && Model::$cpp_files->is_empty(), 'Invalid JSON call emitted output: ' . $source);
}

// A nearer source definition owns the name even when its signature differs.
$valid = 'function read(): result<dynamic> { return json_decode("null"); } function stable(): int { return 9; }';
$compiler = json_source($valid);
$original = CPP_Probe::text(Model::$cpp_files);
$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
$version = $stable->version;
$record = Model::sources()[0];
$record->file->content = $valid . ' function json_decode(string $text): int { return 1; }';
foreach ([['/json-proof/main.phs'], []] as $changes)
{
	$compiler->sync($changes);
	$failed = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	json_check($failed && Model::$cpp_files->is_empty(), 'Shadowing published stale runtime call');
	json_check($stable->version === $version, 'Shadowing rebuilt independent body');
}
$record->file->content = $valid;
$compiler->update_cpp(['/json-proof/main.phs']);
json_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Removing source shadow did not restore runtime target');
json_source($valid);
json_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Runtime call clean/incremental mismatch');

// A generic registered name uses the same scope, exact-selection and backend paths.
$catalog = Model::$runtime_callables;
$signature = $catalog->function_definition('json.encode.dynamic')->signature();
$custom = $catalog->register_function('proof.custom', 'custom', $signature);
Model::$language_scope->register_function($custom);
json_check(Scope_Lookup::functions(Model::$language_scope, 'custom') === [$custom], 'Runtime lookup depends on JSON spelling');
$context = new cpp_generation_context();
$failed = false;
try {
	CPP_Callables::reference($custom, $context);
}
catch (\LogicException $error) {
	$failed = true;
}
json_check($failed, 'Unbound free function acquired fabricated C++ owner');
Model::$cpp_callable_bindings->bind_function($custom, 'example::custom', 'example/custom.hpp');
json_check(CPP_Callables::reference($custom, $context) === 'example::custom'
	&& isset($context->headers['example/custom.hpp']), 'Free function binding lost symbol/header');
json_check(!method_exists($custom, 'cpp_reference') && !property_exists($custom, 'declaration'), 'Runtime target owns backend/source processing');

// Installed overloads must resolve uniquely; identical contracts cannot be ranked.
$duplicate = $catalog->register_function('proof.duplicate', 'custom', $signature);
Model::$language_scope->register_function($duplicate);
$record = Model::sources()[0];
$record->file->content = 'custom(dynamic::{n: 1});';
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->sync(['/json-proof/main.phs']);
$failed = false;
try {
	$compiler->prepare();
}
catch (\RuntimeException $error) {
	$failed = str_contains($error->getMessage(), 'one exact signature');
}
json_check($failed && Model::$cpp_files->is_empty(), 'Ambiguous registered signatures emitted output');

// Argument changes must reselect the installed target and match a clean build.
$compiler = json_source('function write(): result<string> { return json_encode(mixed::{n: 1}); }');
$record = Model::sources()[0];
$record->file->content = 'function write(): result<string> { return json_encode(dynamic::{n: 1}); }';
$compiler->update_cpp(['/json-proof/main.phs']);
$retargeted = CPP_Probe::text(Model::$cpp_files);
json_source($record->file->content);
json_check(CPP_Probe::text(Model::$cpp_files) === $retargeted, 'Runtime overload retarget differs from clean build');
$old_target = Model::$runtime_callables->function_definition('json.decode.dynamic');
Compiler_Lifecycle::reset_preparation();
json_check(Model::$runtime_callables->function_definition('json.decode.dynamic') === $old_target, 'Preparation reset replaced runtime identity');
Compiler_Lifecycle::reset_syntax();
json_check(Model::$runtime_callables->function_definition('json.decode.dynamic') !== $old_target, 'Syntax reset retained runtime identity');
$failed = false;
try {
	Model::$cpp_callable_bindings->function_binding($old_target);
}
catch (\LogicException $error) {
	$failed = true;
}
json_check($failed, 'Backend accepted a stale runtime identity');

$output = $argv[1];
$executions = [];
$programs = [];
foreach (require __DIR__ . '/fixtures/json.php' as $name => [$source, $predicate])
{
	$programs[$name] = [$source, 0];
	json_source($source);
	$text = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc = 0, char** argv = nullptr)', CPP_Probe::text(Model::$cpp_files));
	$text = '#include <scpp/hash_t.hpp>' . "\n" . '#include <limits>' . "\n" . $text . "\nint main() { return (" . $predicate . ") ? 0 : 91; }\n";
	$path = $output . '/' . $name . '.cpp';
	file_put_contents($path, $text);
	$executions[] = ['path' => $path, 'exit_code' => 0];
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
file_put_contents($output . '/programs.json', json_encode(['valid' => $programs], JSON_PRETTY_PRINT));
echo "JSON: registered targets, exact selection, shadow recovery and programs exported\n";
