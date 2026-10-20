<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function generic_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

// Test-only metadata proves inference is not a count-name or count-return special case.
$variable = ['parameter' => 'T'];
$vector = ['name' => 'vector', 'arguments' => [$variable]];
$schemas = [
	['id' => 'proof.identity', 'name' => 'identity', 'type_parameters' => ['T'],
		'parameters' => [['name' => 'value', 'type' => $variable, 'passing' => 'value']], 'returns' => $variable],
	['id' => 'proof.wrap', 'name' => 'wrap', 'type_parameters' => ['T'],
		'parameters' => [['name' => 'value', 'type' => $variable, 'passing' => 'value']], 'returns' => $vector],
	['id' => 'proof.same', 'name' => 'same', 'type_parameters' => ['T'],
		'parameters' => [['name' => 'left', 'type' => $variable, 'passing' => 'value'],
			['name' => 'right', 'type' => $variable, 'passing' => 'value']], 'returns' => 'bool'],
	['id' => 'proof.nested', 'name' => 'nested_count', 'type_parameters' => ['T'],
		'parameters' => [['name' => 'value', 'type' => ['name' => 'vector', 'arguments' => [$vector]], 'passing' => 'observe']], 'returns' => 'int'],
	['id' => 'proof.default', 'name' => 'string_key_count', 'type_parameters' => ['T'],
		'parameters' => [['name' => 'value', 'type' => ['name' => 'hash', 'arguments' => [$variable]], 'passing' => 'observe']], 'returns' => 'int'],
];
$bindings = [];
foreach (['identity', 'wrap', 'same', 'nested', 'default'] as $index => $name) {
	$bindings[] = ['id' => 'proof.' . $name,
		'symbol' => $index < 3 ? 'runtime_generic_test::' . $name : 'scpp::count',
		'header' => $index < 3 ? 'runtime_generic_support.hpp' : 'operators/count/count.hpp'];
}
$schema = ['version' => 1, 'module' => 'proof', 'dependencies' => [], 'functions' => $schemas, 'cpp' => $bindings];
foreach ([$variable, $vector] as $index => $pattern) {
	$schema['functions'][] = ['id' => 'proof.overlap' . $index, 'name' => 'overlap', 'type_parameters' => ['T'],
		'parameters' => [['name' => 'value', 'type' => $pattern, 'passing' => 'observe']], 'returns' => 'int'];
	$schema['cpp'][] = ['id' => 'proof.overlap' . $index, 'symbol' => 'scpp::count', 'header' => 'operators/count/count.hpp'];
}
$input = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) { $input->modules->add($module->name, $module); }
$input->modules->add('proof', Runtime_Catalog_Reader::decode(json_encode($schema), 'proof.json'));
Compiler_Lifecycle::configure_runtime($input);

// Startup must reject malformed recipes without replacing the retained configuration.
$invalid_rows = [];
$bad = $schemas[0]; $bad['type_parameters'] = ['T', 'T']; $invalid_rows[] = $bad;
$bad = $schemas[0]; $bad['type_parameters'] = ['U']; $invalid_rows[] = $bad;
$bad = $schemas[0]; $bad['parameters'][0]['type'] = 'int'; $invalid_rows[] = $bad;
$bad = $schemas[0]; $bad['parameters'][0]['type'] = ['name' => 'vector', 'arguments' => [$variable, $variable]]; $invalid_rows[] = $bad;
$bad = $schemas[0]; $bad['returns'] = ['name' => 'vector', 'arguments' => ['void']]; $invalid_rows[] = $bad;
$bad = $schemas[0]; $bad['parameters'][0]['passing'] = 'reference'; $bad['parameters'][0]['default'] = ['int'=>0]; $invalid_rows[] = $bad;
foreach ($invalid_rows as $row) {
	$invalid = $schema; $invalid['functions'][0] = $row;
	$candidate = new runtime_catalog_input();
	$candidate->modules->add('core', $input->modules['core']);
	$candidate->modules->add('proof', Runtime_Catalog_Reader::decode(json_encode($invalid), 'bad.json'));
	$failed = false;
	try { Compiler_Lifecycle::configure_runtime($candidate); }
	catch (\Throwable $error) { $failed = true; }
	generic_check($failed && Model::$runtime_metadata === $input, 'Invalid generic metadata replaced configuration');
}
$duplicate = $schemas[0];
$duplicate['id'] = 'proof.duplicate';
$duplicate['type_parameters'] = ['U'];
$duplicate['parameters'][0]['type'] = ['parameter' => 'U'];
$duplicate['returns'] = ['parameter' => 'U'];
$invalid = $schema;
$invalid['functions'][] = $duplicate;
$invalid['cpp'][] = ['id' => 'proof.duplicate', 'symbol' => 'runtime_generic_test::identity', 'header' => 'runtime_generic_support.hpp'];
$candidate = new runtime_catalog_input();
$candidate->modules->add('core', $input->modules['core']);
$candidate->modules->add('proof', Runtime_Catalog_Reader::decode(json_encode($invalid), 'duplicate.json'));
$failed = false;
try { Compiler_Lifecycle::configure_runtime($candidate); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'Duplicate runtime overload'); }
generic_check($failed && Model::$runtime_metadata === $input, 'Alpha-equivalent runtime recipe was accepted');

/** Compile with the same retained catalog input across semantic resets. */
function generic_compile(string $source): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/generic', '/generic', '/generic');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->exec_cpp();
	return $compiler;
}

