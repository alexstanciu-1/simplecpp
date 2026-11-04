<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Program_Loader, Program_Frontend, Cpp_Program, entry_target, flavor};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

/** Check that invalid module configuration fails with the expected diagnostic. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (RuntimeException $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new LogicException('Expected rejection: ' . $message);
}

$root = sys_get_temp_dir() . '/scpp-modules-' . bin2hex(random_bytes(8));
mkdir($root);
try
{
	foreach (['app', 'library', 'core', 'runtime'] as $name) {
		mkdir($root . '/' . $name);
	}
	file_put_contents($root . '/app/main.cpp', 'helper(); return 8;');
	file_put_contents($root . '/library/main.cpp', 'void helper() { ping(); }');
	file_put_contents($root . '/core/main.cpp', 'void ping() { echo "core"; }');
	$definitions = [];
	foreach (['app', 'library', 'core', 'runtime'] as $name) {
		$definitions[$name] = ['root' => $root . '/' . $name, 'sources' => $name === 'runtime' ? [] : ['main.cpp' => flavor::cpp],
			'dependencies' => []];
	}
	$definitions['app']['entry'] = 'main.cpp';
	$definitions['app']['dependencies'] = ['library', 'runtime'];
	$definitions['library']['dependencies'] = ['core'];
	$definitions['runtime']['dependencies'] = ['core'];
	$session = Program_Loader::modules($definitions, 'app');
	verify(count($session->modules) === 4, 'Dependency closure');
	verify($session->entry === $session->modules['app'], 'Entry module identity');
	verify($session->modules['library']->dependencies['core'] === $session->modules['runtime']->dependencies['core'],
		'Shared dependency identity');
	verify($session->entry->entry === $session->entry->sources['main.cpp'], 'Entry source identity');
	verify(count($session->units()) === 3, 'Same relative source name collided');
	foreach ($session->modules as $module) {
		foreach ($module->sources as $unit) {
			verify(($unit->module === $module) && ($unit->ast === null), 'Source owner and unloaded state');
		}
	}
	$frontend = new Program_Frontend();
	$program = $frontend->prepare($session, new entry_target(31));
	verify($program->session === $session, 'Preparation lost graph lifetime');
	verify($program->entry_path === 'app/main.cpp', 'Module-qualified entry');
	verify($program->files['core/main.cpp'] === $session->modules['core']->sources['main.cpp']->ast,
		'Duplicate AST ownership');
	$output = Cpp_Program::emit($program);
	verify(count($output->source_units) === 3, 'One output per source');
	$reordered = Program_Loader::modules(array_reverse($definitions, true), 'app');
	verify(Cpp_Program::emit($frontend->prepare($reordered, new entry_target(31)))->files === $output->files,
		'Definition order changed output');
	file_put_contents($root . '/app/main.cpp', 'return');
	rejects(fn() => $frontend->prepare($session, new entry_target(31)), 'Expected');
	foreach ($session->units() as $unit) {
		verify($unit->ast === null, 'Failed compilation retained partial frontend results');
	}
	file_put_contents($root . '/app/main.cpp', 'helper(); return 8;');
	verify(Cpp_Program::emit($frontend->prepare($session, new entry_target(31)))->files === $output->files,
		'Retry differs from clean compilation');
	try {
		Cpp_Program::emit($program);
		throw new RuntimeException('Old module result remained usable');
	}
	catch (LogicException $error) {
		verify(str_contains($error->getMessage(), 'Stale module'), 'Incorrect stale-result rejection');
	}
	$bad = $definitions;
	$bad['core']['dependencies'] = ['app'];
	rejects(fn() => Program_Loader::modules($bad, 'app'), 'cycle');
	$bad = $definitions;
	$bad['app']['dependencies'] = ['absent'];
	rejects(fn() => Program_Loader::modules($bad, 'app'), 'Unknown');
	$bad['app']['dependencies'] = ['core', 'core'];
	verify(count(Program_Loader::modules($bad, 'app')->entry->dependencies) === 1,
		'Repeated dependency was not merged');
	$bad = $definitions;
	$bad['library']['root'] = $root . '/core';
	rejects(fn() => Program_Loader::modules($bad, 'app'), 'Duplicate physical source');
	$bad = $definitions;
	$bad['app']['entry'] = 'absent.cpp';
	rejects(fn() => Program_Loader::modules($bad, 'app'), 'not in the source inventory');
	rejects(fn() => Program_Loader::modules($definitions, 'library'), 'requires an entry source');
}
finally
{
	foreach (['app', 'library', 'core', 'runtime'] as $name) {
		$path = $root . '/' . $name . '/main.cpp';
		if (is_file($path)) {
			unlink($path);
		}
		rmdir($root . '/' . $name);
	}
	rmdir($root);
}
echo "Module graph ownership, shared dependencies, paths, preparation and failure recovery passed\n";
