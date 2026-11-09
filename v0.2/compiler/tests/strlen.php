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
		flavor::cpp => 'string s = "é中😀";', flavor::php => '$s string = "é中😀";', flavor::js => 'let s: string = "é中😀";',
	};
	$s = $flavor === flavor::php ? '$s' : 's';
	$main = $local . 'echo strlen(""), strlen("a\0b"), strlen(' . $s . '); return 8;';
	$cpp = (new Cpp_Emitter())->emit($prepare($main));
	verify(str_contains($cpp, 'scpp::str::byte_length(var_s_0)'), 'Expected direct runtime call on owned string');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('039')];
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
		file_put_contents("$dir/main." . $flavor->name, 'echo strlen(word()); return 8;');
		$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
			? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
		$valid = $function('string', 'word', 'echo "X"; return "é中😀";');
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$revision = time();
		foreach ([$valid, $function('int', 'word', 'return 2;'), 'retry', null,
			$valid . $function('int', 'strlen', 'return 1;'), $valid, 'add_shadow', 'retry', 'remove_shadow'] as $step => $body) {
			if ($body !== 'retry') {
				if ($body === 'add_shadow') {
					file_put_contents("$dir/shadow." . $flavor->name, $function('int', 'strlen', 'return 1;'));
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
			if (in_array($step, [1, 2, 3, 4, 6, 7], true)) {
				$error = match ($step) {
					3 => 'Unknown function', 4, 6, 7 => 'Argument count', default => 'requires string',
				};
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $error);
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$code = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = array_map(static fn($file) => $file->source(), $files);
			$fresh = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
			verify($code === Cpp_Program::emit($fresh)->files, 'Incremental and fresh strlen lowering differ');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex('X9')];
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
echo "strlen byte lengths, admission and incremental recovery passed\n";