$cases = [
	'count_vectors' => '$a vector<int> = [1, 2]; $b vector<string> = ["a"]; $c vector<vector<int>> = [[1], [2]]; $u vector<unique<int>>; $n = count($a); $m = count($b); $p = count($c); $z = count($u); $again = count($u); if ($n !== 2 || $m !== 1 || $p !== 2 || $z !== 0 || $again !== 0) { return 1; } return 0;',
	'count_hashes' => '$a hash<string, int> = [10 => "a", 20 => "b"]; $b hash<int> = {x: 3}; $u hash<unique<int>>; $n = count($a); $m = count($b); $z = count($u); if ($n !== 2 || $m !== 1 || $z !== 0) { return 1; } return 0;',
	'count_records' => 'struct Item { int $number; } $a vector<Item>; $b hash<Item>; $n = count($a); $m = count($b); if ($n !== 0 || $m !== 0) { return 1; } return 0;',
	'count_fields' => 'class Bag { public vector<int> $items = [1, 2]; } function produce(int &$calls): Bag { $calls++; return new Bag(); } function inspect(const vector<int> &$v): int { return count($v); } $bag = new Bag(); $calls = 0; $n = count($bag->items); $m = count(produce($calls)->items); $v vector<int> = [1]; $r = inspect($v); if ($n !== 2 || $m !== 2 || $r !== 1 || $calls !== 1) { return 1; } return 0;',
	'count_boxed' => '$m mixed = {a: null, b: 1}; $d dynamic = [1, 2, 3]; $n = count($m); $r = count($d); $again = count($m); if ($n !== 2 || $r !== 3 || $again !== 2) { return 1; } return 0;',
	'generic_returns' => '$n int = identity(3); $s string = identity("ok"); $length = strlen($s); $v vector<int> = wrap(7); $first = $v[0]; $size = count(wrap(8)); $equal = same(4, 4); if ($n !== 3 || $length !== 2 || $first !== 7 || $size !== 1 || !$equal) { return 1; } return 0;',
	'generic_patterns' => '$v vector<vector<int>> = [[1], [2]]; $h hash<int> = {a: 3}; $n = nested_count($v); $m = string_key_count($h); if ($n !== 2 || $m !== 1) { return 1; } return 0;',
	'generic_order' => 'function step(int &$n): int { $n++; return $n; } $n = 0; $equal = same(step($n), step($n)); if ($equal || $n !== 2) { return 1; } return 0;',
	'count_shadow' => 'function count(int $value): int { return $value; } return count(0);',
];
foreach ($cases as $name => $source) {
	generic_compile($source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}

foreach ([
	'count();', 'count(1);', 'count(true);', 'count(1.5);', 'count("abc");', 'count(null);',
	'$x ?int = 1; count($x);', '$x result<int> = 1; count($x);', '$x shared<int>; count($x);',
	'count([1, 2]);', '$v vector<int>; count($v, $v);',
	'same(1, "1");', 'nested_count(vector<int>::[1]);',
	'$h hash<int, int>; string_key_count($h);',
	'overlap(vector<int>::[1]);',
] as $source) {
	$failed = false;
	try { generic_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	generic_check($failed && Model::$cpp_files->is_empty(), 'Invalid generic/count call published artifacts: ' . $source);
}

// Dynamic kinds remain runtime checks, not guessed from initializer syntax.
foreach (['mixed', 'dynamic'] as $family) {
	foreach (['null', '1', '"abc"'] as $index => $literal) {
		generic_compile('$x ' . $family . ' = ' . $literal . '; $n = count($x); return 91;');
		$text = str_replace('int main(int argc, char** argv)', "// SCPP-PROBE-OBSERVE-ENTRY\nint observed_entry(int argc = 0, char** argv = nullptr)", CPP_Probe::text(Model::$cpp_files));
		$text = '#include <stdexcept>' . "\n" . $text
			. "\nint main() { try { observed_entry(); } catch (const std::runtime_error&) { return 0; } return 92; }\n";
		file_put_contents($argv[1] . '/count_bad_' . $family . '_' . $index . '.cpp', $text);
	}
}

// A concrete signature is attached per call; both calls retain the same provider identity.
generic_compile('$a vector<int>; $b vector<string>; $x = count($a); $y = count($b);');
$body = Model::syntax_files()[0]->root->single_body()->body;
$first = $body->statements[2]->expression->value->require_call_preparation();
$second = $body->statements[3]->expression->value->require_call_preparation();
generic_check(weakref_get($first->target) === weakref_get($second->target), 'Specializations replaced provider identity');
generic_check($first->signature !== $second->signature, 'Provider cached a single concrete signature');
generic_check(!$first->signature->parameters[0]->type->matches($second->signature->parameters[0]->type), 'Inferred payload types collapsed');
$old = Model::$runtime_callables->function_definition('core.count.vector');
$metadata = Model::$runtime_metadata;
Compiler_Lifecycle::reset_syntax();
generic_check(Model::$runtime_metadata === $metadata, 'Generic reset reread metadata');
$failed = false;
try { Model::$cpp_callable_bindings->function_binding($old); }
catch (\LogicException $error) { $failed = true; }
generic_check($failed, 'Stale generic binding survived reset');

// Incremental producer-type changes must reselect, and rejection must recover cleanly.
$valid = 'function items(): vector<int> { return vector<int>::[1]; } function size(): int { return count(items()); }';
$compiler = generic_compile($valid);
$original = CPP_Probe::text(Model::$cpp_files);
$record = Model::sources()[0];
$record->file->content = 'function items(): int { return 1; } function size(): int { return count(items()); }';
$failed = false;
try { $compiler->update_cpp(['/generic/main.phs']); }
catch (\RuntimeException $error) { $failed = true; }
generic_check($failed && Model::$cpp_files->is_empty(), 'Incremental invalid count retained output');
$record->file->content = $valid;
$compiler->update_cpp(['/generic/main.phs']);
generic_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Generic incremental recovery differs from clean output');
echo "Runtime generics passed\n";
