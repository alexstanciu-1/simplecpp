<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['task_callbacks_probe']));
$directory = $argv[2];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = '$n=7;probe_save(function(int $a,int $b) use ($n):int{return $a+$b+$n;});echo probe_invoke();';
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$compiler->exec_cpp();
$initial = CPP_Probe::text(Model::$cpp_files);
$invalid = [
	'$n=7;probe_save(function(int $a,int $b) use (&$n):int{return $a+$b+$n;});',
	'probe_save(fn(int $a):int=>$a);',
	'probe_zero(fn():int=>1);',
	'probe_pair(function(int &$a,int $b):void{$a=$b;});',
];
foreach ($invalid as $source) {
	file_put_contents($path, $source);
	$failed = false;
	try { $compiler->update_cpp([$path]); }
	catch (\RuntimeException $error) { $failed = true; echo $error->getMessage(), "\n"; }
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid callback accepted or published'); }
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $initial) { throw new \LogicException('Callback repair differs'); }
	$compiler->update_cpp([]);
	if (CPP_Probe::text(Model::$cpp_files) !== $initial) { throw new \LogicException('Callback replay differs'); }
}
$edited = 'probe_zero(function():void{echo "zero";});';
file_put_contents($path, $edited);
$compiler->update_cpp([$path]);
$incremental = CPP_Probe::text(Model::$cpp_files);
Compiler_Lifecycle::reset();
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->select_entry('main', 'main.phs');
$fresh->exec_cpp();
if (CPP_Probe::text(Model::$cpp_files) !== $incremental) { throw new \LogicException('Edited callback differs from fresh'); }
echo "Callback rejection, repair and fresh parity passed\n";
