<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'async function child():int{await async_sleep_ms(0);return 3;}async function parent():int{return 1+await child();}echo await parent();';
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$compiler->exec_cpp();
$initial = CPP_Probe::text(Model::$cpp_files);
foreach ([
	str_replace('async function child()', 'function child()', $valid),
	str_replace('return 3;', 'return "bad";', $valid),
	str_replace('1+await child()', '1+child()', $valid),
] as $source) {
	file_put_contents($path, $source);
	$failed = false;
	try { $compiler->update_cpp([$path]); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Async rejection left published output'); }
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $initial) { throw new \LogicException('Async repair differs'); }
	$compiler->update_cpp([]);
	if (CPP_Probe::text(Model::$cpp_files) !== $initial) { throw new \LogicException('Async replay differs'); }
}
foreach ([
	'function child():int{return 4;}echo child();',
	'async function child():string{await async_sleep_ms(0);return "new";}echo await child();',
	$valid,
] as $edited) {
	file_put_contents($path, $edited);
	$compiler->update_cpp([$path]);
	$incremental = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $incremental) { throw new \LogicException('Async edit differs from fresh'); }
}
echo "Async output rejection, repair, retirement and fresh parity passed\n";
