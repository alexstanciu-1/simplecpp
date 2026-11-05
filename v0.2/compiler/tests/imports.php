<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, Program_Frontend, Project_Compiler,
	Incremental_Preparation, entry_target, Cpp_Program, Symbol_Publication, Syntax_Inspection,
	diagnostic, diagnostic_group};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Wrap a namespace body in the selected source notation. */
function body(flavor $flavor, string $namespace, string $text): string
{
	return $flavor === flavor::php ? "namespace $namespace; $text" : "namespace $namespace { $text }";
}

function definition(flavor $flavor, string $name, string $text = ''): string
{
	return $flavor === flavor::cpp ? "void $name() { $text }" : "function $name(): void { $text }";
}

/** Require a source diagnostic, never a backend error or a host exception. */
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

$outputs = [];
foreach (flavor::cases() as $flavor)
{
	$separator = match ($flavor) {
		flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
	};
	$root = $flavor === flavor::cpp ? '::' : '\\';
	$target = 'Lib' . $separator . 'ping';
	$library = body($flavor, 'Lib', definition($flavor, 'ping', 'echo "ping\n";')
		. definition($flavor, 'pong', 'echo "pong\n";'));
	$main = body($flavor, 'App', 'run(); pong(); return 8; use function Lib' . $separator
		. '{ping as run, pong}; use ' . $root . $target . ' as run;');
	$sources = ['main' => new source_file('main', $main, $flavor),
		'lib' => new source_file('lib', $library, $flavor)];
	$prepare = static fn(array $input) => (new Program_Frontend())->prepare_sources('main', $input, new entry_target(31));
	$program = $prepare($sources);
	$file = $program->entry->file;
	verify(count($file->imports) === 3, 'Repeated imports lost from syntax inventory');
	verify($file->imports[0]->parent === $file, 'Import lacks file owner');
	verify($file->imports[0]->target === 'Lib\ping', 'Import target not canonical/absolute');
	verify($file->main()->body()->statements[0]->function_def() === $program->files['lib']->functions()[0],
		'Alias created a copied definition');
	verify($file->main()->body()->statements[0]->symbol_key() === 'func:Lib\ping', 'Call key is not target identity');
	verify(Symbol_Publication::completed($program->files)->resolve('func:App\run') === null, 'Import re-exported');
	verify(Syntax_Inspection::file($file)['imports'][0]['local_name'] === 'run', 'Inspection lost import');
	$before = serialize($program);
	$output = Cpp_Program::emit($program);
	verify(serialize($program) === $before, 'Emission mutated imported facts');
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
	$valid = [
		'use ' . $target . '; ping(); return 8;',
		'use function ' . $target . '; ' . definition($flavor, 'run', 'ping();') . 'run(); return 8;',
		'use function ' . $target . '; ' . $root . $target . '(); return 8;',
	];
	foreach ($valid as $text) {
		$case = $sources;
		$case['main'] = new source_file('main', body($flavor, 'App', $text), $flavor);
		$prepare($case);
	}
	$invalid = [
		['use function Missing' . $separator . 'ping; return 8;', 'Unknown function import'],
		['use const ' . $target . '; return 8;', 'Unknown constant import'],
		['use ' . $target . ' as run; use Lib' . $separator . 'pong as run; return 8;', 'Conflicting function import'],
		['use ' . $target . '; ' . definition($flavor, 'ping') . 'return 8;', 'conflicts with namespace declaration'],
		['use Lib as Short; return 8;', 'Unknown function import'],
		['use ' . $target . ' as Short; Short' . $separator . 'child(); return 8;', 'Unknown function'],
		['use ' . $target . '; ' . $root . 'App' . $separator . 'ping(); return 8;', 'Unknown function'],
	];
	foreach ($invalid as [$text, $message]) {
		$case = $sources;
		$case['main'] = new source_file('main', body($flavor, 'App', $text), $flavor);
		rejects(fn() => $prepare($case), $message);
	}
	$parser = new Source_Frontend();
	foreach (['use Lib{ping}; return 8;', 'use Lib' . $separator . '{}; return 8;',
		definition($flavor, 'bad', 'use ' . $target . ';')] as $text) {
		rejects(fn() => $parser->parse(new source_file('bad', $text, $flavor)),
			str_contains($text, 'bad') ? 'body-local use' : 'Expected');
	}
	// Same namespace does not share imports; a declaration in another file still conflicts.
	$case = $sources;
	$case['other'] = new source_file('other', body($flavor, 'App', definition($flavor, 'other', 'run();')), $flavor);
	rejects(fn() => $prepare($case), 'Unknown function');
	$case['other'] = new source_file('other', body($flavor, 'App', definition($flavor, 'run')), $flavor);
	rejects(fn() => $prepare($case), 'conflicts with namespace declaration');
	$case = $sources;
	$case['other'] = new source_file('other', 'use function Missing' . $separator . 'unused;', $flavor);
	rejects(fn() => $prepare($case), 'Unknown function import');

	// Recheck unused imports and conflicts even when body caches and the importing AST survive.
	$directory = sys_get_temp_dir() . '/scpp-imports-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try
	{
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->value], JSON_THROW_ON_ERROR));
		$main_path = $directory . '/main.' . $flavor->value;
		$lib_path = $directory . '/lib.' . $flavor->value;
		file_put_contents($main_path, $main);
		file_put_contents($lib_path, $library);
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target_layout = new entry_target(31);
		verify(Incremental_Preparation::prepare($session, $target_layout) === ['prepared' => 3, 'reused' => 0], 'Cold imports');
		$caller = $session->entry->entry->ast;
		$slot = $session->scope->entry('func:Lib\ping');
		verify($session->dependency_watches->observer_count($slot) === 1, 'Call watches alias instead of target');
		file_put_contents($lib_path, $library . ' // edited body generation');
		$session = $compiler->frontend($directory, $session);
		verify($session->entry->entry->ast === $caller, 'Unchanged importing AST replaced');
		verify(Incremental_Preparation::prepare($session, $target_layout) === ['prepared' => 2, 'reused' => 1], 'Stable import invalidates caller');
		file_put_contents($lib_path, body($flavor, 'Lib', definition($flavor, 'pong')));
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target_layout), 'Unknown function import');
		file_put_contents($lib_path, $library);
		$session = $compiler->frontend($directory, $session);
		verify($session->scope->entry('func:Lib\ping') === $slot, 'Import target did not reactivate');
		verify(Incremental_Preparation::prepare($session, $target_layout) === ['prepared' => 3, 'reused' => 0], 'Reactivation did not prepare caller');
		$conflict_path = $directory . '/conflict.' . $flavor->value;
		file_put_contents($conflict_path, body($flavor, 'App', definition($flavor, 'run')));
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target_layout), 'conflicts with namespace declaration');
		unlink($conflict_path);
		file_put_contents($main_path, body($flavor, 'App', 'use ' . $target . '; return 8;'));
		$session = $compiler->frontend($directory, $session);
		Incremental_Preparation::prepare($session, $target_layout);
		file_put_contents($lib_path, body($flavor, 'Lib', definition($flavor, 'pong')));
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target_layout), 'Unknown function import');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
verify(($outputs[0] === $outputs[1]) && ($outputs[1] === $outputs[2]), 'Import lowering differs by flavor');
echo "Imports: absolute targets, file scope, groups, conflicts, inspection and incremental reuse passed\n";
