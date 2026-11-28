<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Cpp_Program, Program_Frontend, prepared_program, prepared_entry};

function check(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

/** Require a source diagnostic, optionally checking its explanatory text. */
function rejects(Closure $action, ?string $message = null): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		if ($message !== null) check(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected source diagnostic');
}

$fixtures = [];
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$var = static fn(string $name) => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value) => match ($flavor) {
		flavor::cpp => "$type $name = $value;", flavor::php => '$' . "$name $type = $value;", flavor::js => "let $name: $type = $value;",
	};
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp ? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('lists', $source, $flavor), new entry_target(31));
	$emit = static function (string $source) use ($prepare): string {
		$entry = $prepare($source);
		$before = serialize($entry);
		$cpp = (new Cpp_Emitter())->emit($entry);
		check(serialize($entry) === $before, 'Emission mutated prepared syntax');
		return $cpp;
	};
	$v = $var('values');
	$s = $var('text');
	foreach ([0, 1, 3, 511, 512, 513] as $count) {
		$literal = '[' . implode(', ', array_fill(0, $count, '7')) . ']';
		$cpp = $emit($decl('vector<int>', 'values', $literal) . 'echo count(' . $v . '); return 8;');
		check(!str_contains($cpp, '.append('), 'Literal lowered to per-element append');
		check($count === 0 || str_contains($cpp, '::from_owned(' . ($count <= 512 ? 'input' : 'input.data()') . ', ' . $count . ')'), 'Missing factory');
		$fixtures[$flavor->name . '-count-' . $count] = ['cpp' => $cpp, 'stdout_hex' => bin2hex((string)$count)];
	}
	$boundary = $decl('vector<int>', 'values', '[-0x8000000000000000, 9223372036854775807, true, 9.75]')
		. 'echo ' . $v . '[0], "|", ' . $v . '[1], "|", ' . $v . '[2], "|", ' . $v . '[3]; return 8;';
	$fixtures[$flavor->name . '-boundaries'] = ['cpp' => $emit($boundary), 'stdout_hex' => bin2hex('-9223372036854775808|9223372036854775807|1|9')];
	foreach ([false, true] as $failure) {
		$source = $function('int', 'first', 'echo "A"; return 17;')
			. $function('float', 'second', 'echo "B"; return ' . ($failure ? '1e100' : '24.75') . ';')
			. $function('int', 'third', 'echo "C"; return 31;')
			. $decl('vector<int>', 'values', '[first(), second(), third()]')
			. 'echo ' . $v . '[0], ":", ' . $v . '[1], ":", ' . $v . '[2]; return 8;';
		$fixture = ['cpp' => $emit($source), 'stdout_hex' => bin2hex($failure ? 'AB' : 'ABC17:24:31')];
		if ($failure) $fixture['catch_out_of_range'] = true;
		$fixtures[$flavor->name . '-order-' . (int)$failure] = $fixture;
	}
	$source = $function('int', 'first', 'echo "A"; return 17;')
		. $function('int', 'second', 'echo "B";' . $decl('vector<int>', 'empty', '[]') . 'return ' . $var('empty') . '[0];')
		. $function('int', 'third', 'echo "C"; return 31;')
		. $decl('vector<int>', 'values', '[first(), second(), third()]') . 'return 8;';
	$fixtures[$flavor->name . '-entry-failure'] = ['cpp' => $emit($source), 'stdout_hex' => bin2hex('AB'), 'catch_out_of_range' => true];
	$pretender = match ($flavor) {
		flavor::php => 'struct Pretender { public static function from_owned():int { return 1; } }',
		flavor::cpp => 'struct Pretender { static int from_owned() { return 1; } };',
		flavor::js => 'struct Pretender { static from_owned():int { return 1; } }',
	};
	rejects(fn() => $prepare($pretender . $decl('Pretender', 'value', '[1]') . 'return 8;'), 'published collection initialization capability');
	$bytes = 'long heap-backed input with a NUL\\0after the zero';
	$source = $decl('string', 'text', '"' . $bytes . '"') . $decl('vector<string>', 'values', "[$s, $s, \"a\\0b\"]")
		. $v . '[0] = "replacement";' . 'echo ' . $s . ', "|", ' . $v . '[1], "|", ' . $v . '[2];'
		. $s . ' = "changed"; echo "|", ' . $v . '[1]; return 8;';
	$actual = str_replace('\\0', "\0", $bytes);
	$fixtures[$flavor->name . '-strings'] = ['cpp' => $emit($source), 'stdout_hex' => bin2hex($actual . '|' . $actual . "|a\0b|" . $actual)];
	foreach ([127, 128, 129, 256, 257] as $count) {
		$fixtures[$flavor->name . '-string-count-' . $count] = ['cpp' => $emit($decl('vector<string>', 'values', '[' . implode(',', array_fill(0, $count, '"a\\0b"')) . ']')
			. 'echo count(' . $v . '), ' . $v . '[' . ($count - 1) . ']; return 8;'), 'stdout_hex' => bin2hex($count . "a\0b")];
	}
	$fixtures[$flavor->name . '-string-source-129'] = ['cpp' => $emit($decl('string', 'text', '"' . $bytes . '"')
		. $decl('vector<string>', 'values', '[' . implode(',', array_fill(0, 129, $s)) . ']')
		. 'echo count(' . $v . '), ' . $v . '[128]; return 8;'), 'stdout_hex' => bin2hex('129' . $actual)];
	foreach ([$decl('vector<int>', 'values', '["bad"]'), $decl('vector<int>', 'values', '[1e100]'),
		$decl('vector<int>', 'values', '[[1]]'), $decl('hash<int,int>', 'values', '[1]'),
		$decl('vector<int>', 'values', '[1 => 2]'), 'echo [1];'] as $source) rejects(fn() => $prepare($source . 'return 8;'));

	$directory = sys_get_temp_dir() . '/scpp-list-incremental-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->source_extension();
		$lib = $directory . '/lib.' . $flavor->source_extension();
		file_put_contents($directory . '/unrelated.' . $flavor->source_extension(), $function('int', 'unrelated', 'return 42;'));
		file_put_contents($main, $decl('vector<int>', 'values', '[number(), 3]') . 'echo ' . $v . '[0]; return 8;');
		$compiler = new Project_Compiler();
		$target = new entry_target(31);
		$session = null;
		$root = null;
		$output = null;
		$revision = time();
		foreach ([['int','8'], ['float','9.75'], ['string','"bad"'], [null,''], ['int','12']] as $step => [$type,$value]) {
			if ($type === null) unlink($lib);
			else {
				file_put_contents($lib, $function($type, 'number', 'return ' . $value . ';'));
				touch($lib, ++$revision);
			}
			$session = $compiler->frontend($directory, $session);
			$root ??= $session->entry->entry->ast;
			check($root === $session->entry->entry->ast, 'Unchanged caller AST replaced');
			if ($type === null || $type === 'string') {
				rejects(fn() => Incremental_Preparation::prepare($session, $target));
				rejects(fn() => Incremental_Preparation::prepare($session, $target));
				continue;
			}
			$stats = Incremental_Preparation::prepare($session, $target);
			if ($step === 1) check($stats['prepared'] === 2, 'Caller/signature invalidation must leave unrelated body reusable');
			$files = $session->files();
			$output = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session),
				$output, $session->preparation_work->emission_sources());
			if ($step > 0) foreach ($output->emitted_sources as $path) check(!str_contains($path, 'unrelated'), 'Unrelated emitted unit was rebuilt');
			$session->preparation_work->complete_emission();
			$incremental = $output->files;
			$sources = [];
			foreach ($files as $key => $file) $sources[$key] = $file->source();
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			check($incremental === $fresh, 'Recovery differs from fresh compilation');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex((string)(int)(float)$value)];
			$noop = Incremental_Preparation::prepare($session, $target);
			check($noop['prepared'] === 0, 'No-op redoes preparation');
			$reused = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session), $output, $session->preparation_work->emission_sources());
			check($reused->emitted_sources === [], 'No-op redoes emission');
		}
		foreach ([['vector<int>', '[number(), 4, 5]', '3'], ['vector<int>', '["bad"]', null],
			['vector<int>', '[number()]', '1'], ['vector<string>', '["ok", "x"]', '2'],
			['vector<int>', '[]', '0'], ['vector<int>', '[number()]', '1']] as $step => [$vector, $literal, $expected]) {
			file_put_contents($main, $decl($vector, 'values', $literal) . 'echo count(' . $v . '); return 8;');
			touch($main, ++$revision);
			$session = $compiler->frontend($directory, $session);
			if ($step === 1) {
				rejects(fn() => Incremental_Preparation::prepare($session, $target));
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($session->entry->entry->ast, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) $sources[$key] = $file->source();
			check($incremental === Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files, 'Literal edit recovery differs');
			if ($literal === '[]') check(!str_contains(implode('', $incremental), '::from_owned'), 'Retired literal factory remains in output');
			$fixtures[$flavor->name . '-entry-edit-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex($expected)];
		}
	}
	finally {
		foreach (glob($directory . '/*') as $path) unlink($path);
		rmdir($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
echo 'Flat list initialization and incremental proofs passed: ' . count($fixtures) . " fixtures\n";
