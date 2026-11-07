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
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('hashes', $text, $flavor), new entry_target(31));
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$v = $var('values');
	$code = '';
	$expected = '';
	foreach (['int', 'string'] as $key_type) {
		foreach (['int', 'string'] as $value_type) {
			$name = $key_type . '_' . $value_type;
			$h = $var($name);
			$key = $key_type === 'int' ? '-9' : '"a\\0b"';
			$value = $value_type === 'int' ? '9.75' : '"v\\0x"';
			$code .= $decl("hash<$value_type,$key_type>", $name, '[]') . "$h" . "[$key] = $value;"
				. 'echo ' . "$h" . "[$key];" . "$h" . "[$key] = $value;";
			$expected .= $value_type === 'int' ? '9' : "v\0x";
		}
	}
	$code .= $decl('hash<int,string>', 'values', '[]') . "$v" . '["01"] = 1;' . "$v" . '["1"] = true;'
		. "$v" . '["1"] = 2; echo ' . "$v" . '["01"], ' . "$v" . '["1"];'
		. $decl('hash<string,int>', 'strings', '[]') . $var('strings') . '[0] = "original";'
		. $decl('string', 'copy', $var('strings') . '[0]') . $var('copy') . ' = "changed";'
		. 'echo ' . $var('strings') . '[0]; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering changed prepared facts');
	verify(!str_contains($cpp, 'std::unordered_map') && !str_contains($cpp, 'hash<'), 'Application contains hash templates');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . '12original')];
	foreach ([
		[$decl('hash<int,int>', 'values', '[]') . "$v" . '[true] = 1;', 'Hash key requires int64'],
		[$decl('hash<int,int>', 'values', '[]') . 'echo ' . "$v" . '[1.0];', 'Hash key requires int64'],
		[$decl('hash<int,string>', 'values', '[]') . "$v" . '[1] = 1;', 'Hash key requires string'],
		[$decl('hash<int,int>', 'values', '[]') . "$v" . '[null] = 1;', 'Hash key requires int64'],
		[$decl('hash<int,int>', 'values', '[]') . "$v" . '[1] = "no";', 'conversion'],
		[$decl('hash<string,int>', 'values', '[]') . "$v" . '[1] = 1;', 'conversion'],
		[$decl('hash<int,int>', 'values', '[1]'), 'only empty typed collection'],
		[$decl('hash<int,int>', 'values', '[]') . "$v" . '[] = 1;', 'Append requires a vector'],
		[$decl('hash<int,int>', 'values', '[]') . $decl('hash<int,int>', 'copy', $v), 'Hash value operations are deferred'],
	] as [$text, $message]) {
		rejects(fn() => $prepare($text . 'return 8;'), $message);
	}
	foreach (['missing' => 'echo ' . "$v" . '[0];', 'overflow' => "$v" . '[0] = huge();'] as $case => $operation) {
		$bad = $prepare($function('float', 'huge', 'return 1e100;') . $decl('hash<int,int>', 'values', '[]') . $operation . 'return 8;');
		$fixtures[$flavor->name . '-' . $case] = ['cpp' => (new Cpp_Emitter())->emit($bad),
			'stdout_hex' => '', 'catch_out_of_range' => true];
	}
	$parameter = match ($flavor) {
		flavor::cpp => 'int &key', flavor::php => 'int &$key', flavor::js => '&key: int',
	};
	$mutator = ($flavor === flavor::cpp ? "int mutate($parameter)" : "function mutate($parameter): int")
		. '{' . $var('key') . ' = 2; return 7;}';
	$order = $prepare($mutator . $decl('hash<int,int>', 'values', '[]') . $decl('int', 'key', '1')
		. "$v" . '[' . $var('key') . '] = mutate(' . $var('key') . '); echo ' . "$v" . '[1]; return 8;');
	$fixtures[$flavor->name . '-key-order'] = ['cpp' => (new Cpp_Emitter())->emit($order), 'stdout_hex' => bin2hex('7')];

	$wrapped = $prepare($decl('hash<int,int>', 'values', '[]') . $decl('nullable<int>', 'item', '13')
		. "$v" . '[0] = ' . $var('item') . '; echo ' . "$v" . '[0]; return 8;');
	$fixtures[$flavor->name . '-carrier'] = ['cpp' => (new Cpp_Emitter())->emit($wrapped), 'stdout_hex' => bin2hex('13')];
	$empty = $prepare($decl('hash<int,int>', 'values', '[]') . $decl('nullable<int>', 'item', 'null')
		. "$v" . '[0] = ' . $var('item') . '; return 8;');
	$fixtures[$flavor->name . '-empty-carrier'] = ['cpp' => (new Cpp_Emitter())->emit($empty),
		'stdout_hex' => '', 'catch_runtime_error' => 'carrier_no_value'];
	// Test-only exception observation verifies conversion failure leaves the hash empty; source catch is deferred.
	$atomic = (new Cpp_Emitter())->emit($prepare($function('float', 'huge', 'return 1e100;')
		. $decl('hash<int,int>', 'values', '[]') . "$v" . '[0] = huge(); return 8;'));
	$atomic = preg_replace('/(\t\{ const auto hash_key = [^\n]+\n)/',
		"\ttry {\n$1\t} catch (const std::out_of_range&) { return local_0.size() == 0 ? 8 : 91; }\n\treturn 92;\n", $atomic, 1, $count);
	verify($count === 1, 'Missing keyed write for atomicity observation');
	$fixtures[$flavor->name . '-conversion-atomicity'] = ['cpp' => "#include <stdexcept>\n" . $atomic, 'stdout_hex' => ''];

	$directory = sys_get_temp_dir() . '/scpp-hash-operations-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/lib.' . $flavor->name;
		file_put_contents($main, $decl('hash<int,int>', 'values', '[]') . "$v" . '[0] = number(); echo ' . "$v" . '[0]; return 8;');
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
				verify($stats['prepared'] === 2, 'Signature change did not reprepare hash consumer');
			}
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files,
				new prepared_entry($root, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Incremental hash output differs from fresh');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex($expected)];
		}
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op lost prepared bodies');
		$library_ast = $session->scope->entry('func:number')->declaration();
		foreach ([['string', '"text"', 'text'], ['string', '1', null], ['int', '18', '18']] as $step => [$element, $value, $expected]) {
			file_put_contents($main, $decl('hash<' . $element . ',int>', 'values', '[]') . "$v" . '[0] = ' . $value
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
echo "Hash empty construction, checked keyed writes, value-copy indexing and incremental recovery passed\n";
