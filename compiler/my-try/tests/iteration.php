<?php

/* Iteration model, static rejection, retained-body dependency and cleanup proof. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function iteration_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$directory = sys_get_temp_dir() . '/scpp_iteration_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try {
	$valid = 'function source(): vector<int> { return [2, 3]; } function sum(): int { $n = 0; foreach (source() as $k => $v) { $n += $v; } return $n; } return sum();';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	$function = Model::$global_scope->functions_named('sum')[0]->syntax();
	$body = $function->body;
	$loop = $body->statements[1];
	iteration_check($loop instanceof foreach_node && $loop instanceof loop_node, 'Separate foreach loop model');
	iteration_check(iterator_to_array($loop->children()) === [$loop->iterable, $loop->key, $loop->value, $loop->body], 'Wrong foreach inspection order');
	$facts = $loop->require_preparation()->iteration;
	iteration_check($facts !== null && $facts->key !== null && $facts->value->resolved_kind === binding_kind::declaration, 'Missing iteration binding facts');
	iteration_check(!str_contains($original, 'snapshot'), 'Snapshot introduced');
	file_put_contents($path, str_replace('vector<int> { return [2, 3];', 'hash<int> { return {a: 2, b: 3};', $valid));
	$compiler->update_cpp([$path]);
	iteration_check($function->body === $body && String_Conversions::is_string($loop->require_preparation()->iteration->key_type), 'Retained foreach did not refresh its key type');
	file_put_contents($path, str_replace('vector<int> { return [2, 3];', 'int { return 2;', $valid));
	foreach ([[$path], []] as $changed) {
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'registered iterable'); }
		iteration_check($failed && Model::$cpp_files->is_empty(), 'Noniterable dependency failed to invalidate output');
	}
	iteration_check($function->body === $body && $loop->preparation() === null, 'Retained loop or cleanup failed');
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	iteration_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Iteration repair changed output');
	Token_Cleanup::file(Model::syntax_files()[0]);
	Preparation_Cleanup::tree(Model::syntax_files()[0]->root);
	iteration_check($loop->preparation() === null && $loop->iterable->preparation() === null, 'Cleanup retained iteration facts');
	Compiler_Lifecycle::reset();
	$clean = new Compiler();
	$clean->init([$directory]);
	$clean->exec_cpp();
	iteration_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean and incremental iteration differ');
	foreach ([
		'$v = 3; foreach ($v as $x) {}',
		'$v vector<int> = [1]; foreach ($v as &$x) {}',
		'$v vector<int> = [1]; foreach ($v as $x => $x) {}',
		'$v vector<int> = [1]; foreach ($v as $x) {} return $x;',
		'$v vector<int> = [1]; foreach ($v as $k => $x) {} return $k;',
		'function f(vector<int> $v): int { foreach ($v as $x) { return $x; } }',
		'$v vector<int> = [1]; $x string = ""; foreach ($v as $x) {}',
		'$v = "abc"; foreach ($v as $x) {}',
	] as $invalid) {
		file_put_contents($path, $invalid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$failed = false;
		try { $compiler->init([$directory]); $compiler->exec_cpp(); }
		catch (\RuntimeException $error) { $failed = true; }
		iteration_check($failed && Model::$cpp_files->is_empty(), 'Accepted invalid iteration: ' . $invalid);
	}
	echo "iteration model, dependencies and rejection passed\n";
}
finally { unlink($path); rmdir($directory); }
