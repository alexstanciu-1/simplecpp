<?php

/* Whole-storage resets preserve binding identity and never mutate a copied dynamic object. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function cleanup_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Export real compiler output; rejected sources must not leave a publishable program. */
function cleanup_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/cleanup', '/cleanup', '/cleanup');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	return Model::$cpp_files[0]->text;
}

$cases = [
	'nullable_owner' => '$n ?int = 7; $a =& $n; unset($n); if ($a !== null) { return 1; } unset($n); $a = 9; $v int = $n; if ($v !== 9) { return 2; } return 0;',
	'nullable_scalars' => '$b ?bool = true; $f ?float = 1.5; $s ?string = "x"; unset($b); unset($f); unset($s); if ($b !== null || $f !== null || $s !== null) { return 1; } return 0;',
	'nullable_value_parameter' => 'function reset(?int $n): bool { unset($n); return $n === null; } $n ?int = 8; if (!reset($n)) { return 1; } $v int = $n; if ($v !== 8) { return 2; } return 0;',
	'scalars' => '$n int = 8; $a =& $n; clean($a); $b bool = true; clean($b); $f float = 2.5; clean($f); $s string = "42"; $copy = $s; clean($s); $old int = (int)$copy; if ($f) { return 3; } if ($n !== 0 || $b || !empty($s) || $old !== 42) { return 1; } $a = 3; if ($n !== 3) { return 2; } return 0;',
	'fixed_integer' => '$n int32 = 7; $a =& $n; clean($a); $v int = $n; if ($v !== 0) { return 1; } return 0;',
	'containers' => '$v vector<int> = [1, 2]; $copy = $v; $a =& $v; clean($a); if (!empty($v)) { return 1; } $n = $copy[1]; if ($n !== 2) { return 2; } $a[] = 7; $h hash<int> = {x: 3}; $old = $h; clean($h); if (!empty($h)) { return 3; } $v0 = $v[0]; $hx = $old["x"]; if ($v0 !== 7 || $hx !== 3) { return 4; } return 0;',
	'mixed' => '$m mixed = {x: 3}; $old = $m; $a =& $m; clean($a); if ($m !== null || $a !== null) { return 1; } $n int = $old["x"]; if ($n !== 3) { return 2; } $a["x"] = 9; $n = $m["x"]; if ($n !== 9) { return 3; } clean($m); clean($m); return 0;',
	'dynamic' => '$d dynamic = {x: 3}; $old = $d; $a =& $d; clean($a); if ($d !== null || $a !== null) { return 1; } $n int = $old["x"]; if ($n !== 3) { return 2; } $a = dynamic::{x: 9}; $new int = $d["x"]; $n = $old["x"]; if ($new !== 9 || $n !== 3) { return 3; } return 0;',
	'boxed_scalars' => '$m mixed = "text"; $d dynamic = 7; clean($m); clean($d); if ($m !== null || $d !== null) { return 1; } return 0;',
	'reference_parameter' => 'function reset(dynamic &$d, int &$n): void { clean($d); clean($n); } $d dynamic = {x: 4}; $copy = $d; $n = 8; reset($d, $n); if ($d !== null || $n !== 0) { return 1; } $x int = $copy["x"]; if ($x !== 4) { return 2; } return 0;',
	'field_alias' => 'struct Box { int32 $n; } $b Box; $b->n = 8; $a =& $b->n; clean($a); $n int = $b->n; if ($n !== 0) { return 1; } return 0;',
	'keyed_alias' => '$h hash<int> = {x: 1}; $a =& $h; unset($a["x"]); if (!empty($h)) { return 1; } return 0;',
];
$output = $argv[1] ?? sys_get_temp_dir() . '/scpp_cleanup_' . bin2hex(random_bytes(6));
if (!is_dir($output)) {
	mkdir($output, 0777, true);
}
$executions = [];
foreach ($cases as $name => $source) {
	$cpp = cleanup_source($source);
	$path = $output . '/' . $name . '.cpp';
	file_put_contents($path, $cpp);
	$executions[] = ['path' => $path, 'exit_code' => 0];
}

