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
	$param = static fn(string $type, string $name): string => $flavor === flavor::js ? $name . ': ' . $type : $type . ' ' . $var($name);
	$decl = static fn(string $type, string $value): string => match ($flavor) {
		flavor::php => '$x ' . $type . '=' . $value . ';',
		flavor::cpp => $type . ' x=' . $value . ';',
		flavor::js => 'let x: ' . $type . '=' . $value . ';',
	};
	$x = $var('x');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('mixed_numeric_families', $code, $flavor), new entry_target(31));
	$arithmetic = ['add' => '+', 'subtract' => '-', 'multiply' => '*', 'divide' => '/'];
	$comparisons = ['equal' => '==', 'not_equal' => '!=', 'less' => '<', 'less_equal' => '<=', 'greater' => '>', 'greater_equal' => '>='];
	$code = '';
	foreach ($arithmetic + $comparisons as $name => $operator) {
		foreach (['bf' => ['byte', 'float'], 'fb' => ['float64', 'uint8']] as $order => [$left, $right]) {
			$result = isset($arithmetic[$name]) ? 'float' : 'bool';
			$body = 'return ' . $var('a') . $operator . $var('b') . ';';
			$code .= $fn($name . '_' . $order, $param($left, 'a') . ',' . $param($right, 'b'), $result, $body);
			$expression = $order === 'bf' ? '(byte)8' . $operator . '2.0' : '2.0' . $operator . '(uint8)8';
			$entry = $prepare('return (int)(' . $expression . ');');
			verify($entry->body()->expression_visits === 5, 'Mixed operands were revisited');
			$binary = $entry->body()->statements[0]->value->operand;
			verify($binary->execution_type() === \scpp\compiler\type_float64::instance(), 'Wrong execution representation');
			verify($binary->type_def() === ($result === 'float' ? \scpp\compiler\type_float64::instance() : \scpp\compiler\type_bool::instance()), 'Wrong result identity');
			verify(!$binary->constant_known(), 'Introduced binary folding');
			$before = (new Cpp_Emitter())->emit($entry);
			$binary->reset_preparation();
			$binary->left->type_def()->prepare_binary($binary);
			verify((new Cpp_Emitter())->emit($entry) === $before, 'Reset/reprepare changed mixed output');
		}
	}
	$ref = match ($flavor) {
		flavor::php => 'float &$v', flavor::cpp => 'float &v', flavor::js => '&v: float',
	};
	foreach ($arithmetic as $name => $operator) {
		$code .= $fn($name . '_write', $ref . ',' . $param('byte', 'rhs'), 'void', $var('v') . $operator . '=' . $var('rhs') . '; return;');
		$entry = $prepare($decl('float', '8.0') . $x . $operator . '=(byte)2; return 8;');
		verify($entry->body()->expression_visits === 4, 'Compound RHS was revisited');
		rejects(fn() => $prepare($decl('byte', '8') . $x . $operator . '=2.0; return 8;'), 'Unsupported compound');
	}
	$code .= $fn('zero', $ref, 'byte', $var('v') . '=100.0; return 0;')
		. $fn('failed_write', $ref, 'void', $var('v') . '/=zero(' . $var('v') . '); return;');
	$observer = file_get_contents(__DIR__ . '/mixed_numeric_families_observer.cpp');
	foreach (array_keys($arithmetic + $comparisons) as $name) {
		foreach (['bf', 'fb'] as $order) $observer = str_replace('@' . $name . '_' . $order . '@', '::App::' . $name . '_' . $order . '_', $observer);
	}
	foreach (array_merge(array_map(static fn(string $name): string => $name . '_write', array_keys($arithmetic)), ['failed_write']) as $name) {
		$observer = str_replace('@' . $name . '@', '::App::' . $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()', (new Cpp_Emitter())->emit($prepare($code . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$main = $decl('float', '8.0') . $x . '+=(byte)2;' . $x . '-=(uint8)4;' . $x . '*=(byte)3;' . $x . '/=(byte)2;'
		. 'if (' . $x . '==9.0 && (byte)7/2.0==3.5 && 7.0/(byte)2==3.5) { echo "Y"; }'
		. 'if ((byte)255<255.5 && 255.5>(byte)255 && (byte)0==-0.0 && -0.0==(byte)0) { echo "Y"; }'
		. $x . '=0.0; for (' . $x . '+=(byte)1;' . $x . '<(byte)3;' . $x . '+=(byte)1) { echo "F"; } return 8;';
	$entry = $prepare($main);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated prepared facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('YYFF')];
	foreach (['(byte)1%1.0', '1.0%(byte)1', 'true+1.0', '1.0==true', 'null<1.0', '"1"+1.0'] as $invalid) {
		rejects(fn() => $prepare('if (' . $invalid . ') {} return 8;'), 'Unsupported binary');
	}
	rejects(fn() => $prepare($decl('float', '1.0') . $x . '=(byte)1; return 8;'), 'implicit conversion');
	$directory = sys_get_temp_dir() . '/scpp-mixed-numeric-families-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, $decl('float', '0.5') . $x . '+=value(); if (' . $x . '==8.5 && value()==8.0 && 8.0<=value()) { echo "Y"; } return 8;');
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
		foreach ([['float', '16.0 / 2.0'], ['byte', '8'], ['bool', 'true'], ['uint8', '8'], ['int', '8'],
			['float', '1e309'], ['float64', '+(10.0) - 2.0']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '1e309')) {
				$message = $type === 'bool' ? 'Unsupported compound' : 'finite';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($expression === '16.0 / 2.0' ? 1 : 2), 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental differs from fresh output');
				$fixtures[$flavor->name . '_recovery_' . count($fixtures)] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		}
		file_put_contents($path, '');
		touch($path, ++$stamp);
		rejects(fn() => $project->run(), 'value');
		rejects(fn() => $project->run(), 'value');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'value');
		$write('float', '8.0');
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Deletion recovery differs from fresh');
		file_put_contents($directory . '/main.' . $flavor->name,
			'if ((16.0 / value()) == 2.0) { echo "Y"; } return 8;');
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
echo "Mixed numeric families: grouped operators, single-pass preparation and incremental/fresh recovery passed\n";
