<?php
namespace scpp\compiler;
require_once '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
try {
 if($argv[1]==='publish') {
  $worker=new Output_Publication($argv[2]);
  try { $files=new Storage(); foreach(json_decode(file_get_contents($argv[3]),true) as $path=>$body) $files->append(new cpp_artifact($path,str_ends_with($path,'.hpp')?cpp_artifact_kind::header:cpp_artifact_kind::translation_unit,$path,$body)); $worker->publish($files,new runtime_cpp_build_spec());} finally {$worker->close();}
 } else {
  $config=new native_cpp_config('/usr/bin/clang++',$argv[4],$argv[3]);$config->pch=$argv[5]==='pch';
  $r=(new Native_CPP_Build($config))->build($argv[2]);echo json_encode(['executable'=>$r->executable,'compiled'=>$r->compiled,'reused'=>$r->reused,'pch_built'=>$r->pch_built,'linked'=>$r->linked]);
 }
}catch(\Throwable $e){fwrite(STDERR,$e->getMessage());exit(1);}
