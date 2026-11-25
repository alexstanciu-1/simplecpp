<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
function unbraced_check(bool $ok,string $why):void { if(!$ok)throw new \LogicException($why); }
$original=dirname(__DIR__,3).'/tests/php/strings/strict_level_01/strict_strings_004_strpos_false_branch_regression.phs';
$original_text=file_get_contents($original);
$diagnostic='';try{operator_test_source($original_text);}catch(\RuntimeException $e){$diagnostic=$e->getMessage();}
$original_result=['source'=>$original,'sha256'=>hash('sha256',$original_text),'diagnostic'=>$diagnostic,'passed'=>$diagnostic===''];
if(($argv[2]??'')==='--baseline'){
 $probe='';try{operator_test_source('if (true) return 7; else return 3;');}catch(\RuntimeException $e){$probe=$e->getMessage();}
 unbraced_check(str_contains($probe,"Expected '{'"),'Baseline did not reproduce S06');
 file_put_contents($argv[1].'/baseline.json',json_encode(['original'=>$original_result,'minimal_diagnostic'=>$probe],JSON_PRETTY_PRINT)."\n");exit(0);
}
unbraced_check($diagnostic === '', 'Original fixture failed after S09-B1 closure');
$cases=[
 'true'=>['if(true) return 7; return 3;',7],
 'false'=>['if(false) return 7; return 3;',3],
 'else'=>['if(false) return 7; else return 3;',3],
 'elseif'=>['if(false) return 1; elseif(true) return 2; else return 3;',2],
 'else_if_comments'=>['if(false) return 1; else /* gap */ if(false) return 2; else if(true) return 3; else return 4;',3],
 'mixed'=>['if(false) {return 1;} elseif(false) return 2; else {return 3;}',3],
 'one_statement'=>['$x=0;if(false) $x=1;$x+=2;return $x;',2],
 'outer_write'=>['$x=1;if(true) $x=7;return $x;',7],
 'typed_shadow'=>['$x int = 1;if(true) $x int = 7;return $x;',1],
 'parameter_shadow'=>['function f(int $x):int{if(true) $x int = 7;return $x;}return f(3);',3],
 'sibling_scopes'=>['if(true) $x int = 7;else $x bool = false;$x=3;return $x;',3],
 'return_complete'=>['function f(bool $v):int{if($v) return 1;else return 2;}return f(true)+f(false);',3],
 'void_return'=>['function f(bool $v):void{if($v) return;else return;}f(true);f(false);return 0;',0],
 'lazy_conditions'=>['function probe(int &$n,bool $v):bool{$n++;return $v;}$n=0;if(probe($n,false)) $n=90;elseif(probe($n,true)) $n+=10;elseif(probe($n,true)) $n=99;return $n;',12],
 'lazy_body'=>['if(true) return 7;else return 1/0;',7],
 'lazy_elseif'=>['function bad():bool{$x=1/0;return true;}if(true) return 7;elseif(bad()) return 3;else return 2;',7],
 'selected_error'=>['if(false) return 7;else return 1/0;',0,'division_by_zero'],
 'condition_error'=>['function bad():bool{$x=1%0;return true;}if(bad()) return 7;else return 3;',0,'modulo_by_zero'],
 'loop_transfers'=>['$n=0;$s=0;while($n<5){$n++;if($n===2) continue;if($n===4) break;$s+=$n;}return $s;',4],
 'for_continue'=>['$s=0;for($i=0;$i<4;$i++){if($i===2) continue;$s+=$i;}return $s;',4],
 'nested_braced_loop'=>['$x=0;if(true) while($x<3){$x++;}else $x=9;return $x;',3],
 'nested_block'=>['if(true) {if(false) return 1;else return 2;}else return 3;',2],
];
foreach([false,true] as $a)foreach([false,true] as $b){
 $aa=$a?'true':'false';$bb=$b?'true':'false';$id=(int)$a.''.(int)$b;
 $cases['nearest_'.$id]=['$x=0;if('.$aa.') if('.$bb.') $x=1;else $x=2;return $x;',$a?($b?1:2):0];
 $cases['outer_else_'.$id]=['$x=0;if('.$aa.') {if('.$bb.') $x=1;}else $x=2;return $x;',$a?($b?1:0):2];
 $cases['two_else_'.$id]=['$x=0;if('.$aa.') if('.$bb.') $x=1;else $x=2;else $x=3;return $x;',$a?($b?1:2):3];
}
foreach([false,true] as $a)foreach([false,true] as $b)foreach([false,true] as $c){
 $aa=$a?'true':'false';$bb=$b?'true':'false';$cc=$c?'true':'false';
 $cases['nested_elseif_'.(int)$a.(int)$b.(int)$c]=['$x=0;if('.$aa.') if('.$bb.') $x=1;elseif('.$cc.') $x=2;else $x=3;return $x;',$a?($b?1:($c?2:3)):0];
}
$executions=[];
foreach($cases as $name=>$row){
 operator_test_source($row[0]);$cpp=CPP_Probe::text(Model::$cpp_files);
 if(isset($row[2])){
  $cpp=str_replace('int main(int argc, char** argv)',"// SCPP-PROBE-OBSERVE-ENTRY\nint unbraced_entry(int argc, char** argv)",$cpp);
  $cpp.="\n".'int main(){try{unbraced_entry(0,nullptr);}catch(const scpp::runtime_error& e){return e.code()=="'.$row[2].'"?0:91;}return 92;}';
 }
 $target=$argv[1].'/'.$name.'.cpp';file_put_contents($target,$cpp);$executions[]=['name'=>$name,'path'=>$target,'exit_code'=>$row[1],'source'=>$row[0]];
}
// An unbraced block spans precisely its statement; else remains outside the inner return body.
$syntax=operator_test_source('if(true) if(false) return 1; else return 2; return 3;');
$outer=$syntax->root->single_body()->body->statements[0];$inner=$outer->body->statements[0];
unbraced_check($outer instanceof if_node && $inner instanceof if_node && $outer->next_arm===null && $inner->next_arm!==null,'Nearest-if AST binding');
foreach([$outer->body,$inner->body,$inner->next_arm->body] as $body){
 $statement=$body->statements[0];unbraced_check(count($body->statements)===1 && $body->start_token()===$statement->start_token() && $body->end_token()===$statement->end_token(),'Single-body token span');
}
$rejected=[
 'if(true)', 'if(true) else return 1;', 'if(true) return 1;else',
 'if(false) return 1;else if {return 2;}', 'else return 1;',
 'if(true) return 1;else return 2;else return 3;', 'if(true) ;',
 'if(true): return 1;endif;', 'while(false) return 1;', 'for(;;) break;',
 'if(true) break 2;', 'if(true) break;', 'if(true) continue;',
 'if(true) function f():int{return 1;}', 'if(true){function f():int{return 1;}}',
 'if(true) struct X {int $v;}', 'if(true){struct X {int $v;}}',
 'if(true) $local=7;return $local;', 'if(false) $local=7;else return $local;',
 'function f(bool $v):int{if($v) return 1;}',
 'if(false) $x=missing();return 0;', 'if(true) return 1;else return missing();',
 '$x=1;if(true) $x int = $x;return $x;',
];
$rejections=[];
foreach($rejected as $source){$error='';try{operator_test_source($source);}catch(\RuntimeException $e){$error=$e->getMessage();}unbraced_check($error!=='' && Model::$cpp_files->is_empty(),'Invalid syntax/semantics published output: '.$source);$rejections[]=['source'=>$source,'diagnostic'=>$error];}
// Retained compiler: changing brace placement changes else ownership, not just formatting.
$directory=sys_get_temp_dir().'/scpp_unbraced_'.bin2hex(random_bytes(6));mkdir($directory);$path=$directory.'/main.phs';$recoveries=0;
try{
 $variants=['$x=0;if(true) if(false) $x=1;else $x=2;return $x;', '$x=0;if(true){if(false) $x=1;}else $x=2;return $x;', '$x=0;if(false) $x=1;elseif(true) $x=2;else $x=3;return $x;', '$x=0;if(false){$x=1;}else /* gap */ if(true) $x=2;else {$x=3;}return $x;'];
 file_put_contents($path,$variants[0]);Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$directory]);$compiler->exec_cpp();
 foreach($variants as $index=>$valid){
  file_put_contents($path,$valid);$compiler->update_cpp([$path]);$incremental=CPP_Probe::text(Model::$cpp_files);$compiler->update_cpp([]);unbraced_check(CPP_Probe::text(Model::$cpp_files)===$incremental,'No-op changed output');
  $output=$argv[1].'/incremental_'.$index.'.cpp';file_put_contents($output,$incremental);$executions[]=['name'=>'incremental_'.$index,'path'=>$output,'exit_code'=>$index===1?0:2,'source'=>$valid];
  foreach(['if(true) else return 1;','if(false) missing();return 0;'] as $invalid){
   file_put_contents($path,$invalid);
   foreach([[$path],[]] as $changes){$failed=false;try{$compiler->update_cpp($changes);}catch(\RuntimeException $e){$failed=true;}unbraced_check($failed && Model::$cpp_files->is_empty(),'Invalid update published artifacts');}
   file_put_contents($path,$valid);$compiler->update_cpp([$path]);unbraced_check(CPP_Probe::text(Model::$cpp_files)===$incremental,'Recovery changed output');++$recoveries;
  }
  Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$directory]);$compiler->exec_cpp();unbraced_check(CPP_Probe::text(Model::$cpp_files)===$incremental,'Fresh differs from incremental');
 }
}finally{unlink($path);rmdir($directory);}
file_put_contents($argv[1].'/executions.json',json_encode($executions,JSON_PRETTY_PRINT)."\n");file_put_contents($argv[1].'/checks.json',json_encode(['original'=>$original_result,'positive_programs'=>count($executions),'rejections'=>$rejections,'recovery_cycles'=>$recoveries,'span_checks'=>3],JSON_PRETTY_PRINT)."\n");echo count($executions)." conditional programs, ".count($rejections)." rejections, ".$recoveries." recovery cycles\n";
