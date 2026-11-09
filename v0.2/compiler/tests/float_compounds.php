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
	$param = match ($flavor) {
		flavor::php => 'float64 $v',
		flavor::cpp => 'float64 v',
		flavor::js => 'v: float64',
	};
	$ref = match ($flavor) {
		flavor::php => 'float &$v',
		flavor::cpp => 'float &v',
		flavor::js => '&v: float',
	};
	$x = $var('x');
	$v = $var('v');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('float_compounds', $code, $flavor), new entry_target(31));
	$entry = $prepare($decl('8.0') . $x . '+=2.0; return 8;');
	verify($entry->body()->expression_visits === 3, 'Compound RHS must be prepared once without a synthetic binary');
	$write = $entry->body()->statements[1];
	verify(!$write->constant_known(), 'Compound write retained a stale constant');
	verify($write->local_def() === $entry->body()->statements[0], 'Lost destination binding');
	verify(!str_contains((new Cpp_Emitter())->emit($entry), 'scpp/arithmetic.hpp'), 'Addition requires no runtime helper');
	$code = $decl('8.0');
	foreach (['+=2.0', '-=4.0', '*=3.0', '/=2.0'] as $update) {
		$code .= $x . $update . ';';
	}
	$code .= 'if (' . $x . '==9.0) { echo "A"; }'
		. $fn('change', $ref, 'float', $v . '=100.0; return 3.0;')
		. $fn('bump', $ref, 'void', $v . '+=2.0; return;')
		. $fn('copy', $param, 'float', $v . '*=2.0; return ' . $v . ';')
		. $x . '=10.0;' . $x . '+=change(' . $x . ');'
		. 'if (' . $x . '==13.0) { echo "B"; } bump(' . $x . ');'
		. 'if (copy(' . $x . ')==30.0 && ' . $x . '==15.0) { echo "C"; }'
		. $x . '+=' . $x . '; if (' . $x . '==30.0) { echo "D"; }'
		. $x . '=0.0; for (' . $x . '+=1.0;' . $x . '<4.0;' . $x . '+=1.0) { echo "F"; }'
		. 'for (' . $x . '/=2.0;' . $x . '>0.5;' . $x . '/=2.0) { echo "H"; }'
		. 'return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	verify(str_contains($cpp, 'scpp::float64_divide('), 'Division bypassed checked helper');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('ABCDFFFHH')];
	foreach (['-=true', '*="text"', '/=null', '%=2.0'] as $invalid) {
		rejects(fn() => $prepare($decl('1.0') . $x . $invalid . '; return 8;'), 'Unsupported compound');
	}
	$source = '';
	foreach (['add' => '+=', 'subtract' => '-=', 'multiply' => '*=', 'divide' => '/='] as $name => $op) {
		$params = match ($flavor) {
			flavor::php => 'float &$v, float64 $rhs',
			flavor::cpp => 'float &v, float64 rhs',
			flavor::js => '&v: float, rhs: float64',
		};
		$source .= $fn($name, $params, 'void', $v . $op . $var('rhs') . '; return;');
	}
	$source .= $fn('zero', $ref, 'float', $v . '=100.0; return -0.0;')
		. $fn('mutate_then_fail', $ref, 'void', $v . '/=zero(' . $v . '); return;')
		. $fn('rhs_failure', $ref, 'void', $v . '+=zero(' . $v . ')/0.0; return;');
	$observer = file_get_contents(__DIR__ . '/float_compounds_observer.cpp');
	foreach (['add', 'subtract', 'multiply', 'divide', 'mutate_then_fail', 'rhs_failure'] as $name) {
		$observer = str_replace('@' . $name . '@', $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$directory = sys_get_temp_dir() . '/scpp-float-arithmetic-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, $decl('2.0') . $x . '*=value(); if (' . $x . '==16.0) { echo "Y"; } return 8;');
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
				$fixtures[$flavor->name . '_recovery'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		}
		file_put_contents($directory . '/main.' . $flavor->name,
			$decl('2.0') . $x . '+=value(); if (' . $x . '==10.0) { echo "Y"; } return 8;');
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
verify(!type_float64::instance()->cpp_binary_reorderable(binary_operator::divide), 'Checked division classified as reorderable');
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Float compounds: shared write path, single-pass preparation and incremental/fresh recovery passed\n";
