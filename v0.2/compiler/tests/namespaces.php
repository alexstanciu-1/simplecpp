<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, Program_Frontend, Project_Compiler,
	Incremental_Preparation, entry_target, Cpp_Program, diagnostic_group, diagnostic, change_status};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Create one namespace body using each flavor's chosen surface syntax. */
function wrap(flavor $flavor, string $namespace, string $code): string
{
	$path = str_replace('\\', match ($flavor) {
		flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
	}, $namespace);
	return $flavor === flavor::php ? "namespace $path; $code" : "namespace $path { $code }";
}

function function_text(flavor $flavor, string $name, string $body): string
{
	return $flavor === flavor::cpp ? "void $name() { $body }" : "function $name(): void { $body }";
}

/** @param Closure $action Must reject unsupported syntax or invalid name resolution. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), 'Wrong diagnostic: ' . $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

$outputs = [];
$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$separator = match ($flavor) {
		flavor::php => '\\', flavor::cpp => '::', flavor::js => '.',
	};
	$root_marker = $flavor === flavor::cpp ? '::' : '\\';
	$ping = function_text($flavor, 'ping', 'echo "lib\n"; return;');
	$main = wrap($flavor, 'App', 'run(); Sub' . $separator . 'ping(); ' . $root_marker . 'Lib' . $separator
		. 'ping(); ' . $root_marker . 'global_ping(); return 8;');
	$sources = [
		'main' => new source_file('main', $main, $flavor),
		'app' => new source_file('app', wrap($flavor, 'App', function_text($flavor, 'run', 'echo "app\n";')), $flavor),
		'sub' => new source_file('sub', wrap($flavor, 'App\Sub', $ping), $flavor),
		'lib' => new source_file('lib', wrap($flavor, 'Lib', $ping), $flavor),
		'global' => new source_file('global', function_text($flavor, 'global_ping', 'echo "root\n";'), $flavor),
	];
	$fixtures[$flavor->value] = $sources;
	$program = (new Program_Frontend())->prepare_sources('main', $sources, new entry_target(31));
	$entry = $program->entry->body();
	verify($entry->scope->namespace === 'App', 'Entry lost namespace context');
	verify($entry->statements[1]->value->name === 'Sub\ping', 'Relative AST path not normalized');
	verify($entry->statements[2]->value->name === '\Lib\ping', 'Root marker not preserved');
	verify($entry->statements[1]->function_def()->name() === 'App\Sub\ping', 'Relative qualified lookup failed');
	verify($entry->statements[2]->function_def()->namespace === 'Lib', 'Root lookup failed');
	verify($entry->statements[2]->function_def()->short_name === 'ping', 'Short identity lost');
	verify($entry->statements[2]->source_text() === $root_marker . 'Lib' . $separator . 'ping();', 'Original spelling lost');
	$output = Cpp_Program::emit($program);
	$outputs[] = $output->files;
	if (isset($argv[1])) {
		$dest = $argv[1] . '/' . $flavor->value;
		foreach ($output->files as $path => $text) {
			if (!is_dir(dirname($dest . '/' . $path))) {
				mkdir(dirname($dest . '/' . $path), 0777, true);
			}
			file_put_contents($dest . '/' . $path, $text);
		}
	}
	$sources['main'] = new source_file('main', wrap($flavor, 'App\Nested', 'run(); return 8;'), $flavor);
	rejects(fn() => (new Program_Frontend())->prepare_sources('main', $sources, new entry_target(31)), 'Unknown function');
	$sources['main'] = new source_file('main', wrap($flavor, 'App', 'global_ping(); return 8;'), $flavor);
	rejects(fn() => (new Program_Frontend())->prepare_sources('main', $sources, new entry_target(31)), 'Unknown function');
	$sources['main'] = new source_file('main', $main, $flavor);
	$sources['duplicate'] = new source_file('duplicate', wrap($flavor, 'Lib', $ping), $flavor);
	rejects(fn() => (new Program_Frontend())->prepare_sources('main', $sources, new entry_target(31)), 'Duplicate function: Lib\ping');
	$frontend = new Source_Frontend();
	$local_text = match ($flavor) {
		flavor::php => '$value int = 8;', flavor::cpp => 'int value = 8;', flavor::js => 'let value: int = 8;',
	};
	$local_root = $frontend->parse(new source_file('local', wrap($flavor, 'App', $local_text), $flavor));
	$local = $local_root->main()->body()->locals[0];
	verify(($local->short_name === 'value') && ($local->namespace === null) && ($local->symbol_key() === 'var:value'), 'Local identity was namespace-qualified');
	$inspection = \scpp\compiler\Syntax_Inspection::file($program->entry->file);
	$span = $inspection['statements'][2]['name']['span'];
	verify(substr($main, $span['start'], $span['end'] - $span['start']) === $root_marker . 'Lib' . $separator . 'ping', 'Qualified-name diagnostic span incomplete');
	rejects(fn() => $frontend->parse(new source_file('bad', wrap($flavor, 'App', 'namespace Other {}'), $flavor)), 'namespaces');
	rejects(fn() => $frontend->parse(new source_file('bad', wrap($flavor, 'App', 'export ' . $ping), $flavor)), $flavor === flavor::js ? 'export' : 'Expected');
	if ($flavor !== flavor::php) {
		rejects(fn() => $frontend->parse(new source_file('bad', 'namespace App; return 8;', $flavor)), 'Expected {');
		rejects(fn() => $frontend->parse(new source_file('bad', wrap($flavor, 'App', '') . 'return 8;', $flavor)), 'Only one namespace');
	}

	// Body edit/move retains callable identity; namespace rename removes old and adds new keys.
	$directory = sys_get_temp_dir() . '/scpp-namespaces-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try
	{
		file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->value], \JSON_THROW_ON_ERROR));
		$main_path = $directory . '/main.' . $flavor->value;
		$lib_path = $directory . '/lib.' . $flavor->value;
		file_put_contents($main_path, wrap($flavor, 'App', $root_marker . 'Lib' . $separator . 'ping(); return 8;'));
		file_put_contents($lib_path, wrap($flavor, 'Lib', $ping));
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		verify(Incremental_Preparation::prepare($session, new entry_target(31)) === ['prepared' => 2, 'reused' => 0], 'Cold preparation');
		$caller = $session->entry->entry->ast;
		$slot = $session->scope->entry('func:Lib\ping');
		file_put_contents($lib_path, wrap($flavor, 'Lib', $ping) . ' // body-neutral edit');
		$session = $compiler->frontend($directory, $session);
		verify($session->entry->entry->ast === $caller, 'Unchanged caller AST was replaced');
		verify(Incremental_Preparation::prepare($session, new entry_target(31)) === ['prepared' => 1, 'reused' => 1], 'Caller invalidated by equivalent contract');
		file_put_contents($lib_path, wrap($flavor, 'Elsewhere', $ping));
		$session = $compiler->frontend($directory, $session);
		verify(($slot->status === change_status::removed) && ($session->scope->resolve('func:Elsewhere\ping') !== null), 'Namespace rename did not remove/add');
		rejects(fn() => Incremental_Preparation::prepare($session, new entry_target(31)), 'Unknown function');
		verify($session->dependency_watches->observer_count($slot) === 1, 'Missing qualified symbol lost watcher');
		file_put_contents($lib_path, wrap($flavor, 'Lib', $ping));
		$session = $compiler->frontend($directory, $session);
		verify($session->scope->entry('func:Lib\ping') === $slot, 'Reintroduced namespace identity replaced');
		verify(Incremental_Preparation::prepare($session, new entry_target(31)) === ['prepared' => 2, 'reused' => 0], 'Reintroduction did not repair caller');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
verify(($outputs[0] === $outputs[1]) && ($outputs[1] === $outputs[2]), 'Flavor normalization changed emitted identities');
$php = new Source_Frontend();
verify($php->parse(new source_file('global', 'namespace { return 8; }', flavor::php))->scope->namespace === null, 'Global PHP block');
$js = ['main' => new source_file('main', 'let Lib: int = 1; Lib.ping(); return 8;', flavor::js),
	'lib' => new source_file('lib', 'namespace Lib { function ping(): void {} }', flavor::js)];
rejects(fn() => (new Program_Frontend())->prepare_sources('main', $js, new entry_target(31)), 'Member calls');
$js['main'] = new source_file('main', 'let Lib: int = 1; \\Lib.ping(); return 8;', flavor::js);
(new Program_Frontend())->prepare_sources('main', $js, new entry_target(31));
$mixed = $fixtures['php'];
$mixed['app'] = $fixtures['cpp']['app'];
$mixed['sub'] = $fixtures['js']['sub'];
$mixed_program = (new Program_Frontend())->prepare_sources('main', $mixed, new entry_target(31));
$mixed_output = Cpp_Program::emit($mixed_program);
verify($mixed_output->files === $outputs[0], 'Mixed-flavor namespace resolution differs');
if (isset($argv[1])) {
	foreach ($mixed_output->files as $path => $text) {
		$dest = $argv[1] . '/mixed/' . $path;
		if (!is_dir(dirname($dest))) {
			mkdir(dirname($dest), 0777, true);
		}
		file_put_contents($dest, $text);
	}
}
echo "Namespace identity, lookup, duplicates, flavor normalization and incremental watches passed\n";
