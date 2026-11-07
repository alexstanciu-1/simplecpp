<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Cpp_Program, Program_Frontend, prepared_program, prepared_entry,
	value_conversion, carrier_type_definition};

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

/** Require a source diagnostic, rather than an internal failure or accidental C++ admission. */
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

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$decl = static fn(string $type, string $name, string $value) => declaration($flavor, $type, $name, $value);
	$var = static fn(string $name) => ($flavor === flavor::php ? '$' : '') . $name;
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('carriers', $text, $flavor), new entry_target(31));
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$parameter = match ($flavor) {
		flavor::cpp => 'int consume(nullable<int> value = 4)',
		flavor::php => 'function consume(nullable<int> $value = 4): int',
		flavor::js => 'function consume(value: nullable<int> = 4): int',
	};
	$code = $parameter . '{return ' . $var('value') . ';}'
		. $function('float', 'number', 'return 12.75;')
		. $function('nullable<int>', 'wrapped', 'return number();')
		. $decl('?int', 'a', 'wrapped()') . $decl('result<int>', 'b', $var('a'))
		. $decl('result_or_false<int>', 'c', $var('b')) . $decl('result_or_bool<int>', 'd', $var('c'))
		. 'echo ' . $var('a') . ' + 5, "|", -' . $var('b') . ', "|", ' . $var('c') . ' * 2, "|", '
		. '(int)(' . $var('d') . ' > 10), "|", consume(' . $var('d') . '), "|", consume(), "|";'
		. $decl('byte', 'small', $var('a')) . 'echo (int)' . $var('small') . ', "|";'
		. $decl('nullable<string>', 'text', '"a\\0b"') . $decl('string', 'copy', $var('text'))
		. $var('copy') . ' = "changed";'
		. 'echo (int)(' . $var('text') . ' == "a\\0b"), "|";'
		. $decl('string', 'bytes', $var('text')) . 'echo ' . $var('bytes') . ', "|";'
		. $decl('result_or_false<bool>', 'boolean', 'false') . $decl('bool', 'plain', $var('boolean'))
		. 'echo (int)' . $var('plain') . ', "|";'
		. $decl('nullable<int>', 'empty', 'null') . $var('empty') . ' = 7;'
		. 'echo ' . $var('empty') . ' + 1, "|", 1 + ' . $var('a') . ', "|", ' . $var('a') . ' + ' . $var('b') . '; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Lowering mutated prepared facts');
	verify(str_contains($cpp, 'scpp::checked_float64_to_int64('), 'Dynamic wrap skipped checked payload conversion');
	verify(str_contains($cpp, '.value()'), 'Missing checked extraction');
	verify(!str_contains($cpp, 'std::optional') && !str_contains($cpp, 'std::variant'), 'Application templates leaked');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("17|-12|24|1|12|4|12|1|a\0b|0|8|13|24")];
	$presence = $decl('nullable<int>', 'none', 'null')
		. $decl('result_or_false<bool>', 'successful', 'false')
		. $decl('result_or_false<bool>', 'absent', 'false_sentinel')
		. $decl('result_or_bool<int>', 'truth', 'true_sentinel')
		. 'echo (int)isset(' . $var('none') . '), (int)isset(' . $var('successful') . '), (int)isset('
		. $var('absent') . '), (int)isset(' . $var('truth') . ');'
		. 'if (' . $var('none') . ') {echo "bad";} if (' . $var('successful') . ') {echo "bad";}'
		. 'if (' . $var('truth') . ') {echo "yes";}'
		. 'echo (int)!' . $var('absent') . ';'
		. $function('nullable<int>', 'once', 'echo "once"; return 1;')
		. 'echo (int)isset(once()); if (once()) {echo "ok";}'
		. 'if (' . $var('none') . ' && once()) {echo "bad";} return 8;';
	$fixtures[$flavor->name . '-presence'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($presence)),
		'stdout_hex' => bin2hex('0101yes1once1onceok')];
	$vector = $decl('vector<string>', 'items', '[]') . $var('items') . '[] = "first";'
		. $decl('nullable<vector<string>>', 'packed', $var('items'))
		. $decl('vector<string>', 'copy', $var('packed')) . $var('copy') . '[] = "second";'
		. 'echo ' . $var('items') . '[0], ' . $var('copy') . '[1]; return 8;';
	$fixtures[$flavor->name . '-vector'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($vector)),
		'stdout_hex' => bin2hex('firstsecond')];
	$reference = match ($flavor) {
		flavor::cpp => 'void change(int &value)',
		flavor::php => 'function change(int &$value): void',
		flavor::js => 'function change(&value: int): void',
	};
	// A payload extraction is a value copy, never an exact-storage reference binding.
	rejects(fn() => $prepare($reference . '{return;}' . $decl('nullable<int>', 'item', '8')
		. 'change(' . $var('item') . '); return 8;'), 'exact storage type');
	$single = $prepare($decl('nullable<int>', 'item', '12.75') . 'return 8;');
	$local = $single->file->main()->body()->statements[0];
	verify($local->initialization() === (value_conversion::floating_integer_proven | value_conversion::WRAP), 'Packed wrap code mismatch');
	verify(!$local->constant_known(), 'Carrier state leaked into scalar constants');
	foreach ([['nullable<int>', 'null'], ['result_or_false<int>', 'false_sentinel'], ['result_or_bool<int>', 'true_sentinel']] as [$type, $state]) {
		$failed = $prepare($decl($type, 'item', $state) . 'echo ' . $var('item') . ' + 1; return 8;');
		$fixtures[$flavor->name . '-' . $state] = ['cpp' => (new Cpp_Emitter())->emit($failed),
			'stdout_hex' => '', 'catch_runtime_error' => 'carrier_no_value'];
	}
	$overflow = $function('float', 'huge', 'return 1e100;')
		. $decl('nullable<int>', 'item', 'huge()') . 'return 8;';
	$fixtures[$flavor->name . '-overflow'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($overflow)),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	foreach ([
		[$decl('result_or_false<int>', 'bad', 'null'), 'Null cannot construct'],
		[$decl('nullable<int>', 'bad', 'false_sentinel'), 'Sentinel is not supported'],
		[$decl('result_or_false<int>', 'bad', 'true_sentinel'), 'Sentinel is not supported'],
		[$decl('nullable<int>', 'bad', '"text"'), 'conversion'],
		[$decl('nullable<int>', 'bad', '8') . $var('bad') . ' = 12.75;', 'conversion'],
		[$decl('nullable<int>', 'bad', '1e100'), 'outside int64 range'],
	] as [$text, $message]) {
		rejects(fn() => $prepare($text . 'return 8;'), $message);
	}
	$directory = sys_get_temp_dir() . '/scpp-carrier-values-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$library = $directory . '/library.' . $flavor->name;
		file_put_contents($main, 'echo (int)isset(provide()), provide() + 5; return 8;');
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$slot = null;
		$target = new entry_target(31);
		$revision = time();
		$steps = [['nullable<int>', '12'], ['result<int>', '13.75'], ['nullable<string>', '"wrong"'],
			['retry', ''], ['result_or_false<int>', '14'], ['delete', ''], ['nullable<int64>', '15']];
		foreach ($steps as $step => [$type, $value]) {
			if ($type !== 'retry') {
				if ($type === 'delete') {
					unlink($library);
				}
				else {
					file_put_contents($library, $function($type, 'provide', 'return ' . $value . ';'));
					touch($library, ++$revision);
				}
				$session = $compiler->frontend($directory, $session);
			}
			$root ??= $session->entry->entry->ast;
			$slot ??= $session->scope->entry('func:provide');
			verify($root === $session->entry->entry->ast, 'Unchanged consumer AST replaced');
			verify($slot === $session->scope->entry('func:provide'), 'Stable symbol replaced');
			if (in_array($type, ['nullable<string>', 'retry', 'delete'], true)) {
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $type === 'delete' ? 'Unknown function' : 'Unsupported');
				continue;
			}
			$stats = Incremental_Preparation::prepare($session, $target);
			verify($stats['prepared'] === 2, 'Changed contract did not prepare observing body');
			$files = $session->files();
			$incremental = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = [];
			foreach ($files as $key => $file) {
				$sources[$key] = $file->source();
			}
			$fresh = Cpp_Program::emit((new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target))->files;
			verify($incremental === $fresh, 'Incremental differs from fresh');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $incremental, 'stdout_hex' => bin2hex('1' . (string)((int)(float)$value + 5))];
		}
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op failed to reuse bodies');
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
echo "Packed carrier conversions, payload operators and incremental recovery passed\n";
