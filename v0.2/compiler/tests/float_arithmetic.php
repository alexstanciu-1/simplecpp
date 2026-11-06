<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_float64, binary_operator};

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
	$params = match ($flavor) {
		flavor::php => 'float $a, float64 $b',
		flavor::cpp => 'float a, float64 b',
		flavor::js => 'a: float, b: float64',
	};
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('float_arithmetic', $code, $flavor), new entry_target(31));
	$entry = $prepare($decl('2.0 + 3.0 * 4.0') . 'return 8;');
	verify($entry->body()->expression_visits === 6, 'Each arithmetic operand must be prepared exactly once');
	$local = array_values($entry->body()->locals)[0];
	verify(!$local->initializer->constant_known(), 'Arithmetic was folded during preparation');
	verify(!str_contains((new Cpp_Emitter())->emit($entry), 'scpp/arithmetic.hpp'), 'Native operators need no helper include');
	$code = '';
	foreach (['add' => '+', 'subtract' => '-', 'multiply' => '*', 'divide' => '/'] as $name => $op) {
		$code .= $fn($name, $params, 'float64', 'return ' . $var('a') . $op . $var('b') . ';');
	}
	foreach (['positive' => '+', 'negative' => '-'] as $name => $op) {
		$code .= $fn($name, $params, 'float', 'return ' . $op . '(' . $var('a') . ');');
	}
	$entry = $prepare($code . 'return 8;');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	verify(str_contains($cpp, 'scpp::float64_divide('), 'Division bypassed checked helper');
	$observer = file_get_contents(__DIR__ . '/float_arithmetic_observer.cpp');
	foreach (['add', 'subtract', 'multiply', 'divide', 'positive', 'negative'] as $name) {
		$observer = str_replace('@' . $name . '@', 'scpp_function_' . bin2hex($name), $observer);
	}
	$fixtures[$flavor->name] = ['cpp' => str_replace('int main()', 'int tested_entry()', $cpp) . $observer, 'stdout_hex' => ''];
	foreach (['1.0 % 2.0', 'true * 1.0'] as $invalid) {
		rejects(fn() => $prepare($decl($invalid) . 'return 8;'), 'Unsupported binary');
	}
	foreach (['+=', '-=', '*=', '/='] as $op) {
		rejects(fn() => $prepare($decl('1.0') . $var('x') . $op . '2; return 8;'), 'Unsupported compound');
	}
	rejects(fn() => $prepare($decl('1e309') . 'return 8;'), 'finite');
	$directory = sys_get_temp_dir() . '/scpp-float-arithmetic-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'if (-value() + 2.0 == -6.0) { echo "Y"; } return 8;');
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
		foreach ([['float64', '16.0 / 2.0'], ['int', '8'], ['bool', 'true'], ['float', '4.0 * 2.0'],
			['float', '1e309'], ['float64', '+(10.0) - 2.0']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '1e309')) {
				$message = $type === 'bool' ? 'Unsupported unary' : 'finite';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$project->run();
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Incremental differs from fresh output');
				$fixtures[$flavor->name . '_recovery'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		}
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	}
	finally {
		remove_tree($directory);
	}
}
verify(!type_float64::instance()->cpp_binary_reorderable(binary_operator::divide), 'Checked division classified as reorderable');
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Float arithmetic: single-pass preparation, generated admission, purity and incremental/fresh recovery passed\n";
