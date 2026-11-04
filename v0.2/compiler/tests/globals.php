<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, Program_Frontend, Project_Compiler,
	Incremental_Preparation, entry_target, Cpp_Program, Symbol_Publication, Syntax_Inspection,
	Compilation_Inspection, diagnostic, diagnostic_group, ast_global_declaration, ast_local_declaration,
	change_status, exit_conversion};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require compiler diagnostics rather than PHP exceptions or backend failures. */
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

function wrap(flavor $flavor, string $namespace, string $text): string
{
	return $flavor === flavor::php ? "namespace $namespace; $text" : "namespace $namespace { $text }";
}

/** Render a canonical variable path with the selected flavor's root and final-name notation. */
function variable(flavor $flavor, string $path): string
{
	if ($flavor === flavor::php) {
		$last = strrpos($path, '\\');
		return $last === false ? '$' . $path : substr($path, 0, $last + 1) . '$' . substr($path, $last + 1);
	}
	if ($flavor === flavor::cpp) {
		return str_replace('\\', '::', $path);
	}
	return (str_starts_with($path, '\\') ? '\\' : '') . str_replace('\\', '.', ltrim($path, '\\'));
}

function global_text(flavor $flavor, string $name, string $value, ?string $type = null): string
{
	return match ($flavor) {
		flavor::php => 'global $' . $name . ($type === null ? '' : ' ' . $type) . " = $value;",
		flavor::cpp => 'global ' . ($type ?? 'auto') . " $name = $value;",
		flavor::js => 'global let ' . $name . ($type === null ? '' : ': ' . $type) . " = $value;",
	};
}

function function_text(flavor $flavor, string $name, string $body = ''): string
{
	return $flavor === flavor::cpp ? "void $name() { $body }" : "function $name(): void { $body }";
}

