<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function fallthrough_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

function fallthrough_program(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->sync([]);
	return $compiler;
}

function fallthrough_rejection(Compiler $compiler, string $diagnostic): void
{
	$rejected = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$rejected = str_contains($error->getMessage(), $diagnostic);
	}
	fallthrough_check($rejected, 'Expected preparation diagnostic: ' . $diagnostic);
	fallthrough_check(!Model::$rebuild_required && Model::$prepared_files->is_empty()
		&& Model::$cpp_files->is_empty(), 'Fallthrough failure did not withhold completed output normally');
}

$directory = sys_get_temp_dir() . '/scpp_fallthrough_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$diagnostic = 'S2S non-void function missing can reach the end without returning a value';
	foreach ([
		'function missing(): int {} return 0;',
		'function missing(): value<int> { $a = 1; } return 0;',
		'function missing(): string {} return 0;',
		'struct Point { int $x; } function missing(): Point { $p Point; } return 0;',
		'function helper(): int { return 3; } function missing(): int { helper(); } return 0;',
	] as $source) {
		$compiler = fallthrough_program($path, $source);
		fallthrough_rejection($compiler, $diagnostic);
	}
	$compiler = fallthrough_program($path, 'function missing(): int { return; } return 0;');
	fallthrough_rejection($compiler, 'non-void return requires a value');
	$compiler = fallthrough_program($path, 'function missing(): int { return 1; $a = $unknown; } return 0;');
	fallthrough_rejection($compiler, 'unknown');

	$valid = 'function result(): int { return 7; } function stable(): int { return 2; } return result();';
	$compiler = fallthrough_program($path, $valid);
	$compiler->prepare();
	$compiler->cpp();
	$original = Model::$cpp_files[0]->text;
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	file_put_contents($path, str_replace('return 7;', '$a = 7;', $valid));
	$compiler->sync([$path]);
	fallthrough_rejection($compiler, 'function result can reach the end');
	$compiler->sync([]);
	fallthrough_rejection($compiler, 'function result can reach the end');
	fallthrough_check($stable->version === $version, 'Failure rebuilt independent body');
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	fallthrough_check(Model::$cpp_files[0]->text === $original, 'Recovery differs from clean output');

	// A signature-only edit must recheck the unchanged body's completion.
	$compiler = fallthrough_program($path, 'function missing(): void {} return 0;');
	$compiler->prepare();
	file_put_contents($path, 'function missing(): int {} return 0;');
	$compiler->sync([$path]);
	fallthrough_rejection($compiler, $diagnostic);
	file_put_contents($path, 'function missing(): void {} return 0;');
	$compiler->update_cpp([$path]);

	$controls = [
		'empty_entry' => ['', 0],
		'entry_fallthrough' => ['$a = 3;', 0],
		'void_fallthrough' => ['function f(): void {} f();', 0],
		'void_return' => ['function f(): void { return; } f(); return 0;', 0],
		'value_return' => [$valid, 7],
		'early_return' => ['function f(): int { return 4; $a = 9; } return f();', 4],
	];
	$executions = [];
	foreach ($controls as $name => [$source, $expected]) {
		$compiler = fallthrough_program($path, $source);
		$compiler->prepare();
		$compiler->cpp();
		if (isset($argv[1])) {
			$output = $argv[1] . '/' . $name . '.cpp';
			file_put_contents($output, Model::$cpp_files[0]->text);
			$executions[] = ['path' => $output, 'exit_code' => $expected];
		}
	}
	if (isset($argv[1])) {
		file_put_contents($argv[1] . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
	}
	echo "Fallthrough: preparation rejection, return controls, incremental retry and recovery passed\n";
}
finally {
	unlink($path);
	rmdir($directory);
}
