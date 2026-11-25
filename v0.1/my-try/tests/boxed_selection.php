<?php
namespace scpp\compiler;
require_once dirname(__DIR__).'/boot.php';
require_once __DIR__.'/cpp_probe.php';
function bscheck(bool $ok,string $why):void{if(!$ok){throw new \LogicException($why);}}
function bscompile(string $source):string{
 Compiler_Lifecycle::reset();$m=new module('/bs','/bs','/bs');$f=new file();$f->path='main.phs';$f->content=$source;
 Source_Registry::add($m,$f);Model::$modules->add($m->name,$m);(new Compiler())->exec_cpp();
 return CPP_Probe::text(Model::$cpp_files);
}
function bsexport(string $name,string $source,string $observer):void{
 global $argv;
 $cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',bscompile($source));
 file_put_contents($argv[1].'/'.$name.'.cpp',$cpp."\n#include \"boxed_selection_oracle.hpp\"\n".$observer);
}
$ops=['band'=>'&','bor'=>'|','bxor'=>'^','shl'=>'<<','shr'=>'>>'];
foreach(['mixed'=>'scpp::mixed_t','dynamic'=>'scpp::dynamic_t<>'] as $family=>$cpp){
 $source='function left('.$family.' $v,int &$n):'.$family.'{$n=$n*10+1;return $v;} function right('.$family.' &$a,'.$family.' $b,int &$n):'.$family.'{$n=$n*10+2;$a=99;return $b;} ';
 foreach($ops as $name=>$op){
  $source.='function '.$name.'('.$family.' $a,'.$family.' $b):'.$family.'{return $a '.$op.' $b;} ';
  $source.='function '.$name.'ordered('.$family.' &$a,'.$family.' $b,int &$n):'.$family.'{return left($a,$n) '.$op.' right($a,$b,$n);} ';
 }
 $source.='function bnot('.$family.' $a):'.$family.'{return ~$a;} function unary('.$family.' $a,int &$n):'.$family.'{return ~left($a,$n);}';
 $obs='int main(){return boxed_oracle<'.$cpp.'>(function_band,function_bor,function_bxor,function_shl,function_shr,function_bnot,function_bandordered,function_borordered,function_bxorordered,function_shlordered,function_shrordered,function_unary);}' ;
 bsexport($family,$source,$obs);
}
foreach(['nullable','result','result_or_false'] as $family){
 foreach(['int'=>'scpp::int_t<>','float'=>'scpp::float_t','bool'=>'scpp::bool_t','string'=>'scpp::string_t'] as $payload=>$cpp){
  $w=$family==='nullable'?'?'.$payload:$family.'<'.$payload.'>';
  $source='function left('.$w.' $v,int &$n):'.$w.'{$n=$n*10+1;return $v;} function right('.$w.' $v,int &$n):'.$w.'{$n=$n*10+2;return $v;} function choose('.$w.' $a,'.$w.' $b,int &$n):'.$payload.'{return left($a,$n) ?? right($b,$n);} ';
  $source.='function store('.$w.' $a,'.$w.' $b,'.$payload.' &$out,int &$n):void{$out=left($a,$n) ?? right($b,$n);} ';
  $source.='function bad(int &$n):'.$w.'{$n++;$x=1/0;return $x;} function lazy('.$w.' $a,int &$n):'.$payload.'{return $a ?? bad($n);}';
  // Throwing RHS uses an exact payload return after the division fails.
  $literal=['int'=>'0','float'=>'0.0','bool'=>'false','string'=>'""'][$payload];
  $source=str_replace('return $x;}','return '.$literal.';}',$source);
  $absent=$family==='nullable'?'scpp::null':($family==='result'?'scpp::error_t{scpp::string_t{"bad"}}':'scpp::false_sentinel');
  bsexport($family.'-'.$payload,$source,'int main(){using T='.$cpp.';using W=scpp::'.$family.'<T>;return selection_oracle<W,T>(function_choose,function_store,function_lazy,W{'.$absent.'});}');
 }
}
$bad=[];
foreach(['mixed','dynamic'] as $family){
 $other=$family==='mixed'?'dynamic':'mixed';
 foreach($ops as $name=>$op){
  foreach(['$a '.$family.'=7;$b '.$other.'=2;$v=$a '.$op.' $b;','$a '.$family.'=7;$v=$a '.$op.' 2;','$a '.$family.'=7;$v=2 '.$op.' $a;','$a '.$family.'=7;$b '.$family.'=2;$a '.$op.'= $b;'] as $s){$bad[]=$s;}
 }
}
foreach(['?int','result<int>','result_or_false<int>'] as $w){
 $bad[]='$a '.$w.'=1;$b result_or_bool<int> =2;$v=$a ?? $b;';
 $bad[]='$a result_or_bool<int> =1;$b '.$w.'=2;$v=$a ?? $b;';
}
$bad[]='$a ?int=1;$b result<int> =2;$v=$a ?? $b;';
$bad[]='$a ?int=1;$b ?float=2.0;$v=$a ?? $b;';
$bad[]='struct Row {public int $x;} $a ?Row; $b ?Row; $v=$a ?? $b;';
$rejected=[];
foreach($bad as $s){$error='';try{bscompile($s);}catch(\RuntimeException $e){$error=$e->getMessage();}bscheck($error!==''&&Model::$cpp_files->is_empty(),'Invalid source emitted: '.$s);$rejected[]=['source'=>$s,'diagnostic'=>$error];}
// Unchanged consumers must re-prepare after producer type changes, then recover.
foreach(['coalesce','boxed'] as $kind){
 $dir=$argv[1].'/retained-'.$kind;mkdir($dir);$a=$dir.'/producer.phs';$b=$dir.'/main.phs';
 $good=$kind==='coalesce'?'result<int>':'mixed';$badtype=$kind==='coalesce'?'result_or_bool<int>':'dynamic';
 $producer='function producer():'.$good.'{return 2;}';
 $caller=$kind==='coalesce'?'function caller():int{$a result<int>=1;return $a ?? producer();} echo caller();':'function caller():mixed{$a mixed=7;return $a & producer();}$n int=caller();echo $n;';
 $caller=str_replace('>=','> =',$caller);
 file_put_contents($a,$producer);file_put_contents($b,$caller);
 Compiler_Lifecycle::reset();$compiler=new Compiler();$compiler->init([$dir]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();
 $original=CPP_Probe::text(Model::$cpp_files);
 file_put_contents($a,'function producer():'.$badtype.'{return 2;}');$compiler->sync([$a]);
 $error='';try{$compiler->prepare();}catch(\RuntimeException $e){$error=$e->getMessage();}
 bscheck($error!==''&&Model::$cpp_files->is_empty(),'Retained type edit published output');
 $compiler->sync([]);$retry='';try{$compiler->prepare();}catch(\RuntimeException $e){$retry=$e->getMessage();}bscheck($retry===$error,'Retry changed diagnostic');
 file_put_contents($a,$producer);$compiler->sync([$a]);$compiler->prepare();$compiler->cpp();
 bscheck($original===CPP_Probe::text(Model::$cpp_files),'Repair differs');
 $compiler->sync([]);$compiler->prepare();$compiler->cpp();bscheck($original===CPP_Probe::text(Model::$cpp_files),'No-op differs');
 Compiler_Lifecycle::reset();$compiler=new Compiler();$compiler->init([$dir]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();bscheck($original===CPP_Probe::text(Model::$cpp_files),'Fresh differs');
 file_put_contents($argv[1].'/recovery-'.$kind.'.cpp',$original);
}
file_put_contents($argv[1].'/checks.json',json_encode(['boxed_forms'=>12,'coalesce_forms'=>12,'rejections'=>$rejected,'recovery_cycles'=>2],JSON_PRETTY_PRINT)."\n");
echo "Boxed/selection source preparation passed\n";
