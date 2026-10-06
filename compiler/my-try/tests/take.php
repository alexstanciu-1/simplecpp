<?php

/* Canonical take preparation, output restrictions and retained recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function take_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function take_source(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->exec_cpp();
	return $compiler;
}

$directory = sys_get_temp_dir() . '/scpp_take_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	foreach (['take($v, $e) = $r', 'take($v, $e, $r)'] as $expression)
	{
		take_source($path, '$r result<int> = 1; $v = 0; $e error; $ok bool = ' . $expression . ';');
		$root = Model::syntax_files()[0]->root;
		$node = $root->single_body()->body->statements[3]->initializer;
		take_check($node instanceof take_expression_node, 'Take spellings must share a node');
		take_check($node->require_preparation()->operation === take_operation::error_result, 'Wrong take family');
		Preparation_Cleanup::tree($root);
		take_check($node->preparation() === null && $node->source->preparation() === null
			&& $node->value_binding->preparation() === null && $node->alternative_binding->preparation() === null,
			'Take cleanup missed a named edge');
	}
	foreach ([
		'$r = 1; $v = 0; take($v) = $r;',
		'$r ?int = 1; take($v) = $r;',
		'$r ?int = 1; $v = 0.0; take($v) = $r;',
		'$r result<int> = 1; $v = 0; take($v) = $r;',
		'$r ?int = 1; $v = 0; $e error; take($v, $e) = $r;',
		'$r result<int> = 1; $v = 0; $e = false; take($v, $e) = $r;',
		'$r result_or_bool<bool> = true; $v = false; take($v, $v) = $r;',
		'$r ?int = 1; take(0) = $r;',
		'$r ?int = 1; take($v int) = $r;',
		'$e error; error::$no_error = $e;',
		'$r ?int = 1; $v = 0; $x = (take($v) = $r) + 1;',
	] as $source)
	{
		$failed = false;
		try { take_source($path, $source); }
		catch (\RuntimeException $error) { $failed = true; }
		take_check($failed, 'Missing take rejection: ' . $source);
		take_check(Model::$cpp_files->is_empty(), 'Rejected take published output');
	}
	$valid = 'function source(): ?int { return 3; } '
		. 'function read(): bool { $v = 0; return take($v) = source(); } '
		. 'function stable(): int { return 9; } $ok = read();';
	$compiler = take_source($path, $valid);
	$original = CPP_Probe::text(Model::$cpp_files);
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('?int { return 3;', '?float { return 3.5;', $valid));
	foreach ([[$path], []] as $changed)
	{
		$compiler->sync($changed);
		$failed = false;
		try { $compiler->prepare(); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'exact branch type'); }
		take_check($failed && Model::$cpp_files->is_empty(), 'Changed take source published stale output');
		take_check($stable->version === $version, 'Take change rebuilt independent body');
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	take_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Take repair changed output');
	take_source($path, $valid);
	take_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental take differ');
	echo "take: PASS\n";
}
finally
{
	Compiler_Lifecycle::reset();
	if (is_file($path)) { unlink($path); }
	rmdir($directory);
}
