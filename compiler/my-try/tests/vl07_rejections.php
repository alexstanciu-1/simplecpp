<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$rows=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$directory=sys_get_temp_dir().'/scpp_completion_rejections_'.bin2hex(random_bytes(6));mkdir($directory);$path=$directory.'/main.phs';
$records=[];
try {
 foreach($rows as $row) {
  $lhs=$row['lhs'];$rhs=$row['rhs'];$op=$row['operator'];
  $valid='function probe('.$lhs.' $a, '.$rhs.' $b): bool { return true; }';
  $invalid='function probe('.$lhs.' $a, '.$rhs.' $b): void { $out=($a '.$op.' $b); }';
  file_put_contents($path,$valid);Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$directory]);$compiler->exec_cpp();$fresh=CPP_Probe::text(Model::$cpp_files);
  file_put_contents($path,$invalid);$diagnostic='';
  foreach([[$path],[]] as $changes) {
   $failed=false;try{$compiler->update_cpp($changes);}catch(\RuntimeException $e){$failed=true;$diagnostic=$e->getMessage();}
   if(!$failed||!Model::$prepared_files->is_empty()||!Model::$cpp_files->is_empty())throw new \LogicException('Expected semantic rejection: '.$invalid);
  }
  file_put_contents($path,$valid);$compiler->update_cpp([$path]);
  if(CPP_Probe::text(Model::$cpp_files)!==$fresh)throw new \LogicException('Repair differs from fresh');
  $compiler->update_cpp([]);if(CPP_Probe::text(Model::$cpp_files)!==$fresh)throw new \LogicException('No-op differs');
  $records[]=['signature_id'=>$row['signature_id'],'diagnostic'=>$diagnostic,'recovery'=>true];
 }
} finally {unlink($path);rmdir($directory);}
file_put_contents($argv[2],json_encode($records,JSON_PRETTY_PRINT)."\n");echo count($records)." intentional rejections and recoveries passed\n";
