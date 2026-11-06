<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require semantic diagnostics before native compilation. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

/** Read only emitted native artifacts for incremental/fresh equivalence. @return string[] */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
		$path = $item->getPathname();
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['cpp', 'hpp'], true)) {
			$files[substr($path, strlen($directory) + 1)] = file_get_contents($path);
		}
	}
	ksort($files);
	return $files;
}

/** Remove the isolated proof tree, including emitted bundles. */
function remove_tree(string $path): void
{
	foreach (new DirectoryIterator($path) as $item) {
		if (!$item->isDot()) {
			$item->isDir() ? remove_tree($item->getPathname()) : unlink($item->getPathname());
		}
	}
	rmdir($path);
}

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $params, string $type, string $body): string =>
		($flavor === flavor::cpp ? "$type $name($params)" : "function $name($params): $type") . '{' . $body . '}';
	$decl = static fn(string $value): string => match ($flavor) {
		flavor::php => '$x float=' . $value . ';',
		flavor::cpp => 'float x=' . $value . ';',
		flavor::js => 'let x: float=' . $value . ';',
	};
	$x = $var('x');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('mixed_ordering', $code, $flavor), new entry_target(31));
	$entry = $prepare('return (int)(1 < 2.0);');
	verify($entry->body()->expression_visits === 4, 'Promotion revisited operands');
	$binary = $entry->body()->statements[0]->value->operand;
	verify($binary->execution_type() === \scpp\compiler\type_float64::instance(), 'Wrong execution type');
	verify($binary->left_conversion === \scpp\compiler\value_conversion::numeric_floating
		&& $binary->right_conversion === \scpp\compiler\value_conversion::identity, 'Wrong conversion direction');
	verify(!$binary->constant_known(), 'Promotion introduced folding');
	$before_reset = (new Cpp_Emitter())->emit($entry);
	$binary->reset_preparation();
	verify($binary->left_conversion === \scpp\compiler\value_conversion::identity
		&& $binary->right_conversion === \scpp\compiler\value_conversion::identity, 'Reset retained promotion conversions');
	try {
		$binary->execution_type();
		throw new RuntimeException('Reset retained execution type');
	}
	catch (LogicException) {
	}
	$binary->left->type_def()->prepare_binary($binary);
	verify((new Cpp_Emitter())->emit($entry) === $before_reset, 'Reset/reprepare changed promoted output');

	$code = '';
	$expected = '';
	foreach (['1 < 2.0' => true, '2.0 > 1' => true, '1 <= 1.0' => true, '1.0 >= 1' => true,
		'9007199254740993 > 9007199254740992.0' => false,
		'9007199254740993 <= 9007199254740992.0' => true,
		'9007199254740992.0 < 9007199254740993' => false,
		'9007199254740992.0 >= 9007199254740993' => true,
		'9223372036854775807 < 9223372036854775808.0' => false, '-0.0 < 0' => false] as $expression => $answer) {
		$code .= 'if (' . $expression . ') { echo "T"; } else { echo "F"; }';
		$expected .= $answer ? 'T' : 'F';
	}
	$code .= $fn('left', '', 'int', 'echo "L"; return 1;')
		. $fn('right', '', 'float', 'echo "R"; return 2.0;')
		. 'if (left() < right()) { echo "Y"; }';
	$expected .= 'LRY';
	$entry = $prepare($code . 'return 8;');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated facts');
	verify(str_contains($cpp, 'static_cast<double>'), 'Missing shared numeric conversion');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected)];
	foreach (['1 == 1.0', '1.0 != 1', 'true < 1.0', '(byte)1 < 1.0'] as $invalid) {
		rejects(fn() => $prepare('if (' . $invalid . ') {} return 8;'), 'Unsupported binary');
	}
	rejects(fn() => $prepare($decl('1.0') . $x . '+=1; return 8;'), 'Unsupported compound');
	rejects(fn() => $prepare($decl('1.0') . $x . '=1; return 8;'), 'implicit conversion');
	$literal = $prepare('return 1;')->body()->statements[0]->value;
	foreach (\scpp\compiler\conversion_context::cases() as $context) {
		if (in_array($context, [\scpp\compiler\conversion_context::explicit_cast, \scpp\compiler\conversion_context::typed_destination], true)) {
			verify(\scpp\compiler\type_float64::instance()->prepare_conversion($literal, $context)
				=== \scpp\compiler\value_conversion::numeric_floating, 'Allowed boundary rejected');
		}
		else {
			rejects(fn() => \scpp\compiler\type_float64::instance()->prepare_conversion($literal, $context), 'implicit conversion');
		}
	}
	$source = '';
	foreach (['less' => '<', 'less_equal' => '<=', 'greater' => '>', 'greater_equal' => '>='] as $name => $op) {
		foreach (['if' => ['int', 'float64'], 'fi' => ['float', 'int64']] as $order => [$left, $right]) {
			$params = match ($flavor) {
				flavor::php => $left . ' $a, ' . $right . ' $b',
				flavor::cpp => $left . ' a, ' . $right . ' b',
				flavor::js => 'a: ' . $left . ', b: ' . $right,
			};
			$source .= $fn($name . '_' . $order, $params, 'bool', 'return ' . $var('a') . $op . $var('b') . ';');
		}
	}
	$observer = file_get_contents(__DIR__ . '/mixed_ordering_observer.cpp');
	foreach (['less', 'less_equal', 'greater', 'greater_equal'] as $name) {
		foreach (['if', 'fi'] as $order) {
			$observer = str_replace('@' . $name . '_' . $order . '@', 'scpp_function_' . bin2hex($name . '_' . $order), $observer);
		}
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$directory = sys_get_temp_dir() . '/scpp-mixed-ordering-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'if (value() >= 8) { echo "Y"; } return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $expression) use ($path, $fn, &$stamp): void {
			file_put_contents($path, $fn('value', '', $type, 'return ' . $expression . ';'));
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('float', '8.0');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['float', '16.0 / 2.0'], ['int', '8'], ['bool', 'true'], ['float', '4.0 * 2.0'],
			['float', '1e309'], ['float64', '+(10.0) - 2.0']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '1e309')) {
				$message = $type === 'bool' ? 'Unsupported binary' : 'finite';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($expression === '16.0 / 2.0' ? 1 : 2), 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental differs from fresh output');
				$fixtures[$flavor->name . '_recovery'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		}
		file_put_contents($directory . '/main.' . $flavor->name,
			'if (8 <= value()) { echo "Y"; } return 8;');
		touch($directory . '/main.' . $flavor->name, ++$stamp);
		verify($project->run()['preparation']['prepared'] === 1, 'Operator edit prepared unchanged provider');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Operator edit differs from fresh output');
		$fixtures[$flavor->name . '_operator_edit'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	}
	finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Mixed ordering: conversion contexts, single-pass promotion and incremental/fresh recovery passed\n";
