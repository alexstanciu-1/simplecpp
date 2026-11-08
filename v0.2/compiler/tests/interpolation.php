<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
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
function rejects(Closure $action, string $message): string
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return $error->getMessage();
	}
	throw new RuntimeException('Expected rejection: ' . $message);
}

$fixtures = [];
$target = new entry_target(31);
foreach (flavor::cases() as $flavor) {
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('main', $source, $flavor), $target);
	$decl = static fn(string $name, string $type, string $v) => match ($flavor) {
		flavor::cpp => "$type $name = $v;", flavor::php => '$' . "$name $type = $v;", flavor::js => "let $name: $type = $v;",
	};
	$var = static fn(string $name) => ($flavor === flavor::php ? '$' : '') . $name;
	$field = static fn(string $name) => match ($flavor) {
		flavor::php => '{$' . $name . '}', flavor::js => '${' . $name . '}', flavor::cpp => '{' . $name . '}',
	};
	$quote = static fn(string $text) => match ($flavor) {
		flavor::php => '"' . $text . '"', flavor::js => '`' . $text . '`', flavor::cpp => 'f"' . $text . '"',
	};
	$param = match ($flavor) {
		flavor::cpp => 'string greet(string who)', flavor::php => 'function greet(string $who): string',
		flavor::js => 'function greet(who: string): string',
	};
	$main = $param . '{return ' . $quote('Hi ' . $field('who') . '!') . ';}'
		. $decl('who', 'string', '"é\0"') . $decl('i', 'int', '-12') . $decl('b', 'byte', '255')
		. $decl('f', 'float', '-0.0') . $decl('ok', 'bool', 'true')
		. $decl('snap', 'string', $quote(implode('|', array_map($field, ['who', 'i', 'b', 'f', 'ok', 'who']))))
		. $var('who') . ' = ' . $quote('[' . $field('who') . ']') . ';'
		. 'echo ' . $var('snap') . ', greet(' . $var('who') . ');';
	[$escape, $bytes] = match ($flavor) {
		flavor::php => ['"\$i $i/{$i}x"', '$i -12/-12x'],
		flavor::js => ['`\${i} \` ${i}x`', '${i} ` -12x'],
		flavor::cpp => ['f"{{i}} {{{i}}}"', '{i} {-12}'],
	};
	$main .= 'echo ' . $escape . '; return 8;';
	$entry = $prepare($main);
	$report = \scpp\compiler\Syntax_Inspection::file($entry->file);
	$prepared = \scpp\compiler\Compilation_Inspection::program($entry);
	verify(str_contains(json_encode($report, JSON_THROW_ON_ERROR), '"interpolated_string"')
		&& str_contains(json_encode($prepared, JSON_THROW_ON_ERROR), '"format_bool_t"'), 'Interpolation inspection missing');
	foreach ($entry->body()->references as $ref) {
		if ($ref instanceof \scpp\compiler\ast_interpolation_name) {
			verify(substr($main, $ref->byte_start(), $ref->byte_end() - $ref->byte_start()) === $ref->name, 'Name span mismatch');
		}
	}
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(str_contains($cpp, 'scpp::str::format_bool(') && str_contains($cpp, 'scpp::str::format_float64('), 'Expected generated casts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("é\0|-12|255|-0|true|é\0Hi [é\0]!" . $bytes)];
	foreach (['mixed' => '1', 'dynamic' => '1', 'null' => 'null', 'vector<int>' => '[]'] as $type => $value) {
		rejects(fn() => $prepare($decl('x', $type, $value) . 'echo ' . $quote($field('x')) . '; return 8;'), 'conversion');
	}
	$plain = match ($flavor) {
		flavor::php => "'\$missing'", flavor::js => '"${missing}"', flavor::cpp => '"{missing}"',
	};
	$fixtures[$flavor->name . '-plain'] = ['cpp' => (new Cpp_Emitter())->emit($prepare("echo $plain; return 8;")),
		'stdout_hex' => bin2hex(substr($plain, 1, -1))];
	$single = $decl('v', 'string', '"abc"') . $decl('copy', 'string', $quote($field('v')))
		. $var('v') . ' = "changed"; echo ' . $var('copy') . '; return 8;';
	$fixtures[$flavor->name . '-single'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($single)), 'stdout_hex' => bin2hex('abc')];
	rejects(fn() => $prepare('global ' . $decl('v', 'int', '1') . 'echo ' . $quote($field('v')) . '; return 8;'), 'Unknown interpolation local');
	$nonfinite = $decl('v', 'float', '1e300 * 1e300') . 'echo ' . $quote($field('v')) . '; return 8;';
	$fixtures[$flavor->name . '-nonfinite'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($nonfinite)),
		'stdout_hex' => '', 'catch_out_of_range' => 'nonfinite_decimal_text'];
	$unknown = 'echo ' . $quote('é ' . $field('missing')) . '; return 8;';
	try {
		$prepare($unknown);
		throw new RuntimeException('Unknown interpolation accepted');
	}
	catch (diagnostic $error) {
		verify($error->offset === strpos($unknown, 'missing'), 'Wrong in-token diagnostic position');
	}
	foreach (['x[0]', 'x()', 'x + 1', 'x.y'] as $unsupported) {
		rejects(fn() => $prepare('echo ' . $quote($field($unsupported)) . '; return 8;'), 'interpolation');
	}
	$function = static fn(string $type, string $name, string $body) => ($flavor === flavor::cpp
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';

	$dir = sys_get_temp_dir() . '/scpp-interpolation-' . bin2hex(random_bytes(8));
	mkdir($dir);
	try {
		$lib = "$dir/lib." . $flavor->name;
		file_put_contents("$dir/module.json", '{}');
		$init = match ($flavor) {
			flavor::php => '$v = word();', flavor::cpp => 'auto v = word();', flavor::js => 'let v = word();',
		};
		file_put_contents("$dir/main." . $flavor->name, $init . 'echo ' . $quote('[' . $field('v') . ']') . '; return 8;');
		$valid = $function('bool', 'word', 'return false;');
		$compiler = new Project_Compiler();
		$session = null;
		$root = null;
		$revision = time();
		foreach ([$valid, $function('float', 'word', 'return 8.25;'), $function('mixed', 'word', 'return 8;'),
			'retry', null, $function('string', 'word', 'return "é\0";'), $valid] as $step => $body) {
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
				$actual = rejects(fn() => Incremental_Preparation::prepare($session, $target), $error);
				$sources = array_map(static fn($file) => $file->source(), $session->files());
				$expected = rejects(fn() => (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target), $error);
				verify($actual === $expected, 'Incremental and fresh diagnostics differ');
				continue;
			}
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$code = Cpp_Program::emit(new prepared_program($session->entry_key(), $files, new prepared_entry($root, $target), $session))->files;
			$sources = array_map(static fn($file) => $file->source(), $files);
			$fresh = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
			verify($code === Cpp_Program::emit($fresh)->files, 'Incremental and fresh interpolation lowering differ');
			$fixtures[$flavor->name . '-incremental-' . $step] = ['files' => $code, 'stdout_hex' => bin2hex([0 => '[false]', 1 => '[8.25]', 5 => "[é\0]", 6 => '[false]'][$step])];
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
echo "Scalar interpolation and incremental recovery passed\n";
