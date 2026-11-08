<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

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
		flavor::cpp => 'string s = "A\0é";', flavor::php => '$s string = "A\0é";', flavor::js => 'let s: string = "A\0é";',
	};
	$s = $flavor === flavor::php ? '$s' : 's';
	$byte = $flavor === flavor::php ? '$b' : 'b';
	$copy = match ($flavor) {
		flavor::cpp => "byte b = {$s}[2];", flavor::php => "$byte byte = {$s}[2];", flavor::js => "let b: byte = {$s}[2];",
	};
	$main = $local . $copy . "echo (int){$s}[0], (int){$s}[1], (int){$s}[3]; $s = " . '"z";'
		. "echo (int)$byte, " . '(int)"0123456789012345678901é"[23];';
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$offset = $function('int', 'offset', 'echo "K"; return 1;');
	$number = $function('int', 'number', 'echo "V"; return 255;');
	$main = $offset . $number . $main
		. "$s = " . '"abc";' . "{$s}[offset()] = number(); {$s}[0] = {$s}[1]; {$s}[2] = 0;"
		. "echo (int){$s}[0], (int){$s}[1], (int){$s}[2], strlen($s);"
		. "$s = " . '"01234567890123456789012";' . "{$s}[22] = 90; echo (int){$s}[22], strlen($s);";
	foreach (['copy' => '', 'ref' => '&'] as $mode => $ref) {
		$param = match ($flavor) {
			flavor::cpp => "string {$ref}s", flavor::php => "string {$ref}$s", flavor::js => "{$ref}s: string",
		};
		$header = $flavor === flavor::cpp ? "void change_$mode($param)" : "function change_$mode($param): void";
		$main = $header . "{{$s}[0] = 97; return;}" . $main;
	}
	$main .= "change_copy($s); echo (int){$s}[0]; change_ref($s); echo (int){$s}[0]; return 8;";
	$cpp = (new Cpp_Emitter())->emit($prepare($main));
	verify(str_contains($cpp, '.byte_at('), 'String indexing did not use the runtime binding');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('650169195169KV2552550390234897')];
	foreach (['negative' => '"abc"[-1]', 'end' => '"é"[2]', 'empty' => '""[0]', 'large' => '"abc"[9223372036854775807]'] as $name => $expr) {
		$fixtures["{$flavor->name}-$name"] = ['cpp' => (new Cpp_Emitter())->emit($prepare("echo (int)$expr; return 8;")),
			'stdout_hex' => '', 'catch_out_of_range' => 'sequence_index_out_of_range'];
	}
	foreach (['1.5', 'true', '"0"'] as $key) {
		rejects(fn() => $prepare($local . "echo (int){$s}[$key]; return 8;"), 'String index requires int64');
	}
	rejects(fn() => $prepare($local . "{$s}[0] = 256; return 8;"), 'outside uint8 range');
	foreach (['negative' => '-1', 'end' => '4', 'huge' => '9223372036854775807', 'empty' => '0'] as $name => $key) {
		$source = $local . ($name === 'empty' ? "$s = " . '"";' : '') . "{$s}[$key] = 9; return 8;";
		$fixtures["{$flavor->name}-store-$name"] = ['cpp' => (new Cpp_Emitter())->emit($prepare($source)),
			'stdout_hex' => '', 'catch_out_of_range' => 'sequence_index_out_of_range'];
	}
	rejects(fn() => $prepare($local . "{$s}[true] = 7; return 8;"), 'String index requires int64');
	$bad = $function('int', 'bad', 'return 256;') . $local . "{$s}[0] = bad(); return 8;";
	$fixtures["{$flavor->name}-store-overflow"] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bad)),
		'stdout_hex' => '', 'catch_out_of_range' => 'integer_cast_out_of_range'];
	rejects(fn() => $prepare($local . "{$s}[] = 8; return 8;"), 'Append requires');

	$signature = match ($flavor) {
		flavor::cpp => 'void consume(byte &v)', flavor::php => 'function consume(byte &$v): void',
		flavor::js => 'function consume(&v: byte): void',
	};
	rejects(fn() => $prepare($signature . '{return;}' . $local . "consume({$s}[0]); return 8;"), 'Reference argument requires stable');

	$dir = sys_get_temp_dir() . '/scpp-string-index-' . bin2hex(random_bytes(8));
	mkdir($dir);
	try {
		$lib = "$dir/lib." . $flavor->name;
		file_put_contents("$dir/module.json", '{}');
		file_put_contents("$dir/main." . $flavor->name, $local . "{$s}[0] = word()[0]; {$s}[1] = replacement(); echo (int){$s}[0], (int){$s}[1]; return 8;");
		$word = $function('string', 'word', 'echo "X"; return "é中😀";');
		$replacement = $function('int', 'replacement', 'return 255;');
		$valid = $word . $replacement;
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$revision = time();
		foreach ([$valid, $function('int', 'word', 'return 2;') . $replacement, 'retry', null,
			$function('string', 'word', 'return "B";') . $replacement, $word . $function('string', 'replacement', 'return "X";'), $valid] as $step => $body) {
			if ($body !== 'retry') {
				if ($body === null) {
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
			if (in_array($step, [1, 2, 3, 5], true)) {
				$error = match ($step) {
					3 => 'Unknown function', 5 => 'conversion', default => 'Indexed read requires',
				};
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $error);
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$code = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = array_map(static fn($file) => $file->source(), $files);
			$fresh = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
			verify($code === Cpp_Program::emit($fresh)->files, 'Incremental and fresh string index lowering differ');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex($step === 4 ? '66255' : 'X195255')];
		}
	}
	finally {
		foreach (glob($dir . '/*') as $file) {
			unlink($file);
		}
		rmdir($dir);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
}
echo "String indexed reads and writes, conversions, bounds and incremental recovery passed\n";
