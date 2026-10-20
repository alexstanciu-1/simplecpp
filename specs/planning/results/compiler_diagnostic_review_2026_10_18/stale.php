<?php
namespace scpp\compiler;
require_once '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$dir=$argv[1];mkdir($dir.'/src');mkdir($dir.'/out');$p=$dir.'/src/main.phs';
$valid='$x = 1; return $x;';$invalid='$x = 1; return $y;';
if($argv[2]==='syntax')$invalid='$x = 1; return )x;';
file_put_contents($p,$valid);touch($p,1700000000);clearstatcache();
$c=new Compiler();$c->init([$dir.'/src']);
function build($c,$paths) {global $dir;$pub=new Output_Publication($dir.'/out');try{if($paths===null)$c->exec_cpp($pub);else $c->update_cpp($paths,$pub);$r=['emitted'=>true,'digest'=>hash('sha256',CPP_Output::encode(Model::$cpp_files))];}catch(\Throwable $e){$r=['error'=>$e->getMessage()];}finally{$pub->close();}try{Output_Publication::require_ready($dir.'/out');$r['ready']=true;}catch(\Throwable $e){$r['ready']=false;}return $r;}
$r['base']=build($c,null);file_put_contents($p,$invalid);touch($p,1700000000);clearstatcache();$r['disk_source']=file_get_contents($p);$r['scan_only']=build($c,null);file_put_contents($dir.'/stale-artifacts.json',CPP_Output::encode(Model::$cpp_files));$r['empty_update']=build($c,[]);$r['notified']=build($c,[$p]);Compiler_Lifecycle::reset();$fresh=new Compiler();$fresh->init([$dir.'/src']);$r['fresh']=build($fresh,null);echo json_encode($r);
