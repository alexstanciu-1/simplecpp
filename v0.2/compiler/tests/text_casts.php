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
	$var = static fn(string $n) => ($flavor === flavor::php ? '$' : '') . $n;
	$decl = static fn(string $type, string $n, string $v) => match ($flavor) {
		flavor::php => '$' . "$n $type = $v;", flavor::cpp => "$type $n = $v;", flavor::js => "let $n: $type = $v;",
	};
	$main = $decl('byte', 'b', '255') . 'echo (string)true, (string)false, (string)' . $var('b')
		. ', (string)-42, (string)(-9223372036854775807 - 1), (string)9223372036854775807, (string)(-0.0), (string)0.1, (string)(1.0/3.0), (string)"a\0b";';
	$expected = "truefalse255-42-92233720368547758089223372036854775807-00.10.3333333333333333a\0b";
	foreach (['mixed', 'dynamic'] as $type) {
		$x = $var($type . '_value');
		$main .= $decl($type, $type . '_value', 'false') . "echo (string)$x;";
		foreach (['true', '0', $var('b'), '-0.0', '0.1', '"a\0b"'] as $v) {
			$main .= "$x = $v; echo (string)$x;";
		}
		$expected .= "falsetrue0255-00.1a\0b";
		foreach (['null', '[]'] as $i => $v) {
			$bad = $decl($type, $type . '_value', $v) . "echo (string)$x; return 8;";
			$fixtures["{$flavor->name}-$type-invalid-$i"] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bad)),
				'stdout_hex' => '', 'catch_runtime_error' => 'boxed_scalar_kind'];
		}
		$bad = $decl($type, $type . '_value', 'false') . $decl('string', 'text', $x) . 'return 8;';
		$fixtures["{$flavor->name}-$type-implicit"] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bad)),
			'stdout_hex' => '', 'catch_runtime_error' => 'boxed_scalar_kind'];
	}
	$fixtures[$flavor->name] = ['cpp' => (new Cpp_Emitter())->emit($prepare($main . 'return 8;')), 'stdout_hex' => bin2hex($expected)];
	rejects(fn() => $prepare($decl('string', 's', 'false') . 'return 8;'), 'conversion');
	rejects(fn() => $prepare('echo (string)null; return 8;'), 'conversion');
	foreach (['float', 'mixed', 'dynamic'] as $type) {
		$huge = $var('huge');
		foreach (['infinity' => $huge, 'nan' => "$huge - $huge"] as $kind => $value) {
			$bad = $decl('float', 'huge', '1e300 * 1e300') . $decl($type, 'x', $value) . 'echo (string)' . $var('x') . '; return 8;';
			$fixtures["{$flavor->name}-$type-$kind"] = ['cpp' => (new Cpp_Emitter())->emit($prepare($bad)),
				'stdout_hex' => '', 'catch_out_of_range' => 'nonfinite_decimal_text'];
		}
	}
	$dir = sys_get_temp_dir() . '/scpp-text-cast-' . bin2hex(random_bytes(8));
	mkdir($dir);
	try {
		$lib = "$dir/lib." . $flavor->name;
		file_put_contents("$dir/module.json", '{}');
		file_put_contents("$dir/main." . $flavor->name, 'echo (string)word(); return 8;');
		$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
			? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
		$valid = $function('bool', 'word', 'echo "X"; return false;');
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$revision = time();
		foreach ([$valid, $function('float', 'word', 'return 0.1;'), $function('null', 'word', 'return null;'),
			'retry', null, $function('mixed', 'word', 'return 8;'), $function('dynamic', 'word', 'return true;'), $valid] as $step => $body) {
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
			if (in_array($step, [2, 3, 4], true)) {
				$error = $step === 4 ? 'Unknown function' : 'conversion';
				rejects(fn() => Incremental_Preparation::prepare($session, $target), $error);
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$code = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = array_map(static fn($file) => $file->source(), $files);
			$fresh = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
			verify($code === Cpp_Program::emit($fresh)->files, 'Incremental and fresh text cast lowering differ');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex([0 => 'Xfalse', 1 => '0.1', 5 => '8', 6 => 'true', 7 => 'Xfalse'][$step])];
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
echo "Explicit scalar and boxed text casts, admission and incremental recovery passed\n";
