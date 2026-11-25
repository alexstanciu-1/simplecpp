<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$metadata=$argv[1];$directory=$argv[2];mkdir($directory);$path=$directory.'/main.phs';
$source='task_set_worker_pool_size(0);echo task_publish_batch_count();';
file_put_contents($path,$source);
$module=$argv[3];
 Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($metadata,[$module]));
 $compiler=new Compiler();$compiler->init([$directory]);$compiler->select_entry('main','main.phs');$failed=false;
 try{$compiler->exec_cpp();}catch(\RuntimeException $error){$failed=true;echo $error->getMessage(),"\n";}
 if($module==='core'){
  if(!$failed || !Model::$cpp_files->is_empty()){throw new \LogicException('Unselected tasks module remained available');}
 }else{
  if($failed){throw new \LogicException('Selected tasks module rejected');}
  $output=CPP_Probe::text(Model::$cpp_files);
  $immutable=false;try{Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($metadata,['core']));}catch(\LogicException $error){$immutable=true;}
  if(!$immutable || CPP_Probe::text(Model::$cpp_files)!==$output){throw new \LogicException('Startup catalog changed during compilation');}
 }
$schema=json_decode(file_get_contents($metadata.'/tasks.json'),true);
$names=[];foreach($schema['functions'] as $row){if(!isset($row['member'])){$names[$row['name']]=true;}}
$expected=['task_run','task_start','task_run_publish','task_run_publish_unordered','task_synchronize','task_join','task_cancel','task_done','task_status','task_progress','task_set_status','task_set_worker_pool_size','task_set_publish_try_lock','task_publish_lock_wait_us','task_publish_lock_hold_us','task_publish_callback_us','task_publish_batch_count','task_publish_published_count','task_publish_max_batch_size','task_publish_failed_try_lock_count','task_publish_deferred_flush_count'];
if(count($names)!==count($expected)){throw new \LogicException('Task API inventory differs');}
foreach($expected as $name){if(!isset($names[$name])){throw new \LogicException('Missing task API: '.$name);}}
if($schema['cpp_build']!==['sources'=>['modules/tasks/tasks.cpp'],'definitions'=>['SCPP_HAS_TASKS=1'],'libraries'=>['pthread']]){throw new \LogicException('Task build configuration differs');}
echo "Task opt-in/startup immutability and 21-name metadata inventory passed\n";
