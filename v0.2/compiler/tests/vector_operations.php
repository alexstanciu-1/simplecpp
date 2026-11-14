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
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('vectors', $text, $flavor), new entry_target(31));
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$v = $var('values');
	$s = $var('strings');
	$code = $function('float', 'number', 'return 9.75;') . $decl('vector<int>', 'values', '[]')
		. "$v" . '[] = 8;' . "$v" . '[] = true;' . "$v" . '[] = number();'
		. 'echo ' . "$v" . '[0], ' . "$v" . '[1], ' . "$v" . '[2];'
		. $decl('vector<string>', 'strings', '[]') . "$s" . '[] = "a\\0b";'
		. $decl('string', 'copy', "$s" . '[0]') . $var('copy') . ' = "changed";'
		. "$s" . '[] = ' . "$s" . '[0];' . 'echo ' . "$s" . '[0], ' . "$s" . '[1];'
		. $decl('int', 'i', '0') . 'while (' . $var('i') . ' < 2) {' . "$v" . '[] = 4; ' . $var('i') . '++;}'
		. 'echo ' . "$v" . '[3] + ' . "$v" . '[4]; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering changed prepared facts');
	verify(str_contains($cpp, 'scpp::checked_float64_to_int64('), 'Append skipped generated checked conversion');
	verify(str_contains($cpp, 'scpp::vector_int64{}') && str_contains($cpp, 'scpp::vector_string{}'), 'Empty construction did not use exports');
	verify(!str_contains($cpp, 'std::vector') && !str_contains($cpp, 'vector<'), 'Application contains vector templates');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("819a\0ba\0b8")];
	foreach ([
		[$decl('int', 'values', '[]'), 'explicitly typed local initializer'],
		[$decl('vector<int>', 'values', '[1]'), 'only empty typed collection'],
		[$decl('vector<int>', 'values', '[]') . "$v" . '[] = "no";', 'conversion'],
		[$decl('vector<string>', 'strings', '[]') . "$s" . '[] = 1;', 'conversion'],
		[$decl('vector<int>', 'values', '[]') . "$v" . '[] = 1e100;', 'outside'],
		[$decl('vector<int>', 'values', '[]') . 'echo ' . "$v" . '[1.0];', 'Vector index requires int64'],
		[$decl('int', 'values', '1') . "$v" . '[] = 1;', 'Append requires an appendable collection'],
		[$decl('int', 'values', '1') . 'echo ' . "$v" . '[0];', 'Indexed read requires a vector'],
		[$decl('vector<int>', 'values', '[]') . "$v" . '[false] = 2;', 'Vector index requires int64'],
		[$decl('vector<int>', 'values', '[]') . "$v" . '[0] = "bad";', 'conversion'],
		[$decl('vector<string>', 'strings', '[]') . "$s" . '[0] = 1;', 'conversion'],
		[$decl('vector<int>', 'values', '[]') . $decl('vector<string>', 'copy', $v), 'conversion'],
		['echo [];', 'explicitly typed local initializer'],
	] as [$text, $message]) {
		rejects(fn() => $prepare($text . 'return 8;'), $message);
	}
	$overflow = $prepare($function('float', 'huge', 'return 1e100;') . $decl('vector<int>', 'values', '[]')
		. "$v" . '[] = huge(); return 8;');
	$fixtures[$flavor->name . '-conversion-overflow'] = ['cpp' => (new Cpp_Emitter())->emit($overflow),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	foreach (['', '&'] as $reference) {
		$parameter = match ($flavor) {
			flavor::cpp => 'vector<int> ' . $reference . 'argument',
			flavor::php => 'vector<int> ' . $reference . '$argument',
			flavor::js => $reference . 'argument: vector<int>',
		};
		$signature = ($flavor === flavor::cpp ? "void consume($parameter)" : "function consume($parameter): void") . '{return;}';
		if ($reference === '&') rejects(fn() => $prepare($signature . $decl('vector<int>', 'values', '[]') . "consume($v); return 8;"), 'Writable collection parameters are deferred');
		else $prepare($signature . $decl('vector<int>', 'values', '[]') . "consume($v); return 8;");
	}
	foreach (['empty' => '', 'negative' => "$v" . '[] = 8;', 'end' => "$v" . '[] = 8;'] as $case => $append) {
		$index = $case === 'negative' ? '-1' : ($case === 'end' ? '1' : '0');
		$bad = $prepare($decl('vector<int>', 'values', '[]') . $append . 'echo ' . "$v" . "[$index]; return 8;");
		$fixtures[$flavor->name . '-' . $case] = ['cpp' => (new Cpp_Emitter())->emit($bad),
			'stdout_hex' => '', 'catch_out_of_range' => true];
	}

	// Catch at the append site so native code can inspect the vector after a rejected value.
	foreach (['overflow' => ['huge()', 'std::out_of_range'], 'empty' => [$var('item'), 'std::runtime_error']] as $case => [$rhs, $error]) {
		$source = $function('float', 'huge', 'return 1e100;') . $decl('nullable<int>', 'item', 'null')
			. $decl('vector<int>', 'values', '[]') . $v . '[] = 7;' . $v . "[] = $rhs; return 91;";
		$cpp = (new Cpp_Emitter())->emit($prepare($source));
		$cpp = preg_replace('/(\t\(var_values_1\)\.append\([^\n]+\n)(\treturn )/',
			"\ttry {\n$1\t} catch (const $error&) { return var_values_1.size() == 1 && var_values_1.at(scpp::int64{0}) == 7 ? 8 : 92; }\n$2",
			$cpp, 1, $matches);
		verify($matches === 1, 'Missing final append for failure observation');
		$fixtures[$flavor->name . '-append-atomic-' . $case] = ['cpp' => "#include <stdexcept>\n" . $cpp, 'stdout_hex' => ''];
	}

	// Each argument executes once. The direct native call permits either argument order.
	$replace = $function('int', 'index', 'echo "!"; return 0;')
		. $function('float', 'rhs', 'echo "!"; return 9.75;')
		. $decl('vector<int>', 'values', '[]') . $v . '[] = 7;' . $v . '[] = 8;'
		. $v . '[index()] = rhs();' . $v . '[1] = false; echo ' . $v . '[0], ' . $v . '[1], count(' . $v . ');'
		. $decl('vector<string>', 'strings', '[]') . $s . '[] = "old";' . $s . '[] = "a\0b";'
		. $s . '[0] = ' . $s . '[1];' . $s . '[1] = "new"; echo ' . $s . '[0], ' . $s . '[1]; return 8;';
	$fixtures[$flavor->name . '-replace'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($replace)),
		'stdout_hex' => bin2hex("!!902a\0bnew")];

	$i = $var('i');
	$x = $var('x');
	$b = $var('flag');
	$forms = $decl('vector<int>', 'values', '[]') . $decl('int', 'i', '0') . $decl('int', 'x', '8')
		. $decl('bool', 'flag', 'true') . $v . '[] = 0;' . $v . "[$i] = $x;" . $v . "[0] = 9.75;" . $v . "[$i] = $b;"
		. 'echo ' . $v . '[0]; return 8;';
	$cpp = (new Cpp_Emitter())->emit($prepare($forms));
	verify(str_contains($cpp, 'var_values_0.set(var_i_1, var_x_2);'), 'Exact native locals need a direct call');
	verify(preg_match('/var_values_0\.set\(0(L{0,2}), 9\1\);/', $cpp) === 1, 'Expected exact typed, folded literals');
	verify(str_contains($cpp, 'var_values_0.set(var_i_1, static_cast<scpp::int64>(var_flag_3));'), 'Strict overload requires bool conversion');
	verify(str_contains($fixtures[$flavor->name . '-replace']['cpp'], 'scpp::checked_float64_to_int64(rhs_('),
		'Unknown float must use the checked helper directly');
	$fixtures[$flavor->name . '-native-arguments'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('1')];

	$forms = $decl('vector<int>', 'values', '[]') . $decl('int', 'i', '0') . $decl('int', 'x', '8')
		. $v . "[] = $x; " . $v . "[] = $x <=> $i; echo " . $v . "[$i], " . $v . "[$i + 1]; return 8;";
	$cpp = (new Cpp_Emitter())->emit($prepare($forms));
	verify(str_contains($cpp, '.append(var_x_2)') && str_contains($cpp, '.at(var_i_1)')
		&& str_contains($cpp, '.append(static_cast<scpp::int64>(((var_x_2 > var_i_1)) - ((var_x_2 < var_i_1))))'),
		'Read and append must reuse exact argument representations');
	$fixtures[$flavor->name . '-read-append-arguments'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('81')];

	// Source catch is deferred. Catch around the emitted store while its destination is still in scope.
	foreach (['empty' => [0, 0, '2'], 'negative' => [2, -1, '2'], 'end' => [2, 2, '2'],
		'conversion' => [2, 1, '1e100'], 'conversion-before-bounds' => [2, 2, '1e100'], 'failing-index' => [2, 2, '2']] as $case => [$size, $index, $rhs]) {
		$key = $case === 'failing-index' ? $v . '[index()]' : 'index()';
		$source = $function('int', 'index', 'return ' . $index . ';')
			. $function('float', 'rhs', 'return ' . $rhs . ';') . $decl('vector<int>', 'values', '[]')
			. ($size === 0 ? '' : $v . '[] = 7;' . $v . '[] = 8;') . $v . '[' . $key . '] = rhs(); return 91;';
		$cpp = (new Cpp_Emitter())->emit($prepare($source));
		$unchanged = 'var_values_0.size() == ' . $size;
		if ($size > 0) {
			$unchanged .= ' && var_values_0.at(scpp::int64{0}) == 7 && var_values_0.at(scpp::int64{1}) == 8';
		}
		$reason = $rhs === '1e100' ? 'integer_cast_out_of_range' : 'sequence_index_out_of_range';
		$catch = "\t} catch (const std::out_of_range& error) {\n"
			. "\t\treturn std::string_view(error.what()) == \"$reason\" && $unchanged ? 8 : 92;\n\t}\n";
		$cpp = preg_replace('/(\tvar_values_0\.set\([^\n]+\n)/', "\ttry {\n$1" . $catch, $cpp, 1, $matches);
		verify($matches === 1, 'Missing vector replacement for failure observation');
		$fixtures[$flavor->name . '-replace-' . $case] = ['cpp' => "#include <stdexcept>\n#include <string_view>\n" . $cpp,
			'stdout_hex' => ''];
	}

	$directory = sys_get_temp_dir() . '/scpp-vector-operations-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/lib.' . $flavor->name;
		file_put_contents($main, $decl('vector<int>', 'values', '[]') . $v . '[] = number();' . $v . '[0] = number(); echo ' . $v . '[0]; return 8;');
		$revision = time();
		$write = static function (?string $type, string $value = '') use ($library, &$revision, $function): void {
			if ($type === null) {
				unlink($library);
				return;
			}
			file_put_contents($library, $function($type, 'number', 'return ' . $value . ';'));
			touch($library, ++$revision);
		};
		$compiler = new Project_Compiler();
		$target = new entry_target(31);
		$session = null;
		$root = null;
		foreach ([['int', '8', '8'], ['float', '9.75', '9'], ['string', '"bad"', null],
			['retry', '', null], [null, '', null], ['float', '12.5', '12'], ['int', '8', '8']] as $step => [$type, $value, $expected]) {
			if ($type !== 'retry') {
				$write($type, $value);
				$session = $compiler->frontend($directory, $session);
			}
			$root ??= $session->entry->entry->ast;
			verify($session->entry->entry->ast === $root, 'Unchanged consumer AST replaced');
			if ($expected === null) {
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $type === null ? 'Unknown function' : 'conversion');
				continue;
			}
			$stats = Incremental_Preparation::prepare($session, $target);
			if ($step === 1) {
				verify($stats['prepared'] === 2, 'Signature change did not reprepare vector consumer');
			}
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files,
				new prepared_entry($root, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Incremental vector output differs from fresh');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex($expected)];
		}
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op lost prepared bodies');
		$library_ast = $session->scope->entry('func:number')->declaration();
		foreach ([['string', '"text"', 'text'], ['string', '1', null], ['int', '18', '18']] as $step => [$element, $value, $expected]) {
			file_put_contents($main, $decl('vector<' . $element . '>', 'values', '[]') . $v . '[] = ' . ($element === 'string' ? '""' : '0') . ';' . $v . '[0] = ' . $value
				. '; echo ' . "$v" . '[0]; return 8;');
			touch($main, ++$revision);
			$session = $compiler->frontend($directory, $session);
			verify($session->scope->entry('func:number')->declaration() === $library_ast, 'Local type edit replaced unchanged library');
			if ($expected === null) {
				rejects(fn() => Incremental_Preparation::prepare($session, $target), 'conversion');
				rejects(fn() => Incremental_Preparation::prepare($session, $target), 'conversion');
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files,
				new prepared_entry($session->entry->entry->ast, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Local element-type recovery differs from fresh');
			$fixtures[$flavor->name . '-local-type-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex($expected)];
		}
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Vector construction, append, indexed reads and replacements, and incremental recovery passed\n";
