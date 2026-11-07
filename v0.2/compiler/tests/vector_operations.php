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
		[$decl('int', 'values', '1') . "$v" . '[] = 1;', 'Append requires a vector'],
		[$decl('int', 'values', '1') . 'echo ' . "$v" . '[0];', 'Indexed read requires a vector'],
		[$decl('vector<int>', 'values', '[]') . "$v" . '[0] = 2;', 'indexed writes are deferred'],
		[$decl('vector<int>', 'values', '[]') . $decl('vector<int>', 'copy', $v), 'Vector value operations are deferred'],
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
		rejects(fn() => $prepare($signature . $decl('vector<int>', 'values', '[]') . "consume($v); return 8;"), 'Vector argument passing is deferred');
	}
	foreach (['empty' => '', 'negative' => "$v" . '[] = 8;', 'end' => "$v" . '[] = 8;'] as $case => $append) {
		$index = $case === 'negative' ? '-1' : ($case === 'end' ? '1' : '0');
		$bad = $prepare($decl('vector<int>', 'values', '[]') . $append . 'echo ' . "$v" . "[$index]; return 8;");
		$fixtures[$flavor->name . '-' . $case] = ['cpp' => (new Cpp_Emitter())->emit($bad),
			'stdout_hex' => '', 'catch_out_of_range' => true];
	}

	$directory = sys_get_temp_dir() . '/scpp-vector-operations-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/lib.' . $flavor->name;
		file_put_contents($main, $decl('vector<int>', 'values', '[]') . "$v" . '[] = number(); echo ' . "$v" . '[0]; return 8;');
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
			file_put_contents($main, $decl('vector<' . $element . '>', 'values', '[]') . "$v" . '[] = ' . $value
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
echo "Vector empty construction, checked append, value-copy indexing and incremental recovery passed\n";
