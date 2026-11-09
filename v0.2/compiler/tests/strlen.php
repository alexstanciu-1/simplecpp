<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, Cpp_Emitter, entry_target, Project_Compiler,
	Incremental_Preparation, prepared_program, prepared_entry, Cpp_Program, Program_Frontend, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

/** Verify the expected source diagnostic at an invalid call. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected rejection: ' . $message);
}

$fixtures = [];
$target = new entry_target(31);
foreach (flavor::cases() as $flavor) {
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('main', $source, $flavor), $target);
	$local = match ($flavor) {
		flavor::cpp => 'string s = "é中😀";', flavor::php => '$s string = "é中😀";', flavor::js => 'let s: string = "é中😀";',
	};
	$s = $flavor === flavor::php ? '$s' : 's';
	$main = $local . 'echo strlen(""), strlen("a\0b"), strlen(' . $s . '); return 8;';
	$cpp = (new Cpp_Emitter())->emit($prepare($main));
	verify(str_contains($cpp, 'scpp::str::byte_length(var_s_0)'), 'Expected direct runtime call on owned string');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('039')];
	$method = static fn(string $op, string $value, string $needle): string => match ($flavor) {
		flavor::php => "str_$op($value, $needle)",
		flavor::cpp => "$value.$op($needle)",
		flavor::js => $value . '.' . ['starts_with' => 'startsWith', 'ends_with' => 'endsWith', 'contains' => 'includes'][$op]
			. "($needle)",
	};
	$checks = [];
	foreach ([['starts_with', $s, '"é"'], ['ends_with', $s, '"😀"'], ['contains', $s, '"中"'],
		['contains', '"a\0b"', '"\0"'], ['contains', 'string_byte_from_int(255)', 'string_byte_from_int(255)'],
		['starts_with', '""', '""'], ['ends_with', '"x"', '"xy"'], ['contains', '"ABC"', '"b"']] as [$op, $value, $needle]) {
		$checks[] = '(int)' . $method($op, $value, $needle);
	}
	$replace_names = match ($flavor) {
		flavor::php => ['str_replace_first', 'str_replace'], flavor::js => ['replace', 'replaceAll'],
		flavor::cpp => ['replace_first', 'replace_all'],
	};
	$replacements = [];
	$expected = [];
	foreach ($replace_names as $mode => $name) {
		foreach ([
			['"aaaaa"', '"aa"', '"X"', 'Xaaa', 'XXa'],
			['"a\0b\0"', '"\0"', '"\0X"', "a\0Xb\0", "a\0Xb\0X"],
			['"ab"', '""', '"X"', 'ab', 'ab'], ['""', '""', '"X"', '', ''],
			['"ab"', '"z"', '"X"', 'ab', 'ab'], ['"aba"', '"a"', '""', 'ba', 'b'],
			['"aa"', '"a"', '"aa"', 'aaa', 'aaaa'], ['"éé"', '"é"', '"$&"', '$&é', '$&$&'],
			['"aaaaaaaaaaaaaaaaaaaaaaaa"', '"a"', '"XY"', 'XY' . str_repeat('a', 23), str_repeat('XY', 24)],
		] as [$value, $search, $replacement, $first, $all]) {
			$replacements[] = $flavor === flavor::php ? "$name($search, $replacement, $value)"
				: "$value.$name($search, $replacement)";
			$expected[] = $mode === 0 ? $first : $all;
		}
	}
	$code = 'echo ' . implode(', "|", ', $replacements) . '; return 8;';
	$fixtures[$flavor->name . '-replace'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($code)),
		'stdout_hex' => bin2hex(implode('|', $expected))];
	$pad = static function (string $value, string $width, ?string $fill, string $side) use ($flavor): string {
		$args = $width . ($fill === null ? '' : ", $fill");
		if ($flavor === flavor::php) {
			return "str_pad($value, $args" . ($side === 'default' ? '' : ', STR_PAD_' . strtoupper($side)) . ')';
		}
		$method = $side === 'left' ? ($flavor === flavor::js ? 'padStart' : 'pad_start')
			: ($flavor === flavor::js ? 'padEnd' : 'pad_end');
		return "$value.$method($args)";
	};
	$pads = [];
	$expected = [];
	$cases = [
		['"x"', '4', null, 'default', 'x   '], ['"x"', '6', '"ab"', 'default', 'xababa'],
		['"x"', '4', '"ab"', 'left', 'abax'], ['"x"', '4', '"ab"', 'right', 'xaba'],
		['"é"', '3', '"中"', 'right', "é\xe4"], ['"\0"', '4', '"\0x"', 'left', "\0x\0\0"],
		['"abc"', '-1', '"x"', 'left', 'abc'], ['"abc"', '3', null, 'default', 'abc'],
		['""', '31', '"ab"', 'left', str_repeat('ab', 15) . 'a'],
	];
	if ($flavor === flavor::php) {
		$cases[] = ['"x"', '4', '"ab"', 'both', 'axab'];
		$cases[] = ['"x"', '5', '"ab"', 'both', 'abxab'];
		foreach (['str_pad("abc", 1, "")' => 'str_pad(): Argument #3 ($pad_string) must not be empty',
			'str_pad("abc", 1, "x", 99)' => 'str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH'] as $call => $message) {
			$fixtures['pad-error-' . count($fixtures)] = ['cpp' => (new Cpp_Emitter())->emit($prepare("echo $call; return 8;")),
				'catch_ValueError' => $message, 'stdout_hex' => ''];
		}
	}
	else {
		$cases[] = ['"abc"', '8', '""', 'left', 'abc'];
		$cases[] = ['"abc"', '1', '""', 'right', 'abc'];
		$cases[] = ['"x"', '3', null, 'left', '  x'];
		rejects(fn() => $prepare('echo STR_PAD_LEFT; return 8;'), 'Unknown');
	}
	foreach ($cases as [$value, $width, $fill, $side, $result]) {
		$pads[] = $pad($value, $width, $fill, $side);
		$expected[] = $result;
	}
	$code = 'echo ' . implode(', "|", ', $pads) . '; return 8;';
	$prepared = $prepare($code);
	if ($flavor === flavor::php) {
		$inspection = json_encode(\scpp\compiler\Compilation_Inspection::program($prepared), JSON_THROW_ON_ERROR);
		verify(str_contains($inspection, 'const:STR_PAD_LEFT'), 'Padding constant missing from preparation report');
	}
	$fixtures[$flavor->name . '-padding'] = ['cpp' => (new Cpp_Emitter())->emit($prepared), 'stdout_hex' => bin2hex(implode('|', $expected))];
	$slice = static fn(string $value, string $args): string => match ($flavor) {
		flavor::php => 'substr(' . $value . ($args === '' ? '' : ',' . $args) . ')',
		flavor::cpp => "$value.substr($args)", flavor::js => "$value.slice($args)",
	};
	$min = '(-9223372036854775807 - 1)';
	$max = '9223372036854775807';
	$cases = [
		['"abcdef"', '2', 'cdef'], ['"abcdef"', '-2', 'ef'], ['"abcdef"', '2,3', 'cde', 'c'],
		['"abcdef"', '3,1', 'd', ''], ['"abcdef"', '-4,-1', 'cde'], ['"abcdef"', '1,0', ''],
		['"abc"', $min, 'abc'], ['"abc"', $max, ''], ['"abc"', "1,$min", ''], ['"abc"', "1,$max", 'bc'],
		['"a\0b"', '1,2', "\0b", "\0"], ['"A😀B"', '1,2', "\xf0\x9f", "\xf0"], ['""', '-1', ''],
	];
	$slices = [];
	$expected = [];
	foreach ($cases as $case) {
		[$value, $args, $length_result] = $case;
		$slices[] = $slice($value, $args);
		$expected[] = $flavor === flavor::js ? ($case[3] ?? $length_result) : $length_result;
	}
	$code = 'echo ' . implode(', "|", ', $slices) . '; return 8;';
	$fixtures[$flavor->name . '-slices'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($code)),
		'stdout_hex' => bin2hex(implode('|', $expected))];
	foreach (['', '1,2,3'] as $args) {
		rejects(fn() => $prepare('echo ' . $slice('"abc"', $args) . '; return 8;'), 'Argument count');
	}
	$predicate = $local . 'echo ' . implode(', ', $checks) . '; return 8;';
	$predicate_cpp = (new Cpp_Emitter())->emit($prepare($predicate));
	verify(str_contains($predicate_cpp, '#include <scpp/' . $flavor->name . '/string.hpp>'), 'Missing flavor provider');
	$adapter_names = match ($flavor) {
		flavor::php => ['str_ends_with', 'str_contains'],
		flavor::cpp => ['ends_with', 'contains'],
		flavor::js => ['endsWith', 'includes'],
	};
	foreach ($adapter_names as $adapter) {
		verify(str_contains($predicate_cpp, $adapter . '('), 'Missing generated adapter call: ' . $adapter);
	}
	verify(!str_contains($predicate_cpp, 'scpp::str::ends_with(') && !str_contains($predicate_cpp, 'scpp::str::contains('),
		'Predicate bypassed its flavor adapter');
	$fixtures[$flavor->name . '-predicates'] = ['cpp' => $predicate_cpp,
		'stdout_hex' => bin2hex('11111100')];
	rejects(fn() => $prepare($local . 'echo (int)' . $method('contains', $s, '5') . '; return 8;'), 'requires string');
	$classic = match ($flavor) {
		flavor::php => 'echo strpos("A😀B","B")+0, strrpos("a😀a","a")+0, (int)isset(strpos("abc","x")), strrpos("ab","")+0, strtolower("ÉAb"), strtoupper("éAb"), str_repeat("x\0",2);',
		flavor::js => 'echo "A😀B".indexOf("B"), "a😀a".lastIndexOf("a"), "abc".indexOf("x"), "ab".lastIndexOf(""), "ÉAb".toLowerCase(), "éAb".toUpperCase(), "x\0".repeat(2);',
		flavor::cpp => 'echo "A😀B".find("B"), "a😀a".rfind("a"), "abc".find("x"), "ab".rfind(""), "ÉAb".to_lower(), "éAb".to_upper(), "x\0".repeat(2);',
	};
	$classic_cpp = (new Cpp_Emitter())->emit($prepare($classic . 'return 8;'));
	$transform_names = match ($flavor) {
		flavor::php => ['strtolower', 'strtoupper', 'str_repeat'],
		flavor::js => ['toLowerCase', 'toUpperCase', 'repeat'],
		flavor::cpp => ['to_lower', 'to_upper', 'repeat'],
	};
	foreach ($transform_names as $name) {
		verify(str_contains($classic_cpp, $name . '('), 'Missing string adapter call: ' . $name);
	}
	foreach (['ascii_lower', 'ascii_upper', 'repeat'] as $name) {
		verify(!str_contains($classic_cpp, 'scpp::str::' . $name . '('), 'String transformation bypassed its adapter');
	}
	$fixtures[$flavor->name . '-classic'] = ['cpp' => $classic_cpp,
		'stdout_hex' => bin2hex(($flavor === flavor::php ? "5502" : "55-12") . "ÉabéABx\0x\0")];
	[$first, $last] = match ($flavor) {
		flavor::php => ['strpos', 'strrpos'], flavor::cpp => ['find', 'rfind'], flavor::js => ['indexOf', 'lastIndexOf'],
	};
	$searches = [];
	foreach ([$first, $last] as $search) {
		foreach ([['"a\0a"', '"a"'], ['"a\0a"', '"\0"'], ['"é"', '""'], ['"x"', '"z"']] as [$value, $needle]) {
			$searches[] = $flavor === flavor::php ? "$search($value,$needle)" : "$value.$search($needle)";
		}
	}
	$expected = '0,1,0,-1,2,1,2,-1';
	if ($flavor === flavor::php) {
		$source = '';
		foreach ($searches as $call) {
			$source .= '$pos = ' . $call . '; if (isset($pos)) { echo $pos + 0; } else { echo "false"; } echo ",";';
		}
		$search = $prepare($source . 'return 8;');
		verify($search->file->main()->body()->statements[0]->type_def() === scpp\compiler\type_result_or_false_int64::instance(),
			'PHP byte search must infer the published carrier');
		$expected = '0,1,0,false,2,1,2,false,';
	}
	else {
		$search = $prepare('echo ' . implode(', ",", ', $searches) . '; return 8;');
	}
	$search_cpp = (new Cpp_Emitter())->emit($search);
	verify(str_contains($search_cpp, '#include <scpp/' . $flavor->name . '/string.hpp>')
		&& str_contains($search_cpp, $first . '(') && str_contains($search_cpp, $last . '(')
		&& !str_contains($search_cpp, 'scpp::str::'), 'Byte search did not lower through the flavor adapter');
	$fixtures[$flavor->name . '-search'] = ['cpp' => $search_cpp, 'stdout_hex' => bin2hex($expected)];
	$repeat = static fn(string $value, string $count): string => $flavor === flavor::php
		? "str_repeat($value, $count)" : "$value.repeat($count)";
	$empty_repeats = 'echo "[",' . $repeat('"abc"', '0') . ', ' . $repeat('""', '2') . ', "]"; return 8;';
	$fixtures[$flavor->name . '-repeat-empty'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($empty_repeats)),
		'stdout_hex' => bin2hex('[]')];
	foreach ([['-1', 'invalid_argument', 'negative_string_repeat'], ['9223372036854775807', 'length_error', 'string_repeat_too_large']] as [$n, $exception, $message]) {
		$fixtures[$flavor->name . '-repeat-' . $exception] = ['cpp' => (new Cpp_Emitter())->emit($prepare(
			'echo ' . $repeat('"ab"', $n) . '; return 8;')), 'stdout_hex' => '', 'catch_' . $exception => $message];
	}
	$i = $flavor === flavor::php ? '$i' : 'i';
	$counter = match ($flavor) {
		flavor::cpp => 'int i = 0;', flavor::php => '$i int = 0;', flavor::js => 'let i: int = 0;',
	};
	$trim_names = match ($flavor) {
		flavor::php => ['trim', 'ltrim', 'rtrim'], flavor::js => ['trim', 'trimStart', 'trimEnd'],
		flavor::cpp => ['trim', 'trim_start', 'trim_end'],
	};
	$trim_call = static fn(string $name, string $value): string => $flavor === flavor::php
		? "$name($value)" : "$value.$name()";
	$trim_output = 'echo ' . implode(', "|", ', array_map(static fn($name) => $trim_call($name, $s), $trim_names)) . ';';
	$concat = $flavor === flavor::php ? '.' : '+';
	$byte = "string_byte_from_int($i)";
	$trim_source = $local . $counter . "while ($i < 256) { $s = $byte $concat " . '"A\0B"' . " $concat $byte;"
		. $trim_output . "$i += 1; }";
	$expected = '';
	$mask = " \t\n\r\v" . ($flavor === flavor::php ? "\0" : "\f");
	$oracle = static fn(string $v): string => trim($v, $mask) . '|' . ltrim($v, $mask) . '|' . rtrim($v, $mask);
	for ($n = 0; $n < 256; ++$n) {
		$expected .= $oracle(chr($n) . "A\0B" . chr($n));
	}
	foreach (['""' => '', '" \t\r\n "' => " \t\r\n ", '"\0 A \0"' => "\0 A \0",
		'" long unchanged UTF-8 string "' => ' long unchanged UTF-8 string '] as $literal => $value) {
		$trim_source .= "$s = $literal;" . $trim_output;
		$expected .= $oracle($value);
	}
	$fixtures[$flavor->name . '-trim'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($trim_source . 'return 8;')),
		'stdout_hex' => bin2hex($expected)];
	if ($flavor === flavor::php) {
		foreach ($trim_names as $name) rejects(fn() => $prepare("echo $name(\"x\",\"x\");return 8;"), 'Argument count');
	}
	$bytes = $counter . "while ($i < 256) { echo string_byte_from_int($i); $i += 1; } return 8;";
	$fixtures[$flavor->name . '-all-bytes'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bytes)),
		'stdout_hex' => bin2hex(implode('', array_map(chr(...), range(0, 255))))];
	foreach ([-1, 256] as $value) {
		$fixtures[$flavor->name . '-invalid-' . $value] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare("echo string_byte_from_int($value); return 8;")),
			'stdout_hex' => '', 'catch_invalid_argument' => 'Byte value must be between 0 and 255',
		];
	}
	rejects(fn() => $prepare('echo string_byte_from_int("65"); return 8;'), 'requires int64');
	$namespaced = 'namespace App { use function strlen as bytes; echo strlen("é"), bytes("x"); return 8; }';
	$fixtures[$flavor->name . '-namespace'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($namespaced)), 'stdout_hex' => bin2hex('21')];
	foreach (['strlen()' => 'Argument count', 'strlen("x","y")' => 'Argument count', 'strlen(1)' => 'requires string'] as $call => $error) {
		rejects(fn() => $prepare("echo $call; return 8;"), $error);
	}

	$dir = sys_get_temp_dir() . '/scpp-strlen-' . bin2hex(random_bytes(8));
	mkdir($dir);
	try {
		$lib = "$dir/lib." . $flavor->name;
		file_put_contents("$dir/module.json", '{}');
		file_put_contents("$dir/main." . $flavor->name, 'echo string_byte_from_int(strlen(word())), (int)' . $method('contains', 'word()', '"中"')
			. ', (int)' . $method('ends_with', 'word()', '"😀"') . '; return 8;');
		$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
			? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
		$valid = $function('string', 'word', 'echo "X"; return "é中😀";');
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$revision = time();
		foreach ([$valid, $function('int', 'word', 'return 2;'), 'retry', null,
			$valid . $function('int', 'strlen', 'return 1;'), $valid, 'add_shadow', 'retry', 'remove_shadow', 'shadow_byte', 'retry', 'remove_shadow'] as $step => $body) {
			if ($body !== 'retry') {
				if ($body === 'shadow_byte' || $body === 'add_shadow') {
					$name = $body === 'shadow_byte' ? 'string_byte_from_int' : 'strlen';
					file_put_contents("$dir/shadow." . $flavor->name, $function('int', $name, 'return 1;'));
				}
				elseif ($body === 'remove_shadow') {
					unlink("$dir/shadow." . $flavor->name);
				}
				elseif ($body === null) {
					unlink($lib);
				}
				else {
					file_put_contents($lib, $body);
					touch($lib, ++$revision);
				}
				$session = $compiler->frontend($dir, $session);
			}
			$root ??= $session->entry->entry->ast;
			verify($session->entry->entry->ast === $root, 'Unchanged caller was reparsed');
			$files = $session->files();
			$sources = array_map(static fn($file) => $file->source(), $files);
			$fresh = static fn() => (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
			if (in_array($step, [1, 2, 3, 4, 6, 7, 9, 10], true)) {
				$error = match ($step) {
					3 => 'Unknown function', 4, 6, 7, 9, 10 => 'Argument count', default => 'requires string',
				};
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $error);
				rejects($fresh, $error);
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$code = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			verify($code === Cpp_Program::emit($fresh())->files, 'Incremental and fresh unary function lowering differ');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex("X\x09X1X1")];
		}
	}
	finally {
		foreach (glob($dir . '/*') as $file) {
			unlink($file);
		}
		rmdir($dir);
	}
}
// An unchanged JS caller switches between namespace and member as a source unit changes.
$dir = sys_get_temp_dir() . '/scpp-string-namespace-' . bin2hex(random_bytes(8));
mkdir($dir);
try {
	file_put_contents("$dir/module.json", '{}');
	file_put_contents("$dir/main.js", 'echo (int)Box.includes("a"), (int)\Box.includes("a"); return 8;');
	file_put_contents("$dir/lib.js", 'namespace Box { function includes(s: string): bool { return false; } }');
	$session = null;
	$compiler = new Project_Compiler();
	foreach ([false, true, false] as $step => $shadow) {
		if ($shadow) {
			file_put_contents("$dir/value.js", 'const Box: string = "abc";');
		}
		elseif ($step !== 0) {
			unlink("$dir/value.js");
		}
		$session = $compiler->frontend($dir, $session);
		Incremental_Preparation::prepare($session, $target);
		$files = $session->files();
		$program = new prepared_program($session->entry_key(), $files, new prepared_entry($session->entry->entry->ast, $target), $session);
		$code = Cpp_Program::emit($program)->files;
		$fresh = (new Program_Frontend())->prepare_sources($session->entry_key(), array_map(static fn($f) => $f->source(), $files), $target);
		verify($code === Cpp_Program::emit($fresh)->files, 'Namespace/member incremental selection differs from fresh');
		$fixtures['js-qualifier-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex($shadow ? '10' : '00')];
	}
	file_put_contents("$dir/nested.js", 'namespace Box.Inner { function ping(): int { return 17; } }');
	file_put_contents("$dir/main.js", 'echo Box.Inner.ping(); return 8;');
	foreach (['clean', 'shadow', 'retry', 'recover'] as $step) {
		if ($step === 'shadow') {
			file_put_contents("$dir/value.js", 'const Box: string = "abc";');
		}
		if ($step === 'recover') {
			unlink("$dir/value.js");
		}
		$session = $compiler->frontend($dir, $session);
		$files = $session->files();
		$fresh = static fn() => (new Program_Frontend())->prepare_sources($session->entry_key(),
			array_map(static fn($f) => $f->source(), $files), $target);
		if ($step === 'shadow' || $step === 'retry') {
			rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Member reads');
			rejects($fresh, 'Member reads');
			continue;
		}
		Incremental_Preparation::prepare($session, $target);
		$program = new prepared_program($session->entry_key(), $files, new prepared_entry($session->entry->entry->ast, $target), $session);
		$code = Cpp_Program::emit($program)->files;
		verify($code === Cpp_Program::emit($fresh())->files, 'Nested namespace recovery differs from fresh');
		$fixtures['js-nested-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex('17')];
	}
}
finally {
	foreach (glob($dir . '/*') as $file) {
		unlink($file);
	}
	rmdir($dir);
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
}
echo "Unary byte functions, admission and incremental recovery passed\n";
