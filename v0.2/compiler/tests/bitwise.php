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
	$a = $var('a');
	$b = $var('b');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('bitwise', $code, $flavor), new entry_target(31));
	$operations = ['and' => '&', 'or' => '|', 'xor' => '^', 'left' => '<<', 'right' => '>>'];
	$source = '';
	$names = [];
	foreach ($operations as $name => $operator) {
		foreach (['bb' => ['byte', 'uint8'], 'bi' => ['byte', 'int'], 'ib' => ['int', 'byte'], 'ii' => ['int', 'int']] as $order => [$left, $right]) {
			$result = in_array($operator, ['<<', '>>'], true) ? $left : ($order === 'bb' ? 'byte' : 'int');
			$function = $name . '_' . $order;
			$source .= $fn($function, $param($left, 'a') . ',' . $param($right, 'b'), $result, 'return ' . $a . $operator . $b . ';');
			$names[] = $function;
			$expression = ($left === 'byte' ? '(byte)8' : '8') . $operator . ($right === 'uint8' || $right === 'byte' ? '(byte)1' : '1');
			$entry = $prepare('return (int)(' . $expression . ');');
			$binary = $entry->body()->statements[0]->value->operand;
			verify($binary->type_def()->name === ($result === 'byte' ? 'uint8' : 'int64'), 'Wrong binary result');
			verify(!$binary->constant_known(), 'Bitwise introduced folding');
		}
		foreach (['byte', 'int'] as $left) {
			$ref = match ($flavor) {
				flavor::php => $left . ' &$a', flavor::cpp => $left . ' &a', flavor::js => '&a: ' . $left,
			};
			$function = $name . '_write_' . $left;
			$source .= $fn($function, $ref . ',' . $param('int', 'b'), 'void', $a . $operator . '=' . $b . '; return;');
			$names[] = $function;
		}
	}
	foreach (['byte', 'int'] as $type) {
		$name = 'not_' . $type;
		$source .= $fn($name, $param($type, 'a'), $type, 'return ~' . $a . ';');
		$names[] = $name;
	}
	$ref = match ($flavor) {
		flavor::php => 'byte &$a', flavor::cpp => 'byte &a', flavor::js => '&a: byte',
	};
	$source .= $fn('bad_count', $ref, 'int', $a . '=(byte)7; return 8;')
		. $fn('failed_write', $ref, 'void', $a . '<<=bad_count(' . $a . '); return;');
	$names[] = 'failed_write';
	$observer = file_get_contents(__DIR__ . '/bitwise_observer.cpp');
	foreach ($names as $name) $observer = str_replace('@' . $name . '@', '::App::scpp_function_' . bin2hex($name), $observer);
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()', (new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];
	$code = $decl('byte', '128') . $x . '<<=1; if (!' . $x . ') { echo "A"; }'
		. $x . '|=3;' . $x . '^=1;' . $x . '&=6;' . $x . '>>=1; if (' . $x . '==1) { echo "B"; }'
		. 'if ((~(byte)0)+1==256 && (byte)~(-256)==255 && (1<<2+1)==8 && (5|2^3&6)==5) { echo "C"; }'
		. 'if ((8>>1>>1)==2 && (-3>>1)==-2 && (1<<63)==(-9223372036854775807-1)) { echo "D"; }'
		. 'for (' . $x . '=(byte)1;' . $x . '<8;' . $x . '<<=1) { echo "F"; } return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('ABCDFFF')];
	$fixtures[$flavor->name . '_checked_cast'] = ['cpp' => (new Cpp_Emitter())->emit($prepare('return (int)((byte)~0);')), 'stdout_hex' => '', 'catch_out_of_range' => true];
	$entry = $prepare('return (int)((byte)1<<2);');
	verify($entry->body()->expression_visits === 5, 'Shift operand revisited');
	$binary = $entry->body()->statements[0]->value->operand;
	verify($binary->right_conversion === \scpp\compiler\value_conversion::identity, 'Count narrowed to left identity');
	$before = (new Cpp_Emitter())->emit($entry);
	$binary->reset_preparation();
	$binary->left->type_def()->prepare_binary($binary);
	verify((new Cpp_Emitter())->emit($entry) === $before, 'Reset/reprepare changed output');
	$entry = $prepare($decl('byte', '1') . $x . '<<=1; return 8;');
	verify($entry->body()->expression_visits === 3, 'Compound shift synthesized binary traversal');
	foreach (['1 & true', '1 | 1.0', '1 ^ "1"', '1 << true', '1 >> 1.0', '1.0 << 1', '1 & 1==1'] as $invalid) {
		rejects(fn() => $prepare('if (' . $invalid . ') {} return 8;'), 'Unsupported binary');
	}
	foreach (['~true', '~1.0', '~"1"', '~null'] as $invalid) rejects(fn() => $prepare('return (int)(' . $invalid . ');'), 'Unsupported unary');
	foreach (['1 < < 2', '8 > > 1'] as $invalid) rejects(fn() => $prepare('return ' . $invalid . ';'), 'Expected');
	$directory = sys_get_temp_dir() . '/scpp-bitwise-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		$caller_source = 'if ((value() & 15)==8 && (value()<<1)==16 && ~(byte)0==255) { echo "Y"; } return 8;';
		file_put_contents($directory . '/main.' . $flavor->name, $caller_source);
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
		foreach ([['byte', '4 * 2'], ['int', '8'], ['bool', 'true'], ['uint8', '8'], ['byte', '256'], ['int64', '8']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if (($type === 'bool') || ($expression === '256')) {
				$message = $type === 'bool' ? 'Unsupported binary' : 'outside uint8';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($expression === '4 * 2' ? 1 : 2), 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
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
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Deletion recovery differs from fresh');
		file_put_contents($directory . '/main.' . $flavor->name,
			'if ((value()>>1)==4 && (value()^1)==9) { echo "Y"; } return 8;');
		touch($directory . '/main.' . $flavor->name, ++$stamp);
		verify($project->run()['preparation']['prepared'] === 1, 'Operator edit prepared unchanged provider');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Operator edit differs from fresh output');
		$fixtures[$flavor->name . '_operator_edit'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('Y')];
		$main_path = $directory . '/main.' . $flavor->name;
		$valid_source = file_get_contents($main_path);
		file_put_contents($main_path, str_replace('>>', '> >', $valid_source));
		touch($main_path, ++$stamp);
		rejects(fn() => $project->run(), 'Expected');
		rejects(fn() => $project->run(), 'Expected');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'Expected');
		file_put_contents($main_path, $valid_source);
		touch($main_path, ++$stamp);
		$project->run();
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Spaced shift recovery differs from fresh');
		verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
	}
	finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Integer bitwise/shift groups: single-pass preparation and incremental/fresh recovery passed\n";