$rejections = [
	['$n ?int = 1; $a =& $n; unset($a);', 'reference alias'],
	['$n ?int = 1; $a =& $n; $b =& $a; unset($b);', 'reference alias'],
	['function reset(?int &$n): void { unset($n); }', 'reference parameter'],
	['function &id(?int &$n): ?int { return $n; } $n ?int = 1; unset(id($n));', 'local or parameter'],
	['$n = 1; unset($n);', 'nullable storage'],
	['$s = "x"; unset($s);', 'nullable storage'],
	['$v vector<int> = []; unset($v);', 'nullable storage'],
	['$h hash<int> = {}; unset($h);', 'nullable storage'],
	['$m mixed = null; unset($m);', 'nullable storage'],
	['$d dynamic = null; unset($d);', 'nullable storage'],
	['$n ?int = 1; clean($n);', 'supported scalar'],
	['$r result<int> = 1; clean($r);', 'supported scalar'],
	['struct Box { int32 $n; } $b Box; clean($b);', 'supported scalar'],
	['struct Box { int32 $n; } $b Box; clean($b->n);', 'local or parameter'],
	['$v vector<int> = [1]; clean($v[0]);', 'local or parameter'],
	['clean(1);', 'local or parameter'],
	['clean($missing);', 'established local'],
	['clean();', ''],
	['$a = 1; $b = 2; clean($a, $b);', ''],
	['$a ?int = 1; unset();', ''],
	['$a ?int = 1; unset($a, $a);', ''],
	['$a = 1; $b = clean($a);', ''],
];
foreach ($rejections as [$source, $message])
{
	$failed = false;
	try {
		cleanup_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $message);
		cleanup_check($failed, 'Unexpected rejection: ' . $error->getMessage());
	}
	cleanup_check($failed && Model::$cpp_files->is_empty(), 'Invalid cleanup was accepted: ' . $source);
}

// Signature-only changes must revisit a retained body and clear old reset facts on failure.
$directory = sys_get_temp_dir() . '/scpp_cleanup_incremental_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$valid = 'function reset(?int $n): void { unset($n); } return 0;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = Model::$cpp_files[0]->text;
	$function = Model::$global_scope->functions_named('reset')[0]->syntax();
	$body = $function->body;
	$reset = $body->statements[0];
	cleanup_check($reset->require_preparation() instanceof prepared_storage_reset, 'Reset lost its semantic plan');
	cleanup_check(iterator_to_array($reset->children()) === [$reset->target], 'Reset inspection lost target');
	foreach ([str_replace('?int $n', '?int &$n', $valid), str_replace('?int $n', 'int $n', $valid)] as $invalid)
	{
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			cleanup_check($failed && Model::$cpp_files->is_empty() && $reset->preparation() === null, 'Failed reset retained facts/output');
		}
		cleanup_check($function->body === $body, 'Signature edit replaced unchanged body');
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		cleanup_check(Model::$cpp_files[0]->text === $original, 'Reset recovery changed output');
	}
	Token_Cleanup::file(Model::syntax_files()[0]);
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	cleanup_check(Model::$cpp_files[0]->text === $original, 'Cleanup/reprepare changed reset output');

	$clean_source = 'function reset(int &$n): void { clean($n); } return 0;';
	file_put_contents($path, $clean_source);
	$compiler->update_cpp([$path]);
	$clean_function = Model::$global_scope->functions_named('reset')[0]->syntax();
	$clean_body = $clean_function->body;
	foreach (['dynamic', 'mixed', 'string', 'int'] as $type_name)
	{
		file_put_contents($path, str_replace('int &$n', $type_name . ' &$n', $clean_source));
		$compiler->update_cpp([$path]);
		$reset_type = $clean_body->statements[0]->require_preparation()->storage_type();
		cleanup_check($clean_function->body === $clean_body, 'Clean type edit replaced retained body');
		cleanup_check(Type_Queries::canonical($reset_type)->definition() === Model::$type_catalog->definition($type_name), 'Clean retained an old reset type');
	}
	$clean_output = Model::$cpp_files[0]->text;
	file_put_contents($path, str_replace('int &$n', 'result<int> &$n', $clean_source));
	$failed = false;
	try {
		$compiler->update_cpp([$path]);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'supported scalar');
	}
	cleanup_check($failed && Model::$cpp_files->is_empty(), 'Clean signature edit admitted Result cleanup');
	file_put_contents($path, $clean_source);
	$compiler->update_cpp([$path]);
	cleanup_check(Model::$cpp_files[0]->text === $clean_output, 'Clean recovery changed output');
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo 'Cleanup: ' . count($cases) . ' programs, ' . count($rejections) . " rejections and retained recovery passed\n";
