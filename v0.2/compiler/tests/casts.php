<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection, ast_cast_expression, value_conversion, type_int64};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function definition(flavor $flavor, string $name, string $parameters, string $type, string $body): string
{
	return ($flavor === flavor::cpp ? "$type $name($parameters)" : "function $name($parameters): $type") . '{' . $body . '}';
}

function local(flavor $flavor, string $name, string $type, string $value): string
{
	return match ($flavor) {
		flavor::php => "$$name $type=$value;",
		flavor::cpp => "$type $name=$value;",
		flavor::js => "let $name: $type=$value;",
	};
}

/** Check conversion failures at the source boundary, never accept an internal compiler exception. */
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
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('casts', $source, $flavor), new entry_target(31));
	$entry = $prepare($decl('result', 'int', '(int) 8.75') . 'return 8;');
	$cast = $entry->body()->statements[0]->initializer;
	verify(($cast instanceof ast_cast_expression) && ($cast->operand->parent === $cast), 'Cast syntax ownership');
	verify(($cast->type_def() === type_int64::instance()) && ($cast->constant() === 8), 'Converted scalar fact');
	verify($cast->conversion() === value_conversion::floating_integer_proven, 'Generated conversion decision');
	verify($entry->body()->expression_visits === 3, 'Cast and operand must each prepare once');
	verify($cast->source_text() === '(int) 8.75', 'Cast span');
	// All supported and unsupported generated scalar pairs; explicit syntax grants no extra conversion rights.
	$values = ['int' => '8', 'bool' => 'true', 'byte' => '(byte) 8', 'float' => '8.75', 'null' => 'null', 'string' => '"Hi"'];
	$matrix = '';
	$matrix_output = '';
	$extra = ['string' => ['bool', 'byte', 'int', 'float'], 'int' => ['bool', 'byte', 'float'], 'bool' => ['int', 'float', 'byte'], 'byte' => ['int', 'bool', 'float'], 'float' => ['int', 'bool', 'byte']];
	foreach ($values as $destination => $_) {
		foreach ($values as $source => $value) {
			$code = $decl('converted', $destination, '(' . $destination . ') (' . $value . ')') . 'return 8;';
			if (($source === $destination) || in_array($source, $extra[$destination] ?? [], true)) {
				$prepare($code);
				$expression = '(' . $destination . ') (' . $value . ')';
				if ($destination === 'null') {
					$matrix .= $decl('empty', 'null', $expression);
				}
				elseif ($destination === 'string') {
					$matrix .= 'echo ' . $expression . ';';
					$matrix_output .= match ($source) {
						'string' => 'Hi', 'bool' => 'true', 'int', 'byte' => '8', 'float' => '8.75',
					};
				}
				else {
					$matrix .= 'echo (int) (' . $expression . ');';
					$matrix_output .= (($destination === 'bool') || ($source === 'bool')) ? '1' : '8';
				}
			}
			else {
				rejects(fn() => $prepare($code), 'Unsupported explicit conversion');
			}
		}
	}
	$parameter = match ($flavor) {
		flavor::php => 'int $v',
		flavor::cpp => 'int v',
		flavor::js => 'v: int',
	};
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	$code = $matrix . $fn('identity', $parameter, 'int', 'return (int64)' . $var('v') . ';')
		. $fn('once', '', 'float', 'echo "C"; return 8.75;')
		. $fn('text', '', 'string', 'return "a\\0b";')
		. 'echo (int) 8.75 + 1; echo (int) -8.75; echo identity((int) true);'
		. 'echo (int) (byte) 255; echo (int) (float64) 8;'
		. 'echo (int) once(); echo (string) text();'
		. $decl('n', 'null', '(null) (null)')
		. $decl('x', 'int', '1') . $var('x') . '=(int) 9.75; echo ' . $var('x') . ';'
		. 'if ((bool) 1 && !(bool) -0.0) { echo "Y"; }'
		. 'if ((float) 8 == 8.0) { echo "F"; }'
		. $decl('loop', 'int', '1') . 'while ((bool)' . $var('loop') . ') {' . $var('loop') . '=0; }'
		. 'return (int) (8.99);';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Cast lowering mutated prepared facts');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($matrix_output . "9-812558C8a\0b9YF")];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	foreach (['(byte) 256' => 'outside uint8 range', '(byte) (int) -1.5' => 'outside uint8 range',
		'(int) 1e100' => 'outside int64 range', '(void) 8' => 'Unsupported cast target',
		'(bool) "yes"' => 'Unsupported explicit conversion'] as $invalid => $message) {
		rejects(fn() => $prepare($decl('bad', 'int', $invalid) . 'return 8;'), $message);
	}
	rejects(fn() => $prepare($decl('self', 'int', '(int)' . $var('self')) . 'return 8;'), 'during its initialization');
	rejects(fn() => $prepare($fn('nothing', '', 'void', 'return;') . 'echo (int) nothing(); return 8;'), 'Void call');
	rejects(fn() => $prepare($fn('reference', $ref, 'void', 'return;') . $decl('x', 'int', '8')
		. 'reference((int)' . $var('x') . '); return 8;'), 'stable variable storage');
	// Unknown call results retain runtime checks; each operand must be evaluated only once.
	foreach (['byte' => ['int', '300'], 'int' => ['float', '1e100']] as $target => [$source, $value]) {
		$failure = $fn('outside', '', $source, 'echo "X"; return ' . $value . ';')
			. $decl('bad', $target, '(' . $target . ') outside()') . 'return 8;';
		$fixtures[$flavor->name . '_' . $target . '_range'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
			'stdout_hex' => bin2hex('X'), 'catch_out_of_range' => true];
	}
	$directory = sys_get_temp_dir() . '/scpp-casts-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		file_put_contents($main, 'echo (int) value(); return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('value', '', $type, 'return ' . $value . ';'));
		};
		$write('bool', 'true');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		$write('string', '"invalid"');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported explicit conversion');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed after signature change');
		$write('float', '8.75');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Cast recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		$sources = [];
		foreach ($session->files() as $path => $file) {
			$sources[$path] = $file->source();
		}
		$program = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
		$native_files = Cpp_Program::emit($program)->files;
		$fixtures[$flavor->name . '_recovery'] = ['files' => $native_files, 'stdout_hex' => bin2hex('8')];
		$write('float', '98.75');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		file_put_contents($main, 'echo (int) (bool) value(); return 8;');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Changed cast target not prepared');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
		// A constant used inside a cast remains an ordinary watched symbol, including value changes and tombstones.
		$constant = static fn(string $value): string => $flavor === flavor::js
			? 'const LIMIT: int = ' . $value . ';' : 'const int LIMIT = ' . $value . ';';
		file_put_contents($main, 'echo (int) (byte) LIMIT; return 8;');
		file_put_contents($lib, $constant('8'));
		$session = $compiler->frontend($directory, $session);
		Incremental_Preparation::prepare($session, $target);
		$reader = $session->entry->entry->ast;
		file_put_contents($lib, $constant('300'));
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'outside uint8 range');
		file_put_contents($lib, '');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unknown');
		file_put_contents($lib, $constant('9'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Constant cast recovery');
		verify($session->entry->entry->ast === $reader, 'Constant edit replaced reader AST');
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
echo "Explicit casts, generated conversion pairs, single-pass preparation and incremental recovery passed\n";
