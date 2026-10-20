<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$originals=[];
foreach(['int16','?int','result<int>','result_or_false<int>'] as $type){
 $source='$a '.$type.' = 7; $b '.$type.' = 2; $r = ($a . $b); return 0;';
 operator_test_source($source);$originals[]=$source;
}
file_put_contents($argv[1].'/originals.json',json_encode($originals,JSON_PRETTY_PRINT)."\n");
// Literal-null is a source expression, not an invented parameter type.
$types=[];
foreach(['plain','nullable','result','result_or_false','result_or_bool'] as $family)foreach(['int','float','bool','string'] as $payload){
 $type=$family==='plain'?$payload:($family==='nullable'?'?'.$payload:$family.'<'.$payload.'>');$types[]=[$family,$payload,$type];
}
foreach(['int8','int16','int32','int64','uint8','uint16','uint32'] as $width)$types[]=['plain',$width,$width];
$program='function nulls():string{return null . null;}';$observers='if(function_nulls().native_value()!="")return 1;';$signatures=[['null','.','null','string']];
$familyIds=['nullable'=>0,'result'=>1,'result_or_false'=>2,'result_or_bool'=>3,'plain'=>4];
$native=['int'=>['scpp::int_t<>','std::int64_t'],'float'=>['scpp::float_t','double'],'bool'=>['scpp::bool_t','bool'],'string'=>['scpp::string_t','std::string']];
foreach(['int8','int16','int32','int64','uint8','uint16','uint32'] as $width)$native[$width]=['scpp::int_t<std::'.$width.'_t>','std::'.$width.'_t'];
foreach($types as $id=>[$family,$payload,$type]){
 $program.='function left'.$id.'('.$type.' $v):string{return null . $v;} function right'.$id.'('.$type.' $v):string{return $v . null;}';
 $signatures[]=['null','.',$type,'string'];$signatures[]=[$type,'.','null','string'];
 $f=$familyIds[$family];[$v,$p]=$native[$payload];
 $observers.='{using V='.$v.';using P='.$p.';constexpr int F='.$f.';for(auto state:completion_states<F,mode::concat,P>()){auto value=make<F,V>(state);int expected=extraction<F,mode::concat>(state);auto expected_text=state.kind==state_kind::payload?text(state.value):std::string{};for(bool left:{false,true}){int failure=0;try{auto result=left?function_left'.$id.'(value):function_right'.$id.'(value);if(expected||result.native_value()!=expected_text)return 2;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||!same(value,state))return 3;++cases;}}}';
 if($payload==='string'){
  $program.='function append'.$id.'('.$type.' &$v):'.$type.'{return ($v .= null);}';$signatures[]=[$type,'.=','null',$type];
  $observers.='{using V='.$v.';using P='.$p.';constexpr int F='.$f.';for(auto state:completion_states<F,mode::append,P>()){auto value=make<F,V>(state);int expected=extraction<F,mode::append>(state);auto expected_text=state.kind==state_kind::payload?state.value:std::string{};auto final=written_state<true>(state,expected,expected_text);int failure=0;try{auto result=function_append'.$id.'(value);if(expected||!result_matches<true>(result,expected_text))return 4;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||!same(value,final))return 5;++cases;}}';
 }
}
$rejected=[];
foreach(['uint64','?int16','result<int16>','result_or_false<int16>','result_or_bool<int16>','?uint64','?result<int>','vector<int>','hash<string,int>'] as $type)foreach(['$v . "x"','"x" . $v'] as $expr)$rejected[]='function bad('.$type.' $v):string{return '.$expr.';}';
foreach(['mixed','dynamic'] as $boxed)foreach(['?int','result<float>','result_or_false<bool>','result_or_bool<string>','int16','uint32'] as $carrier){
 $rejected[]='function bad('.$boxed.' $a,'.$carrier.' $b):string{return $a . $b;}';$rejected[]='function bad('.$carrier.' $a,'.$boxed.' $b):string{return $a . $b;}';
}
foreach(['int','?int','float','result<bool>','bool'] as $type)$rejected[]='function bad('.$type.' &$a,string $b):void{$a .= $b;}';
foreach(['int16','?int','result<float>','result_or_false<bool>'] as $type)$rejected[]='function bad(vector<string> &$v,'.$type.' $rhs):void{$v[0] .= $rhs;}';
foreach(['?string','result<string>','result_or_false<string>','result_or_bool<string>'] as $target)foreach(['int','float','bool','null'] as $type){
 $rhs=$type==='null'?'null':'$rhs';$parameter=$type==='null'?'':','.$type.' $rhs';
 $rejected[]='function bad(vector<'.$target.'> &$v'.$parameter.'):void{$v[0] .= '.$rhs.';}';
}
// Keep value boundaries and explicit casts distinct from text-context conversion.
$rejected[]='function bad(?int $v):string{return $v;}';
$rejected[]='function take(string $v):void{} function bad(result<float> $v):void{take($v);}';
$rejected[]='function bad(?int $v):bool{return "1" == $v;}';
$rejected[]='class ObjectValue {} function bad(ObjectValue $v):string{return "x" . $v;}';
foreach($rejected as $source)guarded_cross_reject($source);
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.="\n#include \"".__DIR__."/vl07_completion_support.hpp\"\nusing namespace completion_probe;\nint main(){long cases=1;".$observers.'std::cout<<"cases="<<cases<<"\n";return 0;}';
file_put_contents($argv[1].'/controls.cpp',$cpp);file_put_contents($argv[1].'/signatures.json',json_encode($signatures,JSON_PRETTY_PRINT)."\n");file_put_contents($argv[1].'/rejections.json',json_encode($rejected,JSON_PRETTY_PRINT)."\n");
echo count($signatures)." literal-null rows and ".count($rejected)." scope rejections prepared\n";
