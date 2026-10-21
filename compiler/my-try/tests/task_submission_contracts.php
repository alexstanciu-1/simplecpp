<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['tasks']));
$directory = $argv[2]; mkdir($directory); $path = $directory . '/main.phs';
$valid = '$v vector<int> = [1,2];$r=task_run($v,2,fn(int $n):int=>$n);';
file_put_contents($path,$valid);
$compiler = new Compiler();$compiler->init([$directory]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();
$initial = CPP_Probe::text(Model::$cpp_files);
$schema = json_decode(file_get_contents($argv[1] . '/tasks.json'), true);
foreach (['unknown_transfer', 'mapped_input', 'mapped_callback', 'null_reference', 'null_arguments', 'null_default_type'] as $case) {
	$bad = $schema;
	foreach ($bad['functions'] as &$row) {
		if ($row['id'] !== 'tasks.run.vector') { continue; }
		if ($case === 'unknown_transfer') { $row['transfer_types'] = ['Unknown']; }
		elseif ($case === 'mapped_input') { $row['parameters'][0]['type']['arguments'][0]['void_to_null'] = true; }
		elseif ($case === 'mapped_callback') { $row['parameters'][2]['type']['callable']['returns']['void_to_null'] = true; }
		elseif ($case === 'null_reference') { $row['parameters'][3]['passing'] = 'reference'; }
		elseif ($case === 'null_arguments') { $row['parameters'][3]['type'] = ['name'=>'null','arguments'=>['int']]; }
		else { $row['parameters'][3]['default'] = ['int'=>0]; }
	}
	unset($row);
	$failed = false;
	try {
		$module = Runtime_Catalog_Reader::decode(json_encode($bad), 'invalid-task-contract.json');
		foreach ($module->functions as $function) {
			if ($function->identity === 'tasks.run.vector') { Runtime_Catalog_Patterns::contract($function, Model::$type_catalog); }
		}
	} catch (\RuntimeException $e) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid payload metadata accepted: ' . $case); }
}

$bad = [
 '$n=null;',
 '$v vector<int> = [1];task_run($v,2,fn(int $n):int=>$n,false);',
 'class Box{}$v vector<Box> = [new Box()];task_run($v,2,fn(Box $b):int=>1);',
 'class Box{}$v vector<int> = [1];task_run($v,2,fn(int $n):Box=>new Box());',
 'class Box{}$b=new Box();$v vector<int> = [1];task_run($v,2,function(int $n)use($b):int{return $n;});',
 '$v vector<int> = [1];$n=2;task_start($v,2,function(int $i)use(&$n):int{return $i+$n;});',
 'task_synchronize(fn(int $n):int=>$n);',
 'task_synchronize(fn():int=>1);',
 '$v vector<int> = [1];task_run_publish_unordered($v,2,function(int $n):void{},function(int $n):void{});',
];
foreach ($bad as $source) {
 file_put_contents($path,$source);$failed=false;
 try {$compiler->update_cpp([$path]);} catch (\RuntimeException $e) {$failed=true;echo $e->getMessage(),"\n";}
 if (!$failed || !Model::$cpp_files->is_empty()) {throw new \LogicException('Invalid task submission accepted or published');}
 file_put_contents($path,$valid);$compiler->update_cpp([$path]);
 if (CPP_Probe::text(Model::$cpp_files)!==$initial) {throw new \LogicException('Task submission repair differs');}
 $compiler->update_cpp([]);
 if (CPP_Probe::text(Model::$cpp_files)!==$initial) {throw new \LogicException('Task submission replay differs');}
}
$edit = '$v vector<int> = [1,2];$r=task_run($v,2,function(int $n):void{});';
file_put_contents($path,$edit);$compiler->update_cpp([$path]);$incremental=CPP_Probe::text(Model::$cpp_files);
Compiler_Lifecycle::reset();$fresh=new Compiler();$fresh->init([$directory]);$fresh->select_entry('main','main.phs');$fresh->exec_cpp();
if (CPP_Probe::text(Model::$cpp_files)!==$incremental) {throw new \LogicException('Void result edit differs from fresh');}
echo "Task payload rejection, recovery and void-result fresh parity passed\n";
