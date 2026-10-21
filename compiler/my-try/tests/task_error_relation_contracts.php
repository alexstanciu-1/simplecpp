<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['task_error_probe']));
$directory=$argv[2];mkdir($directory);$path=$directory.'/main.phs';
$valid='$v vector<int> = [1];probe_error_batch($v,1,fn(int $n):int=>$n,null,null,fn(int $n,task_error $e):int=>42);';
file_put_contents($path,$valid);$compiler=new Compiler();$compiler->init([$directory]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();$initial=CPP_Probe::text(Model::$cpp_files);
$schema=json_decode(file_get_contents($argv[1].'/task_error_probe.json'),true);
foreach (['unknown_result','unknown_replacement','missing_field','fixed_function'] as $case) {
 $bad=$schema;
 if ($case==='unknown_result') {$bad['functions'][0]['replacement_results'][0]['result']='Unknown';}
 elseif ($case==='unknown_replacement') {$bad['functions'][0]['replacement_results'][0]['replacement']='Unknown';}
 elseif ($case==='missing_field') {unset($bad['functions'][0]['replacement_results'][0]['replacement']);}
 else {unset($bad['functions'][0]['type_parameters']);}
 $failed=false;
 try {
  $module=Runtime_Catalog_Reader::decode(json_encode($bad),'invalid-error-relation.json');
  foreach($module->functions as $function){Runtime_Catalog_Patterns::contract($function,Model::$type_catalog);}
 } catch(\RuntimeException $e){$failed=true;}
 if(!$failed){throw new \LogicException('Invalid replacement relation accepted: '.$case);}
}
$invalid='$v vector<int> = [1];probe_error_batch($v,1,fn(int $n):int=>$n,null,null,fn(int $n,task_error $e):string=>"bad");';
file_put_contents($path,$invalid);$failed=false;
try{$compiler->update_cpp([$path]);}catch(\RuntimeException $e){$failed=str_contains($e->getMessage(),'replacement result must be void or exactly match');}
if(!$failed || !Model::$cpp_files->is_empty()){throw new \LogicException('Invalid replacement emitted output');}
file_put_contents($path,$valid);$compiler->update_cpp([$path]);
if(CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Replacement repair differs');}
$compiler->update_cpp([]);
if(CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Replacement replay differs');}
$edit='$v vector<int> = [1];probe_error_batch($v,1,function(int $n):void{},null,null,function(int $n,task_error $e):void{});';
file_put_contents($path,$edit);$compiler->update_cpp([$path]);$incremental=CPP_Probe::text(Model::$cpp_files);
Compiler_Lifecycle::reset();$fresh=new Compiler();$fresh->init([$directory]);$fresh->select_entry('main','main.phs');$fresh->exec_cpp();
if(CPP_Probe::text(Model::$cpp_files)!==$incremental){throw new \LogicException('Void replacement edit differs from fresh');}
echo "Replacement metadata validation and retained recovery passed\n";
