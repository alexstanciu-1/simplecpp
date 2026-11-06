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
	$x = $var('x');
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('string_equality', $code, $flavor), new entry_target(31));
	$entry = $prepare('return (int)("a" == "b");');
	verify($entry->body()->expression_visits === 4, 'String equality revisited operands');
	$binary = $entry->body()->statements[0]->value->operand;
	verify($binary->type_def() === \scpp\compiler\type_bool::instance(), 'Wrong result type');
	verify(!$binary->constant_known(), 'String comparison introduced folding');
	$before_reset = (new Cpp_Emitter())->emit($entry);
	$binary->reset_preparation();
	$binary->left->type_def()->prepare_binary($binary);
	verify((new Cpp_Emitter())->emit($entry) === $before_reset, 'Reset/reprepare changed output');

	$code = '';
	$expected = '';
	foreach (['"" == ""' => true, '"10" == "010"' => false, '"Alex" != "alex"' => true,
		'"a\0b" == "a\0c"' => false, '"a" == "a\0"' => false, '"a\0b" == "a\0b"' => true,
		'"é" == "é"' => false] as $expression => $answer) {
		$code .= 'if (' . $expression . ') { echo "T"; } else { echo "F"; }';
		$expected .= $answer ? 'T' : 'F';
	}
	$param = match ($flavor) {
		flavor::php => 'string $v', flavor::cpp => 'string v', flavor::js => 'v: string',
	};
	$ref = match ($flavor) {
		flavor::php => 'string &$v', flavor::cpp => 'string &v', flavor::js => '&v: string',
	};
	$declString = match ($flavor) {
		flavor::php => '$x string="old";', flavor::cpp => 'string x="old";', flavor::js => 'let x: string="old";',
	};
	$code .= $fn('copy', $param, 'string', 'return ' . $var('v') . ';')
		. $fn('change', $ref, 'string', $var('v') . '="new"; echo "R"; return "old";')
		. $declString . 'if (' . $x . '==copy(' . $x . ')) { echo "C"; }'
		. 'if (' . $x . '==change(' . $x . ')) { echo "S"; }'
		. 'if (' . $x . '=="new") { echo "N"; }';
	$expected .= 'CRSN';
	$entry = $prepare($code . 'return 8;');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected)];
	foreach (['"a" < "b"', '"1" == 1', 'true != "1"', 'null == ""', '(byte)1 == "a"'] as $invalid) {
		rejects(fn() => $prepare('if (' . $invalid . ') {} return 8;'), 'Unsupported binary');
	}
	$source = '';
	foreach (['equal' => '==', 'not_equal' => '!='] as $name => $op) {
		$params = match ($flavor) {
			flavor::php => 'string $a, string $b', flavor::cpp => 'string a, string b', flavor::js => 'a: string, b: string',
		};
		$source .= $fn($name, $params, 'bool', 'return ' . $var('a') . $op . $var('b') . ';');
	}
	$observer = file_get_contents(__DIR__ . '/string_equality_observer.cpp');
	foreach (['equal', 'not_equal'] as $name) {
		$observer = str_replace('@' . $name . '@', 'scpp_function_' . bin2hex($name), $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];

	$directory = sys_get_temp_dir() . '/scpp-string-equality-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'if (value() == "ok") { echo "Y"; } return 8;');
		$path = $directory . '/provider.' . $flavor->name;
		$stamp = time();
		$write = static function (string $type, string $expression) use ($path, $fn, &$stamp): void {
			file_put_contents($path, $fn('value', '', $type, 'return ' . $expression . ';'));
			touch($path, ++$stamp);
		};
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$write('string', '"ok"');
		$project->run();
		$caller = $project->session()->entry->entry->ast;
		foreach ([['string', '("ok")'], ['int', '8'], ['string', '"ok"']] as [$type, $expression]) {
			$write($type, $expression);
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			if ($type === 'int') {
				$message = 'Unsupported binary';
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $project->run(), $message);
				rejects(fn() => $fresh->run(), $message);
			}
			else {
				$result = $project->run();
				verify($result['preparation']['prepared'] === ($expression !== '"ok"' ? 1 : 2), 'Unexpected preparation work for ' . $expression . ': ' . json_encode($result['preparation']));
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
		$write('string', '"ok"');
		$project->run();
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Deletion recovery differs from fresh');
		file_put_contents($directory . '/main.' . $flavor->name,
			'if ("bad" != value()) { echo "Y"; } return 8;');
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
echo "String equality: byte content, single-pass preparation and incremental/fresh recovery passed\n";
