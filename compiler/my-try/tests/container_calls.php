<?php

/* Container calls reuse ordinary read/conversion facts and stable-reference admission. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function container_call_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_container_calls_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$valid = 'function consume(int $v): int { return $v; } function run(vector<int> $v): int { return consume($v[0]); } return 0;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = Model::$cpp_files[0]->text;
	$function = Model::$global_scope->functions_named('run')[0]->syntax();
	$body = $function->body;
	$call = $body->statements[0]->expression;
	$index = $call->arguments[0];
	container_call_check($index instanceof index_node && !$index->require_preparation()->addressable, 'Argument indexing exposed an interior reference');
	container_call_check($call->require_call_preparation()->arguments[0]->require_conversion()->operation === conversion_operation::identity, 'Typed index bypassed ordinary call conversion');
	file_put_contents($path, str_replace('consume(int $v)', 'consume(int &$v)', $valid));
	foreach ([[$path], []] as $changed)
	{
		$compiler->sync($changed);
		$failed = false;
		try {
			$compiler->prepare();
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'stable storage');
		}
		container_call_check($failed && Model::$cpp_files->is_empty(), 'Reference signature change did not invalidate indexed call');
	}
	container_call_check($function->body === $body, 'Unchanged caller body was replaced');
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	container_call_check(Model::$cpp_files[0]->text === $original, 'Indexed call recovery changed output');
	Compiler_Lifecycle::reset();
	$clean = new Compiler();
	$clean->init([$directory]);
	$clean->exec_cpp();
	container_call_check(Model::$cpp_files[0]->text === $original, 'Clean and incremental indexed calls differ');
	foreach ([
		'function f(int &$x): void {} $v vector<int> = [1]; f($v[0]);',
		'function f(int &$x): void {} $h hash<int> = {x: 1}; f($h["x"]);',
		'function f(mixed &$x): void {} $m mixed = {x: 1}; f($m["x"]);',
		'function f(mixed &$x): void {} $d dynamic = {x: 1}; f($d["x"]);',
		'function f(vector<int> &$x): void {} $v vector<vector<int>> = [[1]]; f($v[0]);',
		'function f(vector<int> &$x): void {} f(vector<int>::[1]);',
		'function f(hash<int> &$x): void {} $v vector<int> = [1]; f($v);',
	] as $invalid)
	{
		file_put_contents($path, $invalid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$failed = false;
		try {
			$compiler->init([$directory]);
			$compiler->exec_cpp();
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'stable storage');
		}
		container_call_check($failed && Model::$cpp_files->is_empty(), 'Accepted unstable/inexact reference: ' . $invalid);
	}
	echo "container call facts, recovery and reference rejection passed\n";
}
finally {
	unlink($path);
	rmdir($directory);
}
