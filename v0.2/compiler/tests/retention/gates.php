<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';

function gate_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \RuntimeException($message);
	}
}

$root = sys_get_temp_dir() . '/scpp-close-gates-' . bin2hex(random_bytes(8));
mkdir($root);
try
{
	file_put_contents($root . '/module.json', '{}');
	file_put_contents($root . '/main.cpp', 'value(); return 8;');
	$stamp = time();
	$write = static function (string $text) use ($root, &$stamp): void {
		file_put_contents($root . '/library.cpp', $text);
		touch($root . '/library.cpp', ++$stamp);
	};
	$write('void value() {}');
	$project = new Incremental_Project($root, $root . '/out', new entry_target(31));
	$project->run();
	$old = $project->session();
	$root_ref = \WeakReference::create($old->scope->resolve('func:value')->root());
	$write('void value() { echo 1; }');
	$project->run();
	gate_check($project->session()->previous === null, 'Successful generation kept history');
	gate_check(!$old->is_current && ($root_ref->get() !== null), 'External old session was destroyed');
	try {
		Generation_Cleanup::close($old);
		throw new \RuntimeException('Stale session was accepted');
	}
	catch (\LogicException) {
	}
	unset($old);
	gc_collect_cycles();
	gate_check($root_ref->get() === null, 'Retired root did not become collectible');
	$before = $project->session();
	$write('void value() { missing(); }');
	try {
		$project->run();
		throw new \RuntimeException('Preparation failure expected');
	}
	catch (diagnostic_group) {
	}
	gate_check($project->session()->previous === $before, 'Failure prematurely closed history');
	try {
		Generation_Cleanup::close($project->session());
		throw new \RuntimeException('Pending work was accepted');
	}
	catch (\LogicException) {
	}
	$write('void value() { echo 2; }');
	$project->run();
	gate_check($project->session()->previous === null, 'Recovery did not close history');
	gate_check($project->session()->removed_sources === [], 'Retirement records survived');
	Generation_Cleanup::close($project->session());
	echo "Close gates: external ownership, stale rejection, failure retention, recovery and idempotence passed\n";
}
finally {
	$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
