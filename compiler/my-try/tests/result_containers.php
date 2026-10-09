<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Exercise ordinary shared preparation, not a runtime-call-specific entrypoint. */
function result_container_compile(string $source, bool $custom_type = false): string
{
	Compiler_Lifecycle::reset();
	if ($custom_type) { result_container_register_provider(); }
	$module = new module('/result-containers', '/result-containers', '/result-containers');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	if ($text !== CPP_Probe::text(Model::$cpp_files)) {
		throw new \LogicException('Result container no-op output changed');
	}
	return $text;
}

/** A test-only provider proves Result admission is extensible by registration alone. */
function result_container_register_provider(): void
{
	$registry = Model::$type_catalog->registry();
	$storage = [generic_contract::value_storable];
	$copy = [generic_contract::copyable_value];
	$capabilities = [generic_contract::value_storable, generic_contract::copyable_value, generic_contract::result_payload];
	$definition = $registry->define_template('parcel', type_definition_origin::runtime, nominal_type_kind::structure,
		[new template_type_parameter('T', $storage, null, $copy)], $capabilities);
	Model::$language_scope->register_type_as('parcel', $definition);
	Model::$cpp_type_bindings->bind_definition($definition, 'scpp::vector_t', 'scpp/vector_t.hpp');
	$string = Language_Types::string_type(Model::$language_scope);
	$payload = $registry->use($registry->intern_application($definition, [$string])->type_id());
	$result_definition = object_cast(Model::$type_catalog->definition('result'), abstract_template_type_definition::class);
	$signature = new callable_signature();
	$signature->return_type = $registry->use($registry->intern_application($result_definition, [$payload])->type_id());
	$parameter = new callable_parameter();
	$parameter->type = $string;
	$parameter->name = 'path';
	$parameter->mode = passing_mode::value;
	$signature->parameters->append($parameter);
	$target = Model::$runtime_callables->register_function('proof.scan_parcel', 'scan_parcel', $signature);
	Model::$language_scope->register_function($target);
	Model::$cpp_callable_bindings->bind_function($target, 'scpp::fs::scan', 'modules/filesystem/filesystem.hpp');
}

