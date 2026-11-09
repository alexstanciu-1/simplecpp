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
foreach (flavor::cases() as $flavor) {
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $params, string $type, string $body): string =>
		($flavor === flavor::cpp ? "$type $name($params)" : "function $name($params): $type") . '{' . $body . '}';
	$decl = static fn(string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . ' int=' . $value . ';',
		flavor::cpp => 'int ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . ': int=' . $value . ';',
	};
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('power', $code, $flavor), new entry_target(31));
	$code = '';
	$expected = '';
	foreach (['0 ** 0' => '1', '2 ** 3 ** 2' => '512', '-2 ** 2' => '-4',
		'(-2) ** 2' => '4', '2 * 3 ** 2' => '18', '~2 ** 2' => '-5',
		'(-2) ** 63' => '-9223372036854775808', '9223372036854775807 ** 1' => '9223372036854775807'] as $expression => $answer) {
		$code .= 'echo (' . $expression . '), ",";';
		$expected .= $answer . ',';
	}
	$code .= $decl('x', '2') . $var('x') . '**=' . $var('x') . '; echo ' . $var('x') . ';'
		. 'for (' . $decl('i', '0') . $var('i') . '<2;' . $var('x') . '**=2) {' . $var('i') . '++;}'
		. 'echo ' . $var('x') . '; return 8;';
	$expected .= '4256';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Power lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected)];
	$entry = $prepare('return 2 ** 3;');
	verify($entry->body()->expression_visits === 3, 'Power revisited operands');
	$binary = $entry->body()->statements[0]->value;
	verify(!$binary->constant_known(), 'Power introduced folding');
	$before = (new Cpp_Emitter())->emit($entry);
	$binary->reset_preparation();
	$binary->left->type_def()->prepare_binary($binary);
	verify((new Cpp_Emitter())->emit($entry) === $before, 'Power reset/reprepare differs');
	foreach (['((byte)2) ** 3', '2 ** (byte)3', '2.0 ** 3', 'true ** 2', '"2" ** 3', 'null ** 0'] as $invalid) {
		rejects(fn() => $prepare('echo (' . $invalid . '); return 8;'), 'Unsupported binary');
	}
	$params = match ($flavor) {
		flavor::php => 'int $a, int $b', flavor::cpp => 'int a, int b', flavor::js => 'a: int, b: int',
	};
	$ref = match ($flavor) {
		flavor::php => 'int &$a, int $b', flavor::cpp => 'int &a, int b', flavor::js => '&a: int, b: int',
	};
	$one_ref = match ($flavor) {
		flavor::php => 'int &$a', flavor::cpp => 'int &a', flavor::js => '&a: int',
	};
	$source = $fn('power_value', $params, 'int', 'return ' . $var('a') . '**' . $var('b') . ';')
		. $fn('power_write', $ref, 'void', $var('a') . '**=' . $var('b') . ';')
		. $fn('power_rhs', $one_ref, 'int', $var('a') . '=9; return 63;')
		. $fn('power_effect', $one_ref, 'void', $var('a') . '**=power_rhs(' . $var('a') . ');')
		. 'return 8;';
	$observer = file_get_contents(__DIR__ . '/power_observer.cpp');
	foreach (['power_value', 'power_write', 'power_effect'] as $name) {
		$observer = str_replace('@' . $name . '@', $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source))) . $observer, 'stdout_hex' => ''];
	$directory = sys_get_temp_dir() . '/scpp-power-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'echo (value() ** 3); return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $expression) use ($path, $fn, &$stamp): void {
			file_put_contents($path, $fn('value', '', $type, 'return ' . $expression . ';'));
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('int', '2');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['int', '3'], ['byte', '3'], ['int', '2']] as [$type, $literal]) {
			$write($type, $literal);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($type === 'byte') {
				rejects(fn() => $project->run(), 'Unsupported binary');
				rejects(fn() => $project->run(), 'Unsupported binary');
				rejects(fn() => $fresh->run(), 'Unsupported binary');
			} else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($literal === '3' ? 1 : 2), 'Unexpected power work');
				$fresh->run();
				verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Power recovery differs');
				$fixtures[$flavor->name . '_recovery_' . $literal] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex($literal === '3' ? '27' : '8')];
			}
			verify($project->session()->entry->entry->ast === $caller, 'Power caller AST replaced');
		}
		file_put_contents($path, '');
		touch($path, ++$stamp);
		rejects(fn() => $project->run(), 'value');
		rejects(fn() => $project->run(), 'value');
		$write('int', '2');
		$project->run();
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Power deletion recovery differs');
		verify($project->run()['preparation']['prepared'] === 0, 'Power no-op prepared bodies');
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "
");
}
echo "Integer power: precedence, exact results, compound writes and incremental recovery passed
";
