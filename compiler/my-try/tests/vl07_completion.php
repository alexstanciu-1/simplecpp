<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$mode=$argv[2];$lf=$argv[3];$rf=$argv[4];$lp=$argv[5];$rp=$argv[6];
function completion_type(string $f,string $p):string{return $f==='plain'?$p:($f==='nullable'?'?'.$p:$f.'<'.$p.'>');}
function completion_check(bool $ok,string $why):void{if(!$ok)throw new \LogicException($why);}
$left=completion_type($lf,$lp);$right=completion_type($rf,$rp);$compound=in_array($mode,['power','append'],true);
$op=['ordering'=>'<=>','power'=>'**=','concat'=>'.','append'=>'.='][$mode];$ret=$compound?$left:($mode==='ordering'?'int':'string');
$lv=$lp==='string'?'""':($lp==='float'?'1.5':($lp==='bool'?'true':'2'));$rv=$rp==='string'?'"x"':($rp==='float'?'1.5':($rp==='bool'?'false':'2'));
$valid='function probe('.$left.' $a,'.$right.' $b):'.$ret.'{return $a '.$op.' $b;}';
$syntax=operator_test_source('$a '.$left.' = '.$lv.';$b '.$right.' = '.$rv.';$out=($a '.$op.' $b);');$node=$syntax->root->single_body()->body->statements[2]->expression->value;
$facts=$compound?$node->require_compound_assignment_preparation():$node->require_binary_preparation();$decision=$facts->decision;
$guarded=in_array($mode,['ordering','power'],true)&&($lf!=='plain'||$rf!=='plain');
completion_check(count($decision->operand_extractions)===($guarded?2:0),'Wrong extraction shape');
if($mode==='ordering')completion_check($decision->operation===operator_operation::floating_three_way,'Missing numeric spaceship decision');
if($mode==='power')completion_check($decision->operation===operator_operation::integer_power,'Missing checked power decision');
if($compound)completion_check(!$facts->addressable(),'Compound result must be a value');
Preparation_Cleanup::tree($syntax->root);completion_check($node->preparation()===null,'Retained preparation after cleanup');
$program='class Box { public '.$left.' $v='.$lv.'; public static '.$left.' $s='.$lv.'; } '
 .'function box('.$left.' $v):Box{$b=new Box();$b->v=$v;return $b;} function read(Box $b):'.$left.'{return $b->v;} '
 .'function markl('.$left.' $v,int &$calls,int &$trace,bool $fail):'.$left.'{$calls++;$trace=$trace*10+1;if($fail){$bad=1/0;}return $v;} '
 .'function markr('.$right.' $v,int &$calls,int &$trace,bool $fail):'.$right.'{$calls++;$trace=$trace*10+2;if($fail){$bad=1%0;}return $v;} '
 .'function reset('.$left.' &$a,'.$left.' $replacement,'.$right.' $b,int &$calls,bool $fail):'.$right.'{$calls++;$a=$replacement;if($fail){$bad=1%0;}return $b;} '
 .'function local('.$left.' &$a,'.$left.' $replacement,'.$right.' $b,int &$calls,bool $fail):'.$ret.'{return ($a '.$op.' reset($a,$replacement,$b,$calls,$fail));} '
 .'function resetbox(Box $a,'.$left.' $replacement,'.$right.' $b,int &$calls,bool $fail):'.$right.'{$calls++;$a->v=$replacement;if($fail){$bad=1%0;}return $b;} '
 .'function property(Box $a,'.$left.' $replacement,'.$right.' $b,int &$calls,bool $fail):'.$ret.'{return ($a->v '.$op.' resetbox($a,$replacement,$b,$calls,$fail));} '
 .'function rebind(Box &$a,Box $replacement,'.$right.' $b,int &$calls,bool $fail):'.$right.'{$calls++;$a=$replacement;if($fail){$bad=1%0;}return $b;} '
 .'function owner(Box &$a,Box $replacement,'.$right.' $b,int &$calls,bool $fail):'.$ret.'{return ($a->v '.$op.' rebind($a,$replacement,$b,$calls,$fail));} '
 .'function setstatic('.$left.' $v):void{Box::$s=$v;} function readstatic():'.$left.'{return Box::$s;} '
 .'function resetstatic('.$left.' $v,'.$right.' $b,int &$calls,bool $fail):'.$right.'{$calls++;Box::$s=$v;if($fail){$bad=1%0;}return $b;} '
 .'function staticop('.$left.' $replacement,'.$right.' $b,int &$calls,bool $fail):'.$ret.'{return (Box::$s '.$op.' resetstatic($replacement,$b,$calls,$fail));} ';
