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

// Only the generated dynamic handler owns the direct-spelling exception.
foreach ([scpp\compiler\type_dynamic::class => scpp\compiler\type_dynamic::class,
	scpp\compiler\type_mixed::class => scpp\compiler\type_definition::class] as $type => $owner) {
	$method = new ReflectionMethod($type, 'cpp_direct_binary');
	verify($method->getDeclaringClass()->getName() === $owner, "$type direct binary lowering owner");
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
		foreach (['true', '1.5', 'null', $x] as $key) {
			rejects(fn() => $prepare($decl($family, 'x', '[]') . $index('x', $key) . ' = 1;'), 'key requires int or string');
		}
		$bad = $decl($family, 'x', 'null') . $index('x', '"2147483648"') . ' = (int)' . $x . '; return 8;';
		$fixtures[$flavor->name . '-' . $family . '-key-first'] = [
			'cpp' => (new Cpp_Emitter())->emit($prepare($bad)), 'stdout_hex' => '', 'catch_out_of_range' => true];
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
			$args = $family === 'dynamic' ? "var_x_0, $rhs" : 'binary_left, binary_right';
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
	}
	foreach (["2 + $x", "$x + " . $var('n'), "$x + 2.0", "$x >= 3"] as $expression) {
		$source = $decl('dynamic', 'x', '8') . $decl('int', 'n', '2') . $infer('probe', $expression);
		$cpp = (new Cpp_Emitter())->emit($prepare($source . 'return 8;'));
		verify(str_contains($cpp, 'const auto binary_left'), 'Unexpected direct boxed lowering for ' . $expression);
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
