<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$schema = json_decode(file_get_contents($argv[1] . '/task_transfer_probe.json'), true);
foreach (['reference', 'variadic', 'default', 'scalar', 'nested', 'non_boolean'] as $case) {
	$bad = $schema;
	$parameter = $bad['functions'][1]['parameters'][0];
	if ($case === 'reference') { $parameter['passing'] = 'reference'; }
	elseif ($case === 'variadic') { $parameter['variadic'] = true; }
	elseif ($case === 'default') { $parameter['default'] = ['int'=>0]; }
	elseif ($case === 'scalar') { $parameter['type'] = 'int'; }
	elseif ($case === 'non_boolean') { $parameter['requires_transfer'] = 'yes'; }
	else { $parameter['type']['callable']['parameters'] = [$parameter]; }
	$bad['functions'][1]['parameters'][0] = $parameter;
	$failed = false;
	try { Runtime_Catalog_Reader::decode(json_encode($bad), 'invalid-transfer.json'); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid transfer metadata accepted: ' . $case); }
}
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
 'class Box{public int $n=7;}$b=new Box();probe_generic(function() use ($b):int{return $b->n;});',
 'class Box{public int $n=7;}$b=new Box();probe_execute(function() use ($b):int{return $b->n;});',
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
$edited = 'class Box{public int $n=7;}$b=new Box();$f function<int()> = function() use ($b):int{return $b->n;};probe_execute($f);';
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