$outputs = [];
$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$separator = match ($flavor) {
		flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
	};
	$root = $flavor === flavor::cpp ? '::' : '\\';
	$v = variable($flavor, 'value');
	$local = match ($flavor) {
		flavor::php => '$value int = 5;', flavor::cpp => 'int value = 5;', flavor::js => 'let value: int = 5;',
	};
	$show = function_text($flavor, 'show', 'echo ' . $v . '; echo "\n";');
	$lib = wrap($flavor, 'Lib', global_text($flavor, 'value', '10', 'int64') . $show);
	$main = wrap($flavor, 'App', $local . 'echo ' . $v . '; echo "\n"; echo '
		. variable($flavor, '\Lib\value') . '; echo "\n"; ' . $root . 'Lib' . $separator . 'show(); show(); echo '
		. variable($flavor, '\root_value') . '; echo "\n"; return ' . variable($flavor, '\exit_code') . ';'
		. $show . global_text($flavor, 'value', '20'));
	$root_code = global_text($flavor, 'root_value', '42', 'int') . global_text($flavor, 'exit_code', '+8')
		. global_text($flavor, 'minimum', '-9223372036854775808') . global_text($flavor, 'maximum', '9223372036854775807');
	$sources = ['main' => new source_file('main', $main, $flavor),
		'lib' => new source_file('lib', $lib, $flavor), 'root' => new source_file('root', $root_code, $flavor)];
	$fixtures[$flavor->value] = $sources;
	$prepare = static fn(array $input) => (new Program_Frontend())->prepare_sources('main', $input, new entry_target(31));
	$program = $prepare($sources);
	$file = $program->entry->file;
	$body = $file->main()->body();
	verify($body->statements[1]->value->declaration() instanceof ast_local_declaration, 'Local did not shadow global');
	verify($body->statements[3]->value->declaration() === $program->files['lib']->globals()[0], 'Cross-source global link');
	verify($file->functions()[0]->body()->statements[0]->value->declaration() === $file->globals()[0], 'Function captured main local');
	verify($body->statements[3]->value->constant() === null, 'Global initializer folded into a mutable read');
	verify($body->statements[9]->conversion() === exit_conversion::checked, 'Global exit lacks runtime check');
	verify($program->files['root']->main() === null, 'Globals invented executable statements');
	verify($program->files['root']->globals()[2]->initializer->integer() === PHP_INT_MIN, 'Minimum int64 lost');
	verify($program->files['root']->globals()[3]->initializer->integer() === PHP_INT_MAX, 'Maximum int64 lost');
	verify(array_key_first($file->declarations) === 'func:main', 'Main must stay first');
	verify(Syntax_Inspection::file($file)['globals'][0]['qualified_name'] === 'App\value', 'Global inspection');
	verify(Compilation_Inspection::program($program->entry)['statements'][3]['value']['binding'] === 'var:Lib\value', 'Prepared inspection');
	$before = serialize($program);
	$output = Cpp_Program::emit($program);
	verify(serialize($program) === $before, 'Lowering mutated facts');
	verify(str_contains(implode('', $output->files), 'constinit __INT64_TYPE__'), 'Static initialization missing');
	$outputs[] = $output->files;
	if (isset($argv[1])) {
		foreach ($output->files as $path => $text) {
			$dest = $argv[1] . '/' . $flavor->value . '/' . $path;
			if (!is_dir(dirname($dest))) {
				mkdir(dirname($dest), 0777, true);
			}
			file_put_contents($dest, $text);
		}
	}

	// Namespace-qualified access bypasses a same-name local; relative paths remain exact.
	$relative = $sources;
	$relative['main'] = new source_file('main', 'echo ' . variable($flavor, 'Lib\value') . '; return 8;', $flavor);
	$prepare($relative);
	$relative['main'] = new source_file('main', wrap($flavor, 'App', 'echo ' . variable($flavor, 'Lib\value') . '; return 8;'), $flavor);
	rejects(fn() => $prepare($relative), 'local/global');
	$relative['main'] = new source_file('main', wrap($flavor, 'App', 'echo ' . variable($flavor, 'root_value') . '; return 8;'), $flavor);
	rejects(fn() => $prepare($relative), 'local/global');
	$relative['main'] = new source_file('main', wrap($flavor, 'App', $local . 'echo '
		. variable($flavor, '\\App\\value') . '; return 8;' . global_text($flavor, 'value', '20')
		. function_text($flavor, 'local_reader', $local . 'echo ' . $v . ';')), $flavor);
	$shadowed = $prepare($relative);
	verify($shadowed->entry->body()->statements[1]->value->declaration() instanceof ast_global_declaration,
		'Root-qualified read did not bypass local');
	verify($shadowed->entry->file->functions()[0]->body()->statements[1]->value->declaration() instanceof ast_local_declaration,
		'Function-local variable did not shadow global');
	foreach (['9223372036854775808', '-9223372036854775809'] as $literal) {
		$case = $sources;
		$case['root'] = new source_file('root', $root_code . global_text($flavor, 'bad', $literal), $flavor);
		rejects(fn() => $prepare($case), 'outside int64');
	}
	$case = $sources;
	$case['root'] = new source_file('root', $root_code . global_text($flavor, 'bad', '1', 'vector'), $flavor);
	rejects(fn() => $prepare($case), 'unsupported global type');
	$case = $sources;
	$case['duplicate'] = new source_file('duplicate', wrap($flavor, 'Lib', global_text($flavor, 'value', '99')), $flavor);
	rejects(fn() => $prepare($case), 'Duplicate global: Lib\value');
	$parser = new Source_Frontend();
	foreach (['"text"', variable($flavor, 'value'), '1 + 2'] as $initializer) {
		rejects(fn() => $parser->parse(new source_file('bad', global_text($flavor, 'bad', $initializer), $flavor)), 'scalar literal');
	}
	rejects(fn() => $parser->parse(new source_file('bad', function_text($flavor, 'bad', global_text($flavor, 'value', '1')), $flavor)), 'file/namespace');
	$case = $sources;
	$case['main'] = new source_file('main', global_text($flavor, 'value', '1') . $v . ' = 2; return 8;', $flavor);
	if ($flavor === flavor::php) {
		$prepare($case); // Bare PHP writes declare local storage, leaving the global unchanged.
	}
	else {
		rejects(fn() => $prepare($case), 'global writes are deferred');
	}
	$duplicate_file = $parser->collect(new source_file('dup', global_text($flavor, 'value', '1')
		. global_text($flavor, 'value', '2') . function_text($flavor, 'value'), $flavor));
	verify(count($duplicate_file->diagnostics) === 1, 'Duplicate globals were not collected');
	$symbols = Symbol_Publication::completed(['dup' => $duplicate_file]);
	verify($symbols->declarations['var:value']->duplicate_element() === $duplicate_file->globals()[1], 'Duplicate chain missing');
	verify($symbols->resolve('func:value') !== null, 'Function and variable namespaces collided');

	// Stable global handles survive initializer edits; removal invalidates readers, and re-add repairs them.
	$directory = sys_get_temp_dir() . '/scpp-globals-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try
	{
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->value], JSON_THROW_ON_ERROR));
		$main_path = $directory . '/main.' . $flavor->value;
		$lib_path = $directory . '/lib.' . $flavor->value;
		file_put_contents($main_path, 'echo ' . variable($flavor, '\Lib\value') . '; return 8;');
		file_put_contents($lib_path, wrap($flavor, 'Lib', global_text($flavor, 'value', '10')));
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 1, 'reused' => 0], 'Cold globals');
		$caller = $session->entry->entry->ast;
		$read = $caller->main()->body()->statements[0]->value;
		$slot = $session->scope->entry('var:Lib\value');
		$old = $read->declaration();
		file_put_contents($lib_path, wrap($flavor, 'Lib', global_text($flavor, 'value', '100')));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 0, 'reused' => 1], 'Initializer edit invalidated storage read');
		verify($session->entry->entry->ast === $caller, 'Unchanged caller replaced');
		verify(($read->declaration() !== $old) && ($read->declaration()->initializer->integer() === 100), 'Read retained old declaration');
		file_put_contents($lib_path, '// removed global');
		$session = $compiler->frontend($directory, $session);
		verify($slot->status === change_status::removed, 'Removed global lacks tombstone');
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'local/global');
		verify($session->dependency_watches->observer_count($slot) === 1, 'Missing global lost watcher');
		file_put_contents($lib_path, wrap($flavor, 'Lib', global_text($flavor, 'value', '12')));
		$session = $compiler->frontend($directory, $session);
		verify($session->scope->entry('var:Lib\value') === $slot, 'Global identity not reactivated');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 1, 'reused' => 0], 'Reader not repaired');
		file_put_contents($lib_path, wrap($flavor, 'Lib', global_text($flavor, 'value', '1', 'vector')));
		touch($lib_path, time() + 2);
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'unsupported global type');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
verify(($outputs[0] === $outputs[1]) && ($outputs[1] === $outputs[2]), 'Global lowering differs by flavor');
$js = ['main' => new source_file('main', 'let Lib: int = 1; echo Lib.value; return 8;', flavor::js),
	'lib' => $fixtures['js']['lib']];
rejects(fn() => (new Program_Frontend())->prepare_sources('main', $js, new entry_target(31)), 'Member reads');
$js['main'] = new source_file('main', 'let Lib: int = 1; echo \\Lib.value; return 8;', flavor::js);
(new Program_Frontend())->prepare_sources('main', $js, new entry_target(31));
$mixed = $fixtures['php'];
$mixed['lib'] = $fixtures['cpp']['lib'];
$mixed['root'] = $fixtures['js']['root'];
$program = (new Program_Frontend())->prepare_sources('main', $mixed, new entry_target(31));
verify(Cpp_Program::emit($program)->files === $outputs[0], 'Mixed-flavor global resolution differs');
echo "Globals: storage, literal limits, namespace reads, shadowing, duplicate chains and incremental handles passed\n";
