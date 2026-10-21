<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], ['tasks']));
$directory = $argv[2]; mkdir($directory); $path = $directory . '/main.phs';
$valid = '$v mixed = [1];$r=task_run($v,1,fn(mixed $n):mixed=>$n);';
file_put_contents($path,$valid);
$compiler=new Compiler();$compiler->init([$directory]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();
$initial=CPP_Probe::text(Model::$cpp_files);
$schema=json_decode(file_get_contents($argv[1].'/tasks.json'),true);
foreach (['unknown_result','unknown_family','missing_field','unknown_field','fixed_function'] as $case) {
 $bad=$schema;
 foreach ($bad['functions'] as &$row) {
  if ($row['id']!=='tasks.run.mixed') {continue;}
  if ($case==='unknown_result') {$row['boxed_results'][0]['result']='Unknown';}
  elseif ($case==='unknown_family') {$row['boxed_results'][0]['family']='vector';}
  elseif ($case==='missing_field') {unset($row['boxed_results'][0]['family']);}
  elseif ($case==='unknown_field') {$row['boxed_results'][0]['extra']=true;}
  else {unset($row['type_parameters']);}
 }
 unset($row);
 $failed=false;
 try {
  $module=Runtime_Catalog_Reader::decode(json_encode($bad),'invalid-boxed-result.json');
  foreach($module->functions as $function){if($function->identity==='tasks.run.mixed'){Runtime_Catalog_Patterns::contract($function,Model::$type_catalog);}}
 } catch(\RuntimeException $e){$failed=true;}
 if(!$failed){throw new \LogicException('Invalid boxed requirement accepted: '.$case);}
}
foreach (['mixed','dynamic'] as $family) {
 $other=$family==='mixed'?'dynamic':'mixed';
 $bad=[
  'fn('.$family.' $n):'.$other.'=>1',
  'fn('.$family.' $n):vector<int> => [1]',
  'fn('.$family.' $n):?int=>1',
  'fn('.$family.' $n):uint64=>1',
 ];
 foreach($bad as $callback){
  file_put_contents($path,'$v '.$family.' = [1];task_run($v,1,'.$callback.');');$failed=false;
  try{$compiler->update_cpp([$path]);}catch(\RuntimeException $e){echo $e->getMessage(),"\n";$failed=str_contains($e->getMessage(),'Boxed task result must be');}
  if(!$failed || !Model::$cpp_files->is_empty()){throw new \LogicException('Invalid boxed result emitted output: '.$callback);}
  file_put_contents($path,$valid);$compiler->update_cpp([$path]);
  if(CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Boxed result repair differs');}
  $compiler->update_cpp([]);
  if(CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Boxed result replay differs');}
 }
}
foreach (['$v dynamic = [1];$b=task_start($v,1,fn(dynamic $n):dynamic=>$n);$r=task_join($b);',
 '$v mixed = [1];$r=task_run($v,1,function(mixed $n):void{});'] as $edit) {
 file_put_contents($path,$edit);$compiler->update_cpp([$path]);$incremental=CPP_Probe::text(Model::$cpp_files);
 Compiler_Lifecycle::reset();$fresh=new Compiler();$fresh->init([$directory]);$fresh->select_entry('main','main.phs');$fresh->exec_cpp();
 if(CPP_Probe::text(Model::$cpp_files)!==$incremental){throw new \LogicException('Boxed family edit differs from fresh');}
 $compiler=$fresh;
}
echo "Boxed metadata validation, rejection and retained recovery passed\n";