$cases = [];
foreach (['vector<int>' => ['[1]', '0'], 'hash<int>' => ['{x: 1}', '"x"']] as $container => [$literal, $key])
{
	foreach (['result', 'result_or_false', 'result_or_bool'] as $family)
	{
		$type = $family . '<' . $container . '>';
		$outputs = '$out, $error';
		if ($family === 'result_or_false') { $outputs = '$out'; }
		if ($family === 'result_or_bool') { $outputs = '$out, $flag'; }
		$source = 'function wrap(' . $container . ' $v): ' . $type . ' { return $v; } '
			. 'function unwrap(' . $type . ' $v): ' . $container . ' { return $v; } '
			. '$v ' . $container . ' = ' . $literal . '; $r = wrap($v); $copy = $r; '
			. '$v[' . $key . '] = 7; $out ' . $container . ' = ' . $literal . '; $error error; $flag = false; '
			. 'if (!take(' . $outputs . ', $copy)) { return 1; } '
			. 'echo $out[' . $key . '], "|"; $out[' . $key . '] = 9; '
			. '$again = unwrap($copy); echo $again[' . $key . '], "|"; '
			. '$selected = false ? $copy : $v; $selected_value ' . $container . ' = $selected; '
			. 'echo $selected_value[' . $key . '], "|"; '
			. 'if (!isset($copy)) { return 2; } if ($error) { return 3; } return 0;';
		$cases[$family . (str_starts_with($container, 'vector') ? '_vector' : '_hash')] = [$source, '1|1|7|'];
	}
}
$cases['coalesce'] = [<<<'PHS'
function fallback(int &$calls): vector<int> { $calls++; return [7]; }
function source(int &$calls): result<vector<int>> { $calls++; $v vector<int> = []; return $v; }
$calls = 0; $out = source($calls) ?? fallback($calls);
echo count($out), "|", $calls, "|";
$missing result_or_false<vector<int>> = false_sentinel;
$other = $missing ?? fallback($calls); echo $other[0], "|", $calls;
return 0;
PHS, '0|1|7|2'];
$cases['sentinels'] = [<<<'PHS'
$out hash<int> = {x: 8};
$missing result_or_false<hash<int>> = false_sentinel;
if (take($out) = $missing) { return 1; }
if (isset($missing)) { return 2; }
$state = false; $yes result_or_bool<hash<int>> = true_sentinel;
if (!take($out, $state, $yes)) { return 3; }
if (!$state) { return 4; }
echo $out["x"];
return 0;
PHS, '8'];
$cases['nested'] = [<<<'PHS'
$v vector<hash<int>> = [{x: 3}]; $r result<vector<hash<int>>> = $v;
$copy vector<hash<int>> = $r; $copy[0]["x"] = 7;
$original vector<hash<int>> = $r; echo $original[0]["x"], "|", $copy[0]["x"];
return 0;
PHS, '3|7'];
$executions = [];
$cases['registered_parcel'] = ['$r = scan_parcel("."); $value parcel<string> = $r; $copy result<parcel<string>> = $value; $e error; if (!take($value, $e, $copy)) { return 1; } if ($e) { return 2; } echo isset($copy); return 0;', '1'];
$cases['failed_extract'] = ['$r result_or_false<vector<int>> = false_sentinel; $out vector<int> = $r; return 99;', ''];
foreach ($cases as $name => [$source, $expected])
{
	try { $text = result_container_compile($source, $name === 'registered_parcel'); }
	catch (\RuntimeException $error) { throw new \RuntimeException($name . ': ' . $error->getMessage()); }
	if ($name === 'failed_extract')
	{
		$text = str_replace('int main()', 'int main() try', $text);
		$end = strrpos($text, '// SCPP-END-ARTIFACT');
		if ($end === false) { $end = strlen($text); }
		$text = substr_replace($text, 'catch (const std::exception &) { return 0; }' . "\n", $end, 0);
	}
	file_put_contents($argv[1] . '/' . $name . '.cpp', $text);
	$executions[$name] = $expected;
}
foreach ([
	'$v vector<int> = []; $r result<vector<int>> = $v; if ($r) {}',
	'$v hash<int> = {}; $r result<hash<int>> = $v; $s = $r ?: $v;',
	'$v vector<int> = []; $r result<vector<int>> = $v; empty($r);',
	'$v vector<int> = []; $r result<vector<int>> = $v; $s hash<int> = $r;',
	'$v vector<int> = []; $r result<vector<int>> = $v; $s = $r ?? vector<string>::[];',
	'$v vector<int> = []; $r result_or_bool<vector<int>> = $v; $s = $r ?? $v;',
	'function f(result<vector<int>> $r): void { $out vector<string> = []; $e error; take($out, $e) = $r; }',
	'struct Item {} function f(result<vector<unique<Item>>> &$r): void { $copy = $r; }',
	'struct Item {} function f(result<hash<vector<unique<Item>>>> &$r): void { $out hash<vector<unique<Item>>>; $e error; take($out, $e) = $r; }',
] as $source)
{
	$failed = false;
	try { result_container_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid Result container emitted: ' . $source);
	}
}
file_put_contents($argv[1] . '/executions.json', json_encode($executions, JSON_THROW_ON_ERROR));

// Completed element declarations own copy permission and invalidate dependent callers.
$directory = $argv[1] . '/source';
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'struct Item { public int $n = 1; } function copy_result(result<vector<Item>> &$r): result<vector<Item>> { return $r; } return 0;';
file_put_contents($path, $valid);
Compiler_Lifecycle::reset();
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->exec_cpp();
$original = CPP_Probe::text(Model::$cpp_files);
file_put_contents($path, str_replace('public int $n = 1;', 'public unique<int> $owned;', $valid));
$failed = false;
try { $compiler->update_cpp([$path]); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'copyable_value'); }
if (!$failed || !Model::$cpp_files->is_empty()) {
	throw new \LogicException('Noncopyable container element edit did not reject Result copy');
}
file_put_contents($path, $valid);
$compiler->update_cpp([$path]);
if ($original !== CPP_Probe::text(Model::$cpp_files)) {
	throw new \LogicException('Result container copy repair differs');
}
Compiler_Lifecycle::reset();
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->exec_cpp();
if ($original !== CPP_Probe::text(Model::$cpp_files)) {
	throw new \LogicException('Result container fresh output differs');
}
echo "Result containers passed\n";
