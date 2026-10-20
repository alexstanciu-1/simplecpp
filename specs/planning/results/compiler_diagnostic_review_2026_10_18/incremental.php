<?php
namespace scpp\compiler;
require_once '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$dir=$argv[1]; $case=json_decode(file_get_contents($argv[2]),true); mkdir($dir.'/src'); mkdir($dir.'/out');
function write_sources($files) { global $dir; $paths=[]; foreach($files as $name=>$text) { $p=$dir.'/src/'.$name; if($text===null) {if(file_exists($p))unlink($p);} else file_put_contents($p,$text); $paths[]=$p;} return $paths; }
function attempt($c,$paths,$label) { global $dir; $r=[]; $pub=new Output_Publication($dir.'/out'); $stage='sync'; try { $c->sync($paths); $stage='prepare'; $c->prepare(); $r['prepared']=true; $stage='cpp'; $c->cpp($pub); $r['emitted']=true; $r['digest']=hash('sha256',CPP_Output::encode(Model::$cpp_files)); file_put_contents($dir.'/'.$label.'.json',CPP_Output::encode(Model::$cpp_files)); } catch(\Throwable $e) {$r['stage']=$stage;$r['error']=$e->getMessage();} finally {$pub->close();} $r['empty']=Model::$cpp_files->is_empty(); try {Output_Publication::require_ready($dir.'/out');$r['ready']=true;}catch(\Throwable $e){$r['ready']=false;} if(!isset($r['emitted'])) {try {$c->cpp();$r['post_failure_cpp']='emitted';}catch(\Throwable $e){$r['post_failure_cpp']=$e->getMessage();} try {$c->prepare(); $r['direct_retry']='prepared';}catch(\Throwable $e){$r['direct_retry']=$e->getMessage();}} return $r; }
write_sources($case['base']); $c=new Compiler(); $c->init([$dir.'/src']);$c->select_entry('main','main.phs');
$r=['base'=>attempt($c,[],'base')];
if(isset($r['base']['emitted'])) { $r['edit']=attempt($c,write_sources($case['edit']),'edit');$r['retry']=attempt($c,[],'retry'); Compiler_Lifecycle::reset();$fresh=new Compiler();$fresh->init([$dir.'/src']);$fresh->select_entry('main','main.phs');$r['fresh_invalid']=attempt($fresh,[],'fresh-invalid');
// Rebuild baseline, then repeat failure and repair on one retained instance.
write_sources($case['base']); foreach($case['edit'] as $n=>$v) if(!array_key_exists($n,$case['base']))unlink($dir.'/src/'.$n);
Compiler_Lifecycle::reset();$c=new Compiler();$c->init([$dir.'/src']);$c->select_entry('main','main.phs');attempt($c,[],'base2');attempt($c,write_sources($case['edit']),'edit2'); $repair=$case['base'];foreach($case['edit'] as $n=>$v)if(!array_key_exists($n,$repair))$repair[$n]=null; $r['repair']=attempt($c,write_sources($repair),'repair'); }
echo json_encode($r);
