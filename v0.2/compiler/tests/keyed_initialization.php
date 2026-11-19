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
foreach (flavor::cases() as $flavor) {
	$var = static fn(string $name) => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $type, string $name, string $value) => match ($flavor) {
		flavor::cpp => "$type $name = $value;", flavor::php => '$' . "$name $type = $value;", flavor::js => "let $name: $type = $value;",
	};
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp ? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('keyed', $source, $flavor), new entry_target(31));
	$emit = static function (string $source) use ($prepare): string {
		$entry = $prepare($source);
		$before = serialize($entry);
		$cpp = (new Cpp_Emitter())->emit($entry);
		check(serialize($entry) === $before, 'Emission mutated prepared syntax');
		return $cpp;
	};
	$fixture = static function (string $name, string $source, string $expected, ?string $error = null) use (&$fixtures, $flavor, $emit): void {
		$cpp = $emit($source);
		if ($error !== null) {
			$cpp = '#include <stdexcept>' . "\n#include <string_view>\n" . str_replace('int main()', 'int tested_entry()', $cpp);
			$cpp .= '\nint main() {try {tested_entry();} catch(const std::exception& e) {return std::string_view(e.what()) == "' . $error . '" ? 8 : 90;} return 91;}';
			$cpp = str_replace('\\nint main', "\nint main", $cpp);
		}
		$fixtures[$flavor->name . '-' . $name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected)];
	};
	$v = $var('values');
	$const = static fn(string $type, string $name, string $literal) => $flavor === flavor::js ? "const $name: $type=$literal;" : "const $type $name=$literal;";
	$once = $prepare($decl('hash<int,int>', 'values', '[1 => 2, 3 => 4]') . 'return 8;');
	check($once->file->declaration_nodes[0]->body()->expression_visits === 6, 'Key/value preparation revisited syntax');
	$fixture('constant-duplicate', $const('int', 'KEY', '7') . $const('int', 'ALIAS', '7')
		. $decl('hash<int,int>', 'values', '[KEY => 1, ALIAS => 2]') . 'return 8;', '', 'duplicate_initializer_key');

	foreach (['int', 'string'] as $key) foreach (['int', 'string'] as $value) {
		foreach ([0, 1, 63, 64, 65, 257] as $count) {
			$entries = [];
			for ($i = 0; $i < $count; $i++) $entries[] = ($key === 'int' ? (string)$i : '"k' . $i . '"') . ' => ' . ($value === 'int' ? (string)$i : '"v' . $i . '\\0x"');
			$source = $decl("hash<$value,$key>", 'values', '[' . implode(',', $entries) . ($count ? ',' : '') . ']') . 'echo count(' . $v . ');';
			if ($count) $source .= 'echo ' . $v . '[' . ($key === 'int' ? (string)($count - 1) : '"k' . ($count - 1) . '"') . '];';
			$fixture("$key-$value-$count", $source . 'return 8;', $count . ($count ? ($value === 'int' ? (string)($count - 1) : 'v' . ($count - 1) . "\0x") : ''));
		}
	}
	$fixture('bytes', $decl('hash<int,string>', 'values', '["" => 1, "01" => 2, "1" => 3, "a\\0b" => 4]')
		. 'echo ' . $v . '[""], ' . $v . '["01"], ' . $v . '["1"], ' . $v . '["a\\0b"]; return 8;', '1234');
	$fixture('bounds', $decl('hash<int,int>', 'values', '[-0x8000000000000000 => 9223372036854775807, 9223372036854775807 => true]')
		. 'echo ' . $v . '[-0x8000000000000000], ' . $v . '[9223372036854775807]; return 8;', '92233720368547758071');
	foreach (['success', 'duplicate', 'key_failure', 'key_conversion_failure', 'value_failure'] as $mode) {
		$key_body = 'echo "K"; return 1;';
		if ($mode === 'key_failure') $key_body = 'echo "K";' . $decl('vector<int>', 'empty', '[]') . 'return ' . $var('empty') . '[0];';
		if ($mode === 'key_conversion_failure') $key_body = 'echo "K"; return 1e100;';
		$source = $function($mode === 'key_conversion_failure' ? 'float' : 'int', 'key', $key_body) . $function('float', 'value', 'echo "V"; return ' . ($mode === 'value_failure' ? '1e100' : '7.75') . ';')
			. $function('int', 'later', 'echo "L"; return 9;')
			. $decl('hash<int,int>', 'values', '[' . ($mode === 'key_conversion_failure' ? '(int)key()' : 'key()') . ' => value(), ' . ($mode === 'duplicate' ? '1' : '2') . ' => later()]') . 'return 8;';
		$error = match ($mode) {
			'duplicate' => 'duplicate_initializer_key', 'key_failure' => 'sequence_index_out_of_range',
			'value_failure', 'key_conversion_failure' => 'integer_cast_out_of_range', default => null,
		};
		$fixture('order-' . $mode, $source, in_array($mode, ['key_failure', 'key_conversion_failure'], true) ? 'K' : ($mode === 'value_failure' ? 'KV' : 'KVL'), $error);
	}
	$fixture('duplicate-spelling', $decl('hash<int,string>', 'values', "[\"a\" => 1, 'a' => 2]") . 'return 8;', '', 'duplicate_initializer_key');
	$source = $decl('string', 'text', '"heap backed value with embedded\\0bytes for copy"')
		. $decl('hash<string,string>', 'values', '["a" => ' . $var('text') . ', "b" => ' . $var('text') . ']')
		. $v . '["a"] = "replacement";' . $var('text') . ' = "changed"; echo ' . $v . '["b"]; return 8;';
	$fixture('copies', $source, "heap backed value with embedded\0bytes for copy");
	$entries = [];
	for ($i = 0; $i < 65; ++$i) $entries[] = '"long heap-backed key number ' . $i . '" => ' . $var('text');
	$fixture('heap-input', $decl('string', 'text', '"heap backed value with embedded\\0bytes for copy"')
		. $decl('hash<string,string>', 'values', '[' . implode(',', $entries) . ']')
		. 'echo count(' . $v . '), ' . $v . '["long heap-backed key number 64"]; return 8;', "65heap backed value with embedded\0bytes for copy");

	foreach ([$decl('hash<int,int>', 'values', '["bad" => 1]'), $decl('hash<int,string>', 'values', '["k" => "bad"]'),
		$decl('hash<int,int>', 'values', '[true => 1]'), $decl('hash<int,int>', 'values', '[1 => 1e100]'),
		$decl('hash<int,int>', 'values', '[1 => 2, 3]'), $decl('hash<int,int>', 'values', '[1, 2 => 3]'),
		$decl('vector<int>', 'values', '[1 => 2]'), $decl('mixed', 'values', '[1 => 2]'),
		$decl('hash<int,int>', 'values', '[1 => [2 => 3]]'), $decl('vector<hash<int,int>>', 'values', '[[1 => 2]]'),
		$decl('hash<int,int>', 'values', '[1 => ' . $v . '[1]]'), 'echo [1 => 2];'] as $source) rejects(fn() => $prepare($source . 'return 8;'));
	$directory = sys_get_temp_dir() . '/scpp-keyed-incremental-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$lib = $directory . '/lib.' . $flavor->name;
		file_put_contents($directory . '/unrelated.' . $flavor->name, $function('int', 'unrelated', 'return 42;'));
		file_put_contents($main, $decl('hash<int,int>', 'values', '[1 => number(), 3 => 3]') . 'echo ' . $v . '[1]; return 8;');
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
		foreach ([['hash<int,int>', '[2 => number(), 4 => 4, 5 => 5]', '3'], ['hash<int,int>', '[1 => "bad"]', null],
			['hash<int,int>', '[1 => number()]', '1'], ['hash<string,string>', '["ok" => "ok", "x" => "x"]', '2'],
			['hash<int,int>', '[]', '0'], ['hash<int,int>', '[1 => number()]', '1']] as $step => [$vector, $literal, $expected]) {
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
			if ($literal === '[]') check(!str_contains(implode('', $incremental), '::from_owned_entries'), 'Retired literal factory remains in output');
			$fixtures[$flavor->name . '-entry-edit-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex($expected)];
		}
		file_put_contents($main, $decl('hash<int,int>', 'values', '[KEY => 7]') . 'echo count(' . $v . '); return 8;');
		touch($main, ++$revision);
		foreach ([['int', '11'], ['int', '19'], ['string', '"bad"'], [null, ''], ['int', '23']] as $step => [$type, $literal]) {
			if ($type === null) unlink($lib);
			else {
				file_put_contents($lib, $const($type, 'KEY', $literal));
				touch($lib, ++$revision);
			}
			$session = $compiler->frontend($directory, $session);
			if ($type !== 'int') {
				rejects(fn() => Incremental_Preparation::prepare($session, $target));
				rejects(fn() => Incremental_Preparation::prepare($session, $target));
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$program = new prepared_program($session->entry_key(), $files, new prepared_entry($session->entry->entry->ast, $target), $session);
			$incremental = Cpp_Program::emit($program)->files;
			$sources = [];
			foreach ($files as $key => $file) $sources[$key] = $file->source();
			check($incremental === Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files, 'Key dependency recovery differs from fresh');
			$fixtures[$flavor->name . '-key-edit-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex('1')];
		}
	}
	finally {
		foreach (glob($directory . '/*') as $path) unlink($path);
		rmdir($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
echo 'Keyed initialization and incremental proofs passed: ' . count($fixtures) . " fixtures\n";
