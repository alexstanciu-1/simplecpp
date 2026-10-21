<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['task_transfer_probe']));
$directory = $argv[2];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = '$n=7;probe_execute(function() use ($n):int{return $n;});';
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$compiler->exec_cpp();
$initial = CPP_Probe::text(Model::$cpp_files);
$invalid = [
 '$n=7;probe_execute(function() use (&$n):int{return $n;});',
 'probe_execute(fn(int $n):int=>$n);',
 'probe_execute(fn():string=>"wrong");',
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
$edited = 'class Box{public int $n=7;}$b=new Box();probe_execute(function() use ($b):int{return $b->n;});';
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
