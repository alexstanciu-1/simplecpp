<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

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
	$decl = static fn(string $type, string $value): string => match ($flavor) {
		flavor::php => '$x ' . $type . '=' . $value . ';',
		flavor::cpp => $type . ' x=' . $value . ';',
		flavor::js => 'let x: ' . $type . '=' . $value . ';',
	};
	$x = $var('x');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('integer_compounds', $code, $flavor), new entry_target(31));
	verify(\scpp\compiler\type_registry::resolve('byte') === \scpp\compiler\type_registry::resolve('uint8'), 'Aliases do not share identity');
	verify(\scpp\compiler\type_registry::resolve('byte')->name === 'uint8', 'Wrong canonical name');
	$ref = match ($flavor) {
		flavor::php => 'byte &$v', flavor::cpp => 'byte &v', flavor::js => '&v: byte',
	};
	$params = $ref . match ($flavor) {
		flavor::php => ', int $rhs', flavor::cpp => ', int rhs', flavor::js => ', rhs: int',
	};
	$v = $var('v');
	$entry = $prepare($decl('byte', '8') . $x . '+=2; return 8;');
	verify($entry->body()->expression_visits === 3, 'Compound preparation revisited its RHS');
	$write = $entry->body()->statements[1];
	verify($write->execution_type() === \scpp\compiler\type_int64::instance(), 'Wrong execution identity');
	verify($write->left_conversion() === \scpp\compiler\value_conversion::integer_widen
		&& $write->conversion() === \scpp\compiler\value_conversion::identity
		&& $write->write_back() === \scpp\compiler\value_conversion::integer_checked, 'Wrong compound conversions');
	verify(!$write->constant_known(), 'Compound result incorrectly folded');
	$view = \scpp\compiler\Compilation_Inspection::body($entry->body())['statements'][1];
	verify($view['execution_type'] === 'int64' && $view['write_back'] === 'integer_checked', 'Inspection lost compound decisions');
	$beforeReset = (new Cpp_Emitter())->emit($entry);
	$write->reset_preparation();
	foreach ([fn() => $write->execution_type(), fn() => $write->left_conversion(), fn() => $write->write_back()] as $read) {
		$failed = false;
		try {
			$read();
		}
		catch (LogicException) {
			$failed = true;
		}
		verify($failed, 'Reset retained compound facts');
	}
	$write->prepare_local_compound($entry->body()->statements[0]);
	verify((new Cpp_Emitter())->emit($entry) === $beforeReset, 'Reset/reprepare changed output');
	$code = '';
	$expected = '';
	foreach (['byte', 'int'] as $destination) {
		foreach (['(byte)', ''] as $rhsCast) {
			$case = 'case_' . $destination . ($rhsCast === '' ? '_int' : '_byte');
			$body = $decl($destination, '8');
			foreach ([['+=', '2', 10], ['-=', '3', 7], ['*=', '4', 28], ['/=', '3', 9], ['%=', '4', 1]] as [$op, $rhs, $result]) {
				$body .= $x . $op . $rhsCast . $rhs . '; if (' . $x . '==' . $result . ') { echo "Y"; }';
				$expected .= 'Y';
			}
			$code .= $fn($case, '', 'void', $body . 'return;') . $case . '();';
		}
	}
	$code .= $decl('byte', '250') . $x . '+=(byte)10; if (' . $x . '==4) { echo "W"; }'
		. $x . '=(byte)0;' . $x . '-=(byte)1; if (' . $x . '==255) { echo "W"; }'
		. $x . '=(byte)128;' . $x . '*=(byte)2; if (' . $x . '==0) { echo "W"; }'
		. $x . '=(byte)8;' . $x . '+=' . $x . '; if (' . $x . '==16) { echo "S"; }';
	$expected .= 'WWWS';
	foreach ([['+=', 1, 0, 1], ['-=', 1, 2, 1], ['*=', 2, 1, 2], ['/=', 2, 4, 2], ['%=', 3, 5, 2]] as [$op, $rhs, $initial, $result]) {
		$code .= $x . '=(byte)' . $initial . '; for (' . $x . $op . $rhs . '; false; ' . $x . '=' . $x . ') {}'
			. 'if (' . $x . '==' . $result . ') { echo "H"; }'
			. $x . '=(byte)' . $initial . '; for (' . $x . '=' . $x . '; ' . $x . '==' . $initial . '; ' . $x . $op . $rhs . ') {}'
			. 'if (' . $x . '==' . $result . ') { echo "H"; }';
		$expected .= 'HH';
	}
	$code .= $fn('change', $ref, 'int', $v . '=(byte)100; return 3;')
		. $fn('bump', $ref, 'void', $v . '+=2; return;')
		. $x . '=(byte)10;' . $x . '+=change(' . $x . '); bump(' . $x . ');'
		. 'if (' . $x . '==15) { echo "R"; } return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . 'R')];
	foreach (['/=' => 'division_by_zero', '%=' => 'modulo_by_zero'] as $operator => $message) {
		$failure = $prepare($decl('byte', '8') . $x . $operator . '(byte)0; return 8;');
		$fixtures[$flavor->name . '_same_zero_' . count($fixtures)] = ['cpp' => (new Cpp_Emitter())->emit($failure),
			'stdout_hex' => '', 'catch_domain_error' => $message];
	}

	foreach (['true', 'null', '"1"', '1.0'] as $other) {
		rejects(fn() => $prepare($decl('byte', '1') . $x . '+=' . $other . '; return 8;'), 'Unsupported compound');
	}
	rejects(fn() => $prepare($decl('byte', '1') . $x . '=1; return 8;'), 'implicit conversion');
	$source = '';
	foreach (['add' => '+=', 'subtract' => '-=', 'multiply' => '*=', 'divide' => '/=', 'remainder' => '%='] as $name => $op) {
		$source .= $fn($name, $params, 'void', $v . $op . $var('rhs') . '; return;');
	}
	$source .= $fn('mutate', $ref, 'int', $v . '+=(byte)1; return 300;')
		. $fn('mutate_then_fail', $ref, 'void', $v . '+=mutate(' . $v . '); return;')
		. $fn('rhs_failure', $ref, 'void', $v . '+=mutate(' . $v . ')/0; return;')
		. $fn('header_init', $params, 'void', 'for (' . $v . '+=' . $var('rhs') . '; false; ' . $v . '=' . $v . ') {} return;')
		. $fn('header_update', $params, 'void', 'for (' . $v . '=' . $v . '; ' . $v . '<256; ' . $v . '+=' . $var('rhs') . ') {} return;');
	$observer = file_get_contents(__DIR__ . '/integer_compounds_observer.cpp');
	foreach (['add', 'subtract', 'multiply', 'divide', 'remainder', 'mutate_then_fail', 'rhs_failure', 'header_init', 'header_update'] as $name) {
		$observer = str_replace('@' . $name . '@', '::App::' . $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$directory = sys_get_temp_dir() . '/scpp-integer-compounds-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, $decl('byte', '2') . $x . '*=value(); if (' . $x . '==16) { echo "Y"; } return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $expression) use ($path, $fn, &$stamp): void {
			file_put_contents($path, $fn('value', '', $type, 'return ' . $expression . ';'));
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('byte', '8');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['byte', '(byte)8'], ['int', '8'], ['uint8', '8'], ['bool', 'true'], ['byte', '8'], ['uint8', '256'], ['uint8', '8']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '256')) {
				$message = $type === 'bool' ? 'Unsupported compound' : 'outside uint8';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] >= 1 && $result['preparation']['prepared'] <= 2, 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
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
		$write('byte', '8');
		$project->run();
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Deletion recovery differs from fresh');
		file_put_contents($directory . '/main.' . $flavor->name,
			$decl('byte', '2') . $x . '+=value(); if (' . $x . '==10) { echo "Y"; } return 8;');
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
echo "Integer compounds: checked write-back, same-type arithmetic and incremental/fresh recovery passed\n";