if(!$compound)$program.='function binary('.$left.' $a,'.$right.' $b,int &$lc,int &$rc,int &$trace,bool $lf,bool $rf):'.$ret.'{return markl($a,$lc,$trace,$lf) '.$op.' markr($b,$rc,$trace,$rf);} ';
$dir=sys_get_temp_dir().'/scpp_completion_'.bin2hex(random_bytes(6));mkdir($dir);$path=$dir.'/main.phs';
try{
 file_put_contents($path,$valid);Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$dir]);$compiler->exec_cpp();$fresh=CPP_Probe::text(Model::$cpp_files);
 file_put_contents($path,'function probe('.$left.' $a,'.$right.' $b):'.$ret.'{return missing($a);}');
 foreach([[$path],[]] as $changes){$failed=false;try{$compiler->update_cpp($changes);}catch(\RuntimeException $e){$failed=true;}completion_check($failed&&Model::$prepared_files->is_empty()&&Model::$cpp_files->is_empty(),'Invalid source retained artifacts');}
 file_put_contents($path,$valid);$compiler->update_cpp([$path]);completion_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'Repair differs from fresh');$compiler->update_cpp([]);completion_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'No-op differs');
}finally{unlink($path);rmdir($dir);}
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$native=['int'=>['scpp::int_t<>','std::int64_t'],'float'=>['scpp::float_t','double'],'string'=>['scpp::string_t','std::string']];foreach(['int8','int16','int32','int64','uint8','uint16','uint32'] as $width){$native[$width]=['scpp::int_t<std::'.$width.'_t>','std::'.$width.'_t'];}$native['bool']=['scpp::bool_t','bool'];$family=['nullable'=>0,'result'=>1,'result_or_false'=>2,'result_or_bool'=>3,'plain'=>4];
$cpp.="\n#include \"".__DIR__."/vl07_completion_support.hpp\"\nusing namespace completion_probe;\n";
$cpp.='using V='.$native[$lp][0].';using P='.$native[$lp][1].';using W='.$native[$rp][0].';using Q='.$native[$rp][1].';constexpr int L='.$family[$lf].',R='.$family[$rf].';constexpr auto M=mode::'.$mode.';constexpr bool compound='.($compound?'true':'false').';' . "\n";
$cpp.='int main(){long cases=0;auto lefts=completion_states<L,M,P>();auto rights=completion_states<R,M,Q>();' . "\n";
if(!$compound)$cpp.= <<<'CPP'
for(auto a:lefts)for(auto b:rights)for(bool lf:{false,true})for(bool rf:{false,true}){
 auto av=make<L,V>(a);auto bv=make<R,W>(b);I lc{0},rc{0},trace{0};auto expected=calculation<M>(a,b);
 int error=lf?4:extraction<L,M>(a);bool reached=error==0;if(reached)error=rf?5:extraction<R,M>(b);if(!error)error=expected.error;int failure=0;
 try{auto out=function_binary(av,bv,lc,rc,trace,B{lf},B{rf});if(error||!result_matches<false>(out,expected.value))return 10;}catch(const std::exception&e){failure=classify(e);}
 if(failure!=error||lc.native_value()!=1||rc.native_value()!=(reached?1:0)||trace.native_value()!=(reached?12:1)||!same(av,a)||!same(bv,b))return 11;++cases;
}
CPP;
$cpp.= <<<'CPP'
for(auto a:lefts)for(auto b:rights)for(auto replacement:lefts)for(bool fail:{false,true}){
 auto expected=calculation<M>(a,b);int error=extraction<L,M>(a);bool reached=error==0;if(reached)error=fail?5:extraction<R,M>(b);if(!error)error=expected.error;
 auto final=reached?replacement:a;
 final=written_state<compound>(final,error,expected.value);
 auto av=make<L,V>(a);auto rv=make<L,V>(replacement);auto bv=make<R,W>(b);I calls{0};int failure=0;
 try{auto out=function_local(av,rv,bv,calls,B{fail});if(error||!result_matches<compound>(out,expected.value))return 20;}catch(const std::exception&e){failure=classify(e);}
 if(failure!=error||calls.native_value()!=(reached?1:0)||!same(av,final)||!same(rv,replacement)||!same(bv,b))return 21;
 auto box=function_box(make<L,V>(a));calls=I{0};failure=0;
 try{auto out=function_property(box,rv,bv,calls,B{fail});if(error||!result_matches<compound>(out,expected.value))return 30;}catch(const std::exception&e){failure=classify(e);}
 if(failure!=error||calls.native_value()!=(reached?1:0)||!same(function_read(box),final))return 31;
 function_setstatic(make<L,V>(a));calls=I{0};failure=0;
 try{auto out=function_staticop(rv,bv,calls,B{fail});if(error||!result_matches<compound>(out,expected.value))return 40;}catch(const std::exception&e){failure=classify(e);}
 if(failure!=error||calls.native_value()!=(reached?1:0)||!same(function_readstatic(),final))return 41;
 auto original=function_box(make<L,V>(a));auto other=function_box(rv);auto current=original;calls=I{0};failure=0;
 try{auto out=function_owner(current,other,bv,calls,B{fail});if(error||!result_matches<compound>(out,expected.value))return 50;}catch(const std::exception&e){failure=classify(e);}
 auto original_final=written_state<compound>(a,error,expected.value);
 if(failure!=error||calls.native_value()!=(reached?1:0)||!same(function_read(original),original_final)||!same(function_read(other),replacement)||!same(function_read(current),reached?replacement:original_final))return 51;
 ++cases;
}
std::cout<<"cases="<<cases<<"\n";return 0;}
CPP;
file_put_contents($argv[1].'/completion.cpp',$cpp);file_put_contents($argv[1].'/signatures.json',json_encode([[$left,$op,$right,$ret]],JSON_PRETTY_PRINT)."\n");echo "1 signature, 1 recovery passed\n";
