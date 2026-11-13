<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Cpp_Program, Program_Frontend, prepared_program, prepared_entry};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function declaration(flavor $flavor, string $type, string $name, string $value): string
{
	return match ($flavor) {
		flavor::cpp => "$type $name = $value;", flavor::php => '$' . "$name $type = $value;",
		flavor::js => "let $name: $type = $value;",
	};
}

/** Assert a source diagnostic, not an internal compiler failure. */
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
foreach (flavor::cases() as $flavor) {
	$decl = static fn(string $type, string $name, string $value) => declaration($flavor, $type, $name, $value);
	$var = static fn(string $name) => ($flavor === flavor::php ? '$' : '') . $name;
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('boxed', $text, $flavor), new entry_target(31));
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';

	$x = $var('x');
	$y = $var('y');
	$infer = static fn(string $name, string $value) => match ($flavor) {
		flavor::cpp => "auto $name = $value;", flavor::php => '$' . "$name = $value;",
		flavor::js => "let $name = $value;",
	};
	$index = static fn(string $name, string $key) => $var($name) . '[' . $key . ']';

	foreach (['mixed', 'dynamic'] as $family) {
		$box = $decl($family, 'x', '[]') . $index('x', '"1"') . ' = 8;'
			. $decl($family, 'y', $x) . $index('y', '1') . ' = 9;'
			. 'echo (int)' . $index('x', '1') . ', (int)' . $index('y', '"1"') . ';'
			. $index('x', '"01"') . ' = 2;' . $index('x', '"a\0b"') . ' = 3;'
			. $index('x', '"4294967296"') . ' = 4;' . $index('x', '2147483647') . ' = 5;'
			. "$y = 7; echo (int)" . $index('x', '"1"') . ', (int)' . $index('x', '"01"')
			. ', (int)' . $index('x', '"a\0b"') . ', (int)' . $index('x', '"4294967296"')
			. ', (int)' . $index('x', '"2147483647"') . ';'
			. $decl('null', 'miss', $index('x', '"absent"')) . $decl($family, 'nil', 'null')
			. $decl('null', 'again', $index('nil', '0'));
		if ($family === 'mixed') {
			$box .= $index('nil', '0') . ' = 6; echo (int)' . $index('nil', '0') . ';';
		}
		$box .= $decl($family, 'parent_box', '[]') . $index('parent_box', '0') . " = $x;"
			. $decl($family, 'child', $index('parent_box', '0')) . $index('child', '1') . ' = 7;'
			. 'echo (int)' . $index('x', '1') . ';';
		$fixtures[$flavor->name . '-' . $family . '-container'] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($box . ' return 8;')),
			'stdout_hex' => bin2hex($family === 'mixed' ? '898234568' : '99923457')];
		$key = $var('key');
		$item = $var('item');
		$arguments = $function('int', 'number', 'echo "!"; return 6;')
			. $decl($family, 'x', '[]') . $decl('int', 'key', '0') . $decl('int', 'item', '2')
			. $index('x', $key) . " = $item; echo (int)" . $index('x', $key) . ';'
			. $index('x', $key) . " = $item <=> $key; echo (int)" . $index('x', $key) . ';'
			. $index('x', $key) . ' = number(); echo (int)' . $index('x', $key) . ';'
			. $decl('byte', 'small', '255') . $index('x', $key) . ' = ' . $var('small') . '; echo (int)' . $index('x', $key) . ';'
			. $decl($family, 'y', '7') . $index('x', $key) . " = $y; echo (int)" . $index('x', $key) . '; return 8;';
		$cpp = (new Cpp_Emitter())->emit($prepare($arguments));
		$factory = "scpp::{$family}::from_integer";
		foreach (['var_item_2', 'static_cast<scpp::int64>(((var_item_2 > var_key_1)) - ((var_item_2 < var_key_1)))',
			'number_()', 'static_cast<scpp::int64>(var_small_3)'] as $argument) {
			verify(str_contains($cpp, "var_x_0.source_set(scpp::boxed_key(var_key_1), $factory($argument));"), "Unexpected $family factory argument: $argument");
		}
		verify(!str_contains($cpp, 'write_key')
			&& str_contains($cpp, '.source_read(scpp::boxed_key(var_key_1))')
			&& str_contains($cpp, 'var_x_0.source_set(scpp::boxed_key(var_key_1), var_y_4);'), 'Lost key validation or same-family binding');
		$fixtures[$flavor->name . '-' . $family . '-arguments'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('21!62557')];
		$literals = $decl($family, 'x', '[]') . $index('x', '"k"') . ' = 8;'
			. 'echo (int)' . $index('x', '"k"') . ';'
			. $index('x', '0') . ' = 2.5; echo (int)' . $index('x', '0') . ';'
			. $index('x', '0') . ' = true; echo (int)' . $index('x', '0') . ';'
			. $index('x', '0') . ' = null;' . $decl('null', 'nil', $index('x', '0'))
			. $index('x', '0') . ' = "text"; echo (string)' . $index('x', '0') . '; return 8;';
		$cpp = (new Cpp_Emitter())->emit($prepare($literals));
		verify(preg_match('/var_x_0\.source_set\(scpp::boxed_key\(scpp::string\{"k", 1\}\), scpp::' . $family . '::from_integer\(8L{0,2}\)\);/', $cpp) === 1,
			'Expected a direct keyed store for a boxed integer literal');
		verify(substr_count($cpp, 'var_x_0.source_set(scpp::boxed_key(') === 5, 'Expected direct boxed literal stores');
		verify(str_contains($cpp, 'var_x_0.source_read(scpp::boxed_key(scpp::string{"k", 1}))')
			&& str_contains($cpp, ", scpp::{$family}::from_string("), 'Missing string read or string boxing');
		$fixtures[$flavor->name . '-' . $family . '-literal-store'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('821text')];

		$parameter = match ($flavor) {
			flavor::cpp => 'int &key', flavor::php => 'int &$key', flavor::js => '&key: int',
		};
		$mutate = ($flavor === flavor::cpp ? "int mutate($parameter)" : "function mutate($parameter): int")
			. '{' . $key . ' = 2; return 7;}';
		$mutation = $mutate . $decl($family, 'x', '[]') . $decl('int', 'key', '1')
			. $index('x', '1') . ' = 0;' . $index('x', '2') . ' = 0;'
			. $index('x', $key) . " = mutate($key); echo (int)" . $index('x', '1') . ', (int)' . $index('x', '2') . ", $key;"
			. "$key = 1;" . $index('x', "mutate($key)") . " = $key; echo (int)" . $index('x', '7') . '; return 8;';
		$fixtures[$flavor->name . '-' . $family . '-mutated-key'] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($mutation)), 'stdout_hex' => array_map('bin2hex', ['7021', '7022', '0721', '0722'])];
		$f = $var('f');
		$casts = $function('float', 'fraction', 'return 6.75;') . $decl($family, 'x', '[]')
			. $decl('float', 'f', '2.5') . $decl($family, 'y', '7')
			. $index('x', '0') . " = (int)$f;" . $index('x', '1') . ' = (int)fraction();'
			. $index('x', '2') . " = (int)$y;" . $index('x', '3') . ' = (int)4;'
			. 'echo (int)' . $index('x', '0') . ', (int)' . $index('x', '1') . ', (int)' . $index('x', '2')
			. ', (int)' . $index('x', '3') . '; return 8;';
		$cpp = (new Cpp_Emitter())->emit($prepare($casts));
		foreach (['static_cast<scpp::int64>(var_f_1)', 'scpp::checked_float64_to_int64(fraction_())',
			"scpp::boxed_{$family}_explicit_cast_int64(var_y_2)"] as $converted) {
			verify(str_contains($cpp, "$factory(($converted))"), "Expected exact converted argument: $converted");
		}
		$fixtures[$flavor->name . '-' . $family . '-cast-arguments'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('2674')];
		$peer = $family === 'mixed' ? 'dynamic' : 'mixed';
		rejects(fn() => $prepare($decl($family, 'x', '[]') . $decl($peer, 'y', '8')
			. $index('x', '0') . " = $y;"), 'Unsupported boxed conversion');
		foreach (['true', '1.5', 'null', $x] as $key) {
			rejects(fn() => $prepare($decl($family, 'x', '[]') . $index('x', $key) . ' = 1;'), 'key requires int or string');
		}
		// Source catch is deferred. Observe the receiver at the failed store in the native fixture.
		$bad = $decl($family, 'x', 'null') . $index('x', '"2147483648"') . ' = 8; return 91;';
		$cpp = (new Cpp_Emitter())->emit($prepare($bad));
		$cpp = preg_replace('/^(\s*var_x_0\.source_set\([^\n]+;)$/m',
			"\ttry {\n$1\n\t} catch (const std::out_of_range& e) { return var_x_0.kind() == scpp::boxed_kind::null"
			. " && std::string_view(e.what()) == \"packed_integer_key_limit\" ? 8 : 92; }", $cpp, 1, $matches);
		verify($matches === 1, 'Missing boxed store for failure observation');
		$fixtures[$flavor->name . '-' . $family . '-invalid-key-no-store'] = [
			'cpp' => "#include <stdexcept>\n#include <string_view>\n" . $cpp, 'stdout_hex' => ''];
		foreach (['1', '2147483648'] as $key_text) {
			$order = $function('string', 'key', 'echo "K"; return "' . $key_text . '";')
				. $function('int', 'rhs', 'echo "R"; return 7;') . $decl($family, 'x', '[]')
				. $index('x', 'key()') . ' = rhs(); echo (int)' . $index('x', '1') . '; return 8;';
			$fixture = ['cpp' => (new Cpp_Emitter())->emit($prepare($order)),
				'stdout_hex' => array_map('bin2hex', $key_text === '1' ? ['KR7', 'RK7'] : ['K', 'RK'])];
			if ($key_text !== '1') {
				$fixture['catch_out_of_range'] = 'packed_integer_key_limit';
			}
			$fixtures[$flavor->name . '-' . $family . '-key-order-' . $key_text] = $fixture;
		}
		$bad = $function('int', 'rhs', 'echo "!"; return 8;') . $decl($family, 'x', '[]')
			. $index('x', '-1') . ' = rhs(); return 8;';
		$fixtures[$flavor->name . '-' . $family . '-negative-key'] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($bad)), 'stdout_hex' => ['', bin2hex('!')], 'catch_out_of_range' => 'packed_integer_key_limit'];
		$bad = $decl($family, 'x', '1') . $decl($family, 'y', $index('x', '0')) . 'return 8;';
		$fixtures[$flavor->name . '-' . $family . '-scalar-read'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bad)),
			'stdout_hex' => '', 'catch_runtime_error' => 'boxed_expected_container'];
		if ($family === 'dynamic') {
			$bad = $decl($family, 'x', 'null') . $index('x', '0') . ' = 1; return 8;';
			$fixtures[$flavor->name . '-dynamic-null-write'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bad)),
				'stdout_hex' => '', 'catch_runtime_error' => 'dynamic_requires_container'];
		}
		$probe = $prepare($decl($family, 'x', '8.75') . $infer('boxed', "2.0 + $x")
			. $infer('plain', "2.0 + (float)$x") . $infer('ordered', "$x < $x") . 'return 8;');
		$locals = $probe->file->main()->body()->statements;
		verify($locals[1]->type_def()->name === $family && $locals[2]->type_def()->name === 'float64'
			&& $locals[3]->type_def()->name === 'bool_t', 'Boxed operator result identity changed');
		verify(!$locals[0]->constant_known(), 'Boxed storage leaked a scalar constant fact');
		$src = $decl($family, 'x', '8.75') . $decl($family, 'copy', $x) . "$x = 2;"
			. $decl('int', 'converted', $var('copy')) . 'echo ' . $var('converted') . ';'
			. $decl($family, 'y', $var('copy') . ' + 2') . 'echo (int)' . $y . ';'
			. $decl($family, 'z', '2.0 + ' . $x) . 'echo (int)' . $var('z') . ';'
			. "if ($y > $x && 2 <= $x && $x < 3 && $y >= $x) { echo \"Y\"; }"
			. $decl('byte', 'b', '255') . "$x = " . $var('b') . "; echo (int)$x;"
			. "$x = true; echo (int)$x;" . $decl($family, 'nil', 'null')
			. $decl('null', 'n', $var('nil')) . "$x = \"a\\0b\"; echo (string)$x;"
			. "$y = \"a\\0c\"; if ($x < $y) { echo \"S\"; }"
			. "$x = -1.75;" . $decl('int', 'last', "$x + 2") . 'echo ' . $var('last') . ';'
			. "$x = 3; $y = 4; echo (int)($x + $y); return 8;";
		$entry = $prepare($src);
		$before = serialize($entry);
		$cpp = (new Cpp_Emitter())->emit($entry);
		verify($before === serialize($entry), 'Emitter mutated boxed facts');
		$fixtures[$flavor->name . '-' . $family] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("8104Y2551a\0bS07")];
		$literal = $decl($family, 'x', '8') . $infer('sum', "$x + 2") . $infer('less', "$x < 3")
			. 'echo (int)' . $var('sum') . ', (int)' . $var('less') . "; $x = 1; if ($x < 3) { echo 1; } return 8;";
		$cpp = (new Cpp_Emitter())->emit($prepare($literal));
		foreach (['add' => '2LL', 'less' => '3LL'] as $op => $rhs) {
			$args = "var_x_0, $rhs";
			verify(str_contains($cpp, "scpp::boxed_{$op}_{$family}_int64($args)"), 'Unexpected boxed literal operands');
		}
		$fixtures[$flavor->name . '-' . $family . '-literal'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('1001')];
		$param = match ($flavor) {
			flavor::cpp => "int read_box($family x)", flavor::php => 'function read_box(' . $family . ' $x): int',
			flavor::js => "function read_box(x: $family): int",
		};
		$main = $decl($family, 'x', '8.75') . "echo read_box($x), (int)$x, read_box(3); return 8;";
		$units = ['main' => new source_file('main', $main, $flavor),
			'lib' => new source_file('lib', $param . " { $x = $x + 1; return $x; }", $flavor)];
		$program = (new Program_Frontend())->prepare_sources('main', $units, new entry_target(31));
		$fixtures[$flavor->name . '-' . $family . '-parameter'] = ['files' => Cpp_Program::emit($program)->files,
			'stdout_hex' => bin2hex('984')];
		foreach (['add' => 'echo (int)(' . $x . ' + 2);', 'order' => 'if (2 < ' . $x . ') { echo 1; }',
			'less-literal' => 'if (' . $x . ' < 3) { echo 1; }', 'cast' => 'echo (int)' . $x . ';'] as $case => $operation) {
			$bad = $prepare($decl($family, 'x', '"8"') . $operation . 'return 8;');
			$fixtures[$flavor->name . '-' . $family . '-' . $case] = ['cpp' => (new Cpp_Emitter())->emit($bad),
				'stdout_hex' => '', 'catch_runtime_error' => 'boxed_scalar_kind'];
		}
		$bad = $prepare($decl($family, 'x', '1e100') . $decl('int', 'result', $x) . 'return 8;');
		$fixtures[$flavor->name . '-' . $family . '-overflow'] = ['cpp' => (new Cpp_Emitter())->emit($bad),
			'stdout_hex' => '', 'catch_out_of_range' => true];
		rejects(fn() => $prepare($decl($family, 'x', '1') . 'echo (int)(' . $x . ' + true);'), 'Unsupported boxed binary');
		rejects(fn() => $prepare($decl($family, 'x', '1') . $decl($family === 'mixed' ? 'dynamic' : 'mixed', 'y', $x)), 'Unsupported boxed conversion');
		$stable = $decl($family, 'x', '3') . $decl($family, 'y', '4') . $decl('int', 'n', '2')
			. "echo (int)($x + $y),(int)(2 + $x),(int)($x + " . $var('n') . "), (int)($x + $x);"
			. "if ($x < $y && $x <= 3 && $y > 2.0 && $y >= $x) { echo 1; } return 8;";
		$cpp = (new Cpp_Emitter())->emit($prepare($stable));
		verify(!str_contains($cpp, 'binary_left') && str_contains($cpp, "boxed_add_{$family}_{$family}(var_x_0, var_y_1)"),
			'Expected direct stable boxed calls');
		$fixtures[$flavor->name . '-' . $family . '-stable'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('75561')];

		$bad_pair = $decl($family, 'x', '1') . $decl($family, 'y', '[]') . $infer('sum', "$x + $y") . 'return 8;';
		$cpp = (new Cpp_Emitter())->emit($prepare($bad_pair));
		verify(str_contains($cpp, "boxed_add_{$family}_{$family}(var_x_0, var_y_1)"), 'Invalid payload must reach the direct helper');
		$fixtures[$flavor->name . '-' . $family . '-bad-pair'] = ['cpp' => $cpp, 'stdout_hex' => '', 'catch_runtime_error' => 'boxed_scalar_kind'];

		$replace = match ($flavor) {
			flavor::cpp => "int replace($family &x)", flavor::php => 'function replace(' . $family . ' &$x): int',
			flavor::js => "function replace(&x: $family): int",
		};
		$effects = $replace . " { $x = 20; echo \"R\"; return 2; }" . $decl($family, 'x', '8')
			. "echo (int)($x + replace($x)), (int)$x; $x = 8; echo (int)(replace($x) + $x); return 8;";
		$cpp = (new Cpp_Emitter())->emit($prepare($effects));
		verify(str_contains($cpp, 'const auto binary_left = var_x_0; const auto binary_right = replace_(var_x_0);')
			&& str_contains($cpp, 'const auto binary_left = replace_(var_x_0); const auto binary_right = var_x_0;'),
			'Effectful boxed operands require snapshots');
		$fixtures[$flavor->name . '-' . $family . '-mutating'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('R1020R22')];
	}
	foreach ([false, true] as $containers) {
		$directory = sys_get_temp_dir() . '/scpp-boxed-scalars-' . bin2hex(random_bytes(8));
		mkdir($directory);
		try {
			file_put_contents($directory . '/module.json', '{}');
			$main = $directory . '/main.' . $flavor->name;
			$library = $directory . '/lib.' . $flavor->name;
			file_put_contents($main, $containers ? $infer('x', 'number()') . $infer('y', $x)
				. $index('y', '"1"') . ' = 22; echo (int)' . $index('x', '1') . '; return 8;' : 'echo (int)(number() + 2); return 8;');
			$revision = time();
			$write = static function (?string $type, string $value = '') use ($library, &$revision, $function, $containers, $decl, $index, $x): void {
				if ($type === null) {
					unlink($library);
					return;
				}
				$body = $containers && $type !== 'bool' ? $decl($type, 'x', '[]') . $index('x', '1') . ' = ' . $value . "; return $x;"
					: 'return ' . $value . ';';
				file_put_contents($library, $function($type, 'number', $body));
				touch($library, ++$revision);
			};
			$compiler = new Project_Compiler();
			$target = new entry_target(31);
			$session = null;
			$root = null;
			foreach ([['mixed', '8', $containers ? '8' : '10'], ['dynamic', '9.75', $containers ? '22' : '11'], ['bool', 'true', null],
				['retry', '', null], [null, '', null], ['dynamic', '12.5', $containers ? '22' : '14'], ['mixed', '8', $containers ? '8' : '10']] as $step => [$type, $value, $expected]) {
				if ($type !== 'retry') {
					$write($type, $value);
					$session = $compiler->frontend($directory, $session);
				}
				$root ??= $session->entry->entry->ast;
				verify($session->entry->entry->ast === $root, 'Unchanged consumer AST replaced');
				if ($expected === null) {
					rejects(fn() => Incremental_Preparation::prepare($session, $target), $type === null ? 'Unknown function' : ($containers ? 'Keyed write requires' : 'Unsupported'));
					continue;
				}
				$stats = Incremental_Preparation::prepare($session, $target);
				if ($step === 1) {
					verify($stats['prepared'] === 2, 'Signature change did not reprepare boxed consumer');
				}
				$files = $session->files();
				$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files,
					new prepared_entry($root, $target), $session))->files;
				$sources = [];
				foreach ($files as $key => $file) {
					$sources[$key] = $file->source();
				}
				$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
				verify($incremental === $fresh, 'Incremental boxed output differs from fresh');
				$fixtures[$flavor->name . ($containers ? '-container-incremental-' : '-incremental-') . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex($expected)];
			}
			$session = $compiler->frontend($directory, $session);
			verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op lost prepared bodies');

		}
		finally {
			foreach (glob($directory . '/*') as $path) {
				unlink($path);
			}
			rmdir($directory);
		}
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Boxed scalar and container operations and incremental recovery passed\n";
