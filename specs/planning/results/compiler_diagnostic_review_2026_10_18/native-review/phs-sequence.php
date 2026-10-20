<?php
namespace scpp\compiler;
require_once '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$d=$argv[1];mkdir($d.'/src');$p=$d.'/src/main.phs';$c=new Compiler();$rows=[];
function publish($label,$text,$notify,$initial=false){global $d,$p,$c,$rows;
 file_put_contents($p,$text);touch($p,1700000000);clearstatcache();$pub=new Output_Publication($d.'/published');
 try{if($initial)$c->init([$d.'/src']);if($notify)$c->update_cpp([$p],$pub);else $c->exec_cpp($pub);$rows[$label]['publication']='ready';}catch(\Throwable $e){$rows[$label]['publication_error']=$e->getMessage();}finally{$pub->close();}
 $config=new native_cpp_config('/usr/bin/clang++','/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/include',$d.'/cache');$config->pch=false;
 try{$r=(new Native_CPP_Build($config))->build($d.'/published');$rows[$label]['build']=['compiled'=>$r->compiled,'reused'=>$r->reused,'linked'=>$r->linked,'executable'=>$r->executable];}catch(\Throwable $e){$rows[$label]['build_error']=$e->getMessage();}
 $rows[$label]['native_status']=file_get_contents($d.'/cache/.scpp-native.status');file_put_contents($d.'/results.json',json_encode($rows,JSON_PRETTY_PRINT));
}
publish('baseline','$x = 1; return $x;',false,true);
publish('missed_invalid_edit','$x = 1; return $y;',false);
publish('notified_invalid_edit','$x = 1; return $y;',true);
publish('repair','$x = 1; return $x;',true);
publish('hash_baseline','$h hash<int, string>; return 0;',true);
publish('hash_invalid','$h hash<int, float>; return 0;',true);
publish('hash_repair','$h hash<int, string>; return 0;',true);
echo json_encode($rows);
