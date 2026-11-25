<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
function predicate_check(bool $ok,string $why):void{if(!$ok)throw new \LogicException($why);}
$original=dirname(__DIR__,3).'/tests/php/control_flow/strict_level_01/strict_control_flow_002_mixed_type_predicates.phs';
$text=file_get_contents($original);$original_record=['path'=>$original,'sha256'=>hash('sha256',$text)];
if(($argv[2]??'')==='--baseline'){
 $error='';try{operator_test_source($text);}catch(\RuntimeException $e){$error=$e->getMessage();}
 predicate_check(str_contains($error,'is_bool'),'Baseline did not reproduce unresolved predicate');$original_record['diagnostic']=$error;file_put_contents($argv[1].'/baseline.json',json_encode($original_record,JSON_PRETTY_PRINT)."\n");exit(0);
}
operator_test_source($text);file_put_contents($argv[1].'/original.cpp',CPP_Probe::text(Model::$cpp_files));
$program= <<<'PHS'
function pbool(const mixed &$v):bool{return is_bool($v);}
function pint(const mixed &$v):bool{return is_int($v);}
function pfloat(const mixed &$v):bool{return is_float($v);}
function produce(int &$calls):mixed{$calls++;return 7;}
function temporaries():int{$calls=0;$a=is_int(produce($calls));$b=is_bool(produce($calls));$c=is_float(produce($calls));if(!$a || $b || $c)return 90;return $calls;}
class Box {public mixed $value = 7;}
function makebox(int &$calls):Box{$calls++;return new Box();}
function properties():int{$calls=0;$box=new Box();$a=is_int($box->value);$b=is_int(makebox($calls)->value);$c=is_bool(makebox($calls)->value);$d=is_float(makebox($calls)->value);$box->value="after";if(!$a || !$b || $c || $d || is_int($box->value))return 90;return $calls;}
function mutate(mixed &$v):bool{$answer=is_int($v);$v="after";return $answer;}
function throwing():mixed{$bad=1/0;return 7;}
function error():bool{return is_int(throwing());}
function branch():int{$v mixed = 7;if(is_int($v)) return 2;elseif(is_bool($v)) return 3;else return 4;}
function saved_result():bool{$v mixed = 7;$answer=is_int($v);$v=false;return $answer && !is_int($v) && is_bool($v);}
PHS;
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));predicate_check(str_contains($cpp,'static_cast<const '),'Missing observed const view');
$cpp.="\n#include \"".__DIR__."/mixed_predicates_support.hpp\"\n#include <iostream>\n";
$cpp.= <<<'CPP'
int main(){using namespace predicate_probe;
 auto count=verify(function_pbool,function_pint,function_pfloat);
 if(function_temporaries().native_value()!=3||function_properties().native_value()!=3)return 1;
 M value{I{7}};if(!function_mutate(value).native_value()||value.get_string().native_value()!="after")return 2;
 if(function_branch().native_value()!=2||!function_savedU_result().native_value())return 3;
 bool failed=false;try{function_error();}catch(const scpp::runtime_error& e){failed=e.code()=="division_by_zero";}if(!failed)return 4;
 std::cout<<"predicate_checks="<<count<<"\n";return 0;}
CPP;
file_put_contents($argv[1].'/behavior.cpp',$cpp);
$rejections=[];
foreach(['is_bool','is_int','is_float'] as $name){
 foreach(['bool','int','float','string','int16','uint64','dynamic','?int','result<int>','result_or_false<int>','result_or_bool<int>','vector<int>'] as $type)$rejections[]='function bad('.$type.' $v):bool{return '.$name.'($v);}';
 $rejections[]=$name.'();';$rejections[]='$v mixed = 7;'.$name.'($v,$v);';$rejections[]='$v mixed = 7;$r =& '.$name.'($v);';
}
$rejections[]='function consume(int &$v):void{} $v mixed = 7;if(is_int($v)) consume($v);';
$rejections[]='$v mixed = 7;is_string($v);';
$failures=[];
foreach($rejections as $source){$error='';try{operator_test_source($source);}catch(\RuntimeException $e){$error=$e->getMessage();}predicate_check($error!==''&&Model::$cpp_files->is_empty(),'Wider contract admitted: '.$source);$failures[]=['source'=>$source,'diagnostic'=>$error];}
$dir=sys_get_temp_dir().'/scpp_predicate_'.bin2hex(random_bytes(6));mkdir($dir);$path=$dir.'/main.phs';$recoveries=0;
try{
 foreach(['is_bool','is_int','is_float'] as $name){
  $valid='function probe(mixed $v):bool{return '.$name.'($v);}';file_put_contents($path,$valid);Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$dir]);$compiler->exec_cpp();$fresh=CPP_Probe::text(Model::$cpp_files);
  foreach(['int','dynamic'] as $wrong){
   file_put_contents($path,str_replace('mixed $v',$wrong.' $v',$valid));
   foreach([[$path],[]] as $changes){$failed=false;try{$compiler->update_cpp($changes);}catch(\RuntimeException $e){$failed=true;}predicate_check($failed&&Model::$cpp_files->is_empty()&&Model::$prepared_files->is_empty(),'Failed predicate update retained artifacts');}
   file_put_contents($path,$valid);$compiler->update_cpp([$path]);predicate_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'Repaired output differs from fresh');$compiler->update_cpp([]);predicate_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'No-op changed output');++$recoveries;
  }
 }
}finally{unlink($path);rmdir($dir);}
file_put_contents($argv[1].'/checks.json',json_encode(['original'=>$original_record,'signatures'=>[['is_bool','mixed','observe','bool'],['is_int','mixed','observe','bool'],['is_float','mixed','observe','bool']],'rejections'=>$failures,'recovery_cycles'=>$recoveries],JSON_PRETTY_PRINT)."\n");echo "3 exact-mixed signatures, ".count($failures)." rejections, ".$recoveries." recovery cycles\n";
