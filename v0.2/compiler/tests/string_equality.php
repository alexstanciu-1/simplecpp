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
	foreach (['null == null' => true, 'null != null' => false, '"" == ""' => true, '"10" == "010"' => false, '"Alex" != "alex"' => true,
		'"a\0b" == "a\0c"' => false, '"a" == "a\0"' => false, '"a\0b" == "a\0b"' => true,
		'"é" == "é"' => false, '"10" < "2"' => true, '"" < "a"' => true,
		'"a\0" > "a"' => true, '"a\0b" < "a\0c"' => true, '"same" <= "same"' => true,
		'"same" >= "same"' => true, '"z" <= "a"' => false, '"a" >= "z"' => false] as $expression => $answer) {
		$code .= 'if (' . $expression . ') { echo "T"; } else { echo "F"; }';
		$expected .= $answer ? 'T' : 'F';
	}
	// All canonical scalar pairs: aliases share identity, numeric representations do not.
	$identities = ['null', 'true', '1', '(byte)1', '1.0', '"1"'];
	foreach ($identities as $i => $left) {
		foreach ($identities as $j => $right) {
			$code .= 'if (' . $left . ' === ' . $right . ') { echo "T"; } else { echo "F"; }'
				. 'if (' . $left . ' !== ' . $right . ') { echo "T"; } else { echo "F"; }';
			$expected .= $i === $j ? 'TF' : 'FT';
		}
	}
	foreach (['(byte)1 === (uint8)1', '"a\0b" === "a\0b"', '"a\0b" !== "a\0c"', '-0.0 === 0.0'] as $test) {
		$code .= 'if (' . $test . ') { echo "I"; }';
		$expected .= 'I';
	}
	$identity_entry = $prepare('return (int)(1 === 1.0);');
	verify($identity_entry->body()->expression_visits === 4, 'Strict identity revisited operands');
	verify(!$identity_entry->body()->statements[0]->value->operand->constant_known(), 'Identity introduced folding');
	foreach (['(1 <=> 2) == -1', '(2 <=> 1) == 1', '(1 <=> 1) == 0',
		'((byte)255 <=> -1) == 1', '(-1 <=> (byte)255) == -1', '((byte)1 <=> (byte)1) == 0',
		'((byte)1 <=> 1.5) == -1', '(1.5 <=> (byte)1) == 1', '(-0.0 <=> 0.0) == 0',
		'(9007199254740993 <=> 9007199254740992.0) == 0',
		'(9007199254740993 <=> 9007199254740992) == 1',
		'("a\0b" <=> "a\0c") == -1', '("a" <=> "a\0") == -1', '("10" <=> "2") == -1'] as $test) {
		$code .= 'if (' . $test . ') { echo "W"; }';
		$expected .= 'W';
	}
	foreach (['true <=> false', 'null <=> null', '"1" <=> 1', '1 <=> true'] as $invalid) {
		rejects(fn() => $prepare('echo (' . $invalid . '); return 8;'), 'Unsupported binary');
	}
	$three_way = $prepare('return (1 <=> (byte)2);');
	verify($three_way->body()->expression_visits === 4, 'Three-way revisited operands');
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
	$code .= $fn('null_left', '', 'null', 'echo "L"; return null;')
		. $fn('null_right', '', 'null', 'echo "R"; return null;')
		. 'if (null_left() == null_right()) { echo "N"; }';
	$expected .= 'LRN';
	$code .= 'if (null_left() !== copy("text")) { echo "D"; }'
		. 'if (copy("text") !== null_right()) { echo "D"; }';
	$expected .= 'LDRD';

	$concat = $flavor === flavor::php ? '.' : '+';
	$code .= 'echo ("a\0" ' . $concat . ' "b"), ("" ' . $concat . ' "c");'
		. $x . ' ' . $concat . '= " tail"; echo ' . $x . ';'
		. 'echo (copy("L") ' . $concat . ' copy("R"));'
		. 'echo (' . $x . ' ' . $concat . ' change(' . $x . '));';
	$expected .= "a\0bcnew tailLRRnew tailold";
	$code .= $fn('append', $ref, 'void', $var('v') . ' ' . $concat . '= "\0Z";')
		. 'append(' . $x . '); echo ' . $x . ';'
		. 'for (' . $x . '=' . $x . '; false; ' . $x . ' ' . $concat . '= "unreached") {}'
		. $x . ' ' . $concat . '= ' . $x . '; echo ' . $x . ';';
	$expected .= "new\0Znew\0Znew\0Z";

	$concat_entry = $prepare('echo ("a" ' . $concat . ' "b"); return 8;');
	verify($concat_entry->body()->expression_visits === 4, 'Concatenation revisited operands');
	foreach (['"a" ' . $concat . ' 1', 'true ' . $concat . ' "a"', '"a" ' . $concat . ' null'] as $invalid) {
		rejects(fn() => $prepare('echo (' . $invalid . '); return 8;'), 'Unsupported binary');
	}
	if ($flavor === flavor::php) {
		rejects(fn() => $prepare('echo ("a" + "b"); return 8;'), 'Unsupported binary');
		rejects(fn() => $prepare($declString . $x . ' += "b"; return 8;'), 'Unsupported compound');
	}

	$entry = $prepare($code . 'return 8;');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected)];
	foreach (['"a" < 1', '"1" == 1', 'true != "1"', 'null == ""', 'null == false', 'null != 0', '0.0 == null', '(byte)0 == null', '(byte)1 == "a"'] as $invalid) {
		rejects(fn() => $prepare('if (' . $invalid . ') {} return 8;'), 'Unsupported binary');
	}
	$source = '';
	foreach (['equal' => '==', 'not_equal' => '!=', 'less' => '<', 'less_equal' => '<=', 'greater' => '>', 'greater_equal' => '>=', 'compare' => '<=>'] as $name => $op) {
		$params = match ($flavor) {
			flavor::php => 'string $a, string $b', flavor::cpp => 'string a, string b', flavor::js => 'a: string, b: string',
		};
		$source .= $fn($name, $params, $name === 'compare' ? 'int' : 'bool', 'return ' . $var('a') . $op . $var('b') . ';');
	}
	$observer = file_get_contents(__DIR__ . '/string_equality_observer.cpp');
	foreach (['equal', 'not_equal', 'less', 'less_equal', 'greater', 'greater_equal', 'compare'] as $name) {
		$observer = str_replace('@' . $name . '@', '::App::' . $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_boundary'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($source . 'return 8;'))) . $observer, 'stdout_hex' => ''];

	$float_params = match ($flavor) {
		flavor::php => 'float $a, float $b', flavor::cpp => 'float a, float b', flavor::js => 'a: float, b: float',
	};
	$float_code = $fn('same_float', $float_params, 'bool', 'return ' . $var('a') . '===' . $var('b') . ';')
		. $fn('different_float', $float_params, 'bool', 'return ' . $var('a') . '!==' . $var('b') . ';')
		. $fn('compare_float', $float_params, 'int', 'return ' . $var('a') . '<=>' . $var('b') . ';')
		. $fn('identity_fail_right', '', 'bool', 'return null === (1 / 0);')
		. $fn('identity_fail_left', '', 'bool', 'return (1 / 0) === null_effect();')
		. $fn('null_effect', '', 'null', 'echo "UNREACHED"; return null;') . 'return 8;';
	$observer = file_get_contents(__DIR__ . '/identity_observer.cpp');
	foreach (['same_float', 'different_float', 'identity_fail_right', 'identity_fail_left', 'compare_float'] as $name) {
		$observer = str_replace('@' . $name . '@', '::App::' . $name . '_', $observer);
	}
	$fixtures[$flavor->name . '_identity_float'] = ['cpp' => str_replace('int main()', 'int tested_entry()',
		(new Cpp_Emitter())->emit($prepare($float_code))) . $observer, 'stdout_hex' => ''];

	$directory = sys_get_temp_dir() . '/scpp-string-equality-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', json_encode(['sources' => ['main.' . $flavor->name, 'provider.' . $flavor->name]], JSON_THROW_ON_ERROR));
		file_put_contents($directory . '/main.' . $flavor->name, 'if ((value() ' . $concat . ' "") >= "ok" && value() < "z" && (value() <=> "ok") == 0) { echo ("" ' . $concat . ' "Y"); } return 8;');
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
		file_put_contents($directory . '/main.' . $flavor->name, 'if (value() == null) { echo "N"; } return 8;');
		touch($directory . '/main.' . $flavor->name, ++$stamp);
		$write('null', 'null');
		$project->run();
		$null_caller = $project->session()->entry->entry->ast;
		$write('int', '0');
		rejects(fn() => $project->run(), 'Unsupported binary');
		rejects(fn() => $project->run(), 'Unsupported binary');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'Unsupported binary');
		$write('null', 'null');
		$project->run();
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Null equality recovery differs');
		verify($project->session()->entry->entry->ast === $null_caller, 'Null caller AST replaced');
		$fixtures[$flavor->name . '_null_recovery'] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex('N')];
		verify($project->run()['preparation']['prepared'] === 0, 'Null no-op prepared bodies');
		file_put_contents($directory . '/main.' . $flavor->name, 'if (value() === (byte)1) { echo "T"; } else { echo "F"; } return 8;');
		touch($directory . '/main.' . $flavor->name, ++$stamp);
		foreach ([['byte', '1', 'T'], ['int', '1', 'F'], ['float', '1.0', 'F'],
			['string', '"1"', 'F'], ['null', 'null', 'F'], ['uint8', '1', 'T']] as [$type, $literal, $output]) {
			$write($type, $literal);
			$project->run();
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			$fresh->run();
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Identity type edit differs');
			$fixtures[$flavor->name . '_identity_' . $type] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex($output)];
		}
		file_put_contents($path, '');
		touch($path, ++$stamp);
		rejects(fn() => $project->run(), 'value');
		rejects(fn() => $project->run(), 'value');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'value');
		$write('uint8', '1');
		$project->run();
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Identity deletion recovery differs');
		verify($project->run()['preparation']['prepared'] === 0, 'Identity no-op prepared bodies');
		file_put_contents($directory . '/main.' . $flavor->name, 'echo (value() <=> (byte)1); return 8;');
		touch($directory . '/main.' . $flavor->name, ++$stamp);
		foreach ([['byte', '0', '-1'], ['int', '1', '0'], ['float', '1.5', '1']] as [$type, $literal, $output]) {
			$write($type, $literal);
			$project->run();
			$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
			$fresh->run();
			verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Spaceship type edit differs');
			$fixtures[$flavor->name . '_spaceship_' . $type] = ['files' => artifacts($directory . '/out'), 'stdout_hex' => bin2hex($output)];
		}
		$write('bool', 'true');
		rejects(fn() => $project->run(), 'Unsupported binary');
		rejects(fn() => $project->run(), 'Unsupported binary');
		$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
		rejects(fn() => $fresh->run(), 'Unsupported binary');
		$write('float', '1.5');
		$project->run();
		$fresh->run();
		verify(artifacts($directory . '/out') === artifacts($directory . '/fresh'), 'Spaceship recovery differs');
		verify($project->run()['preparation']['prepared'] === 0, 'Spaceship no-op prepared bodies');



	}
	finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Scalar comparison/identity/concatenation: byte content/order, single-pass preparation and incremental/fresh recovery passed\n";
