<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$payload=$argv[2];$family=$argv[3];$type=$family==='nullable'?'?'.$payload:$family.'<'.$payload.'>';
$rows=[];$program='';$checks='';$index=0;
foreach(['==','!=','===','!=='] as $op)foreach([false,true] as $reverse){
 $expression=$reverse?'null '.$op.' $a':'$a '.$op.' null';
 $source='function probe'.$index.'('.$type.' $a):bool{return '.$expression.';}';
 $admitted=$family==='nullable'&&in_array($op,['===','!=='],true);
 if($admitted){$program.=$source;$checks.='if(function_probe'.$index.'(v).native_value()!='.($op==='==='?'empty':'!empty').')return 10;';}
 else{guarded_cross_reject($source);}
 $rows[]=['kind'=>'binary','lhs'=>$reverse?'null':$type,'operator'=>$op,'rhs'=>$reverse?$type:'null','admitted'=>$admitted];++$index;
}
if($type==='?string'){$program.='function neg(?string $a):bool{return !$a;}';$checks.='if(function_neg(v).native_value()!=(empty||s.value.empty()||s.value=="0"))return 11;';$rows[]=['kind'=>'unary','lhs'=>$type,'operator'=>'!','rhs'=>'','admitted'=>true];}
if($program!==''){
 operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
 $native=['int'=>['scpp::int_t<>','std::int64_t'],'float'=>['scpp::float_t','double'],'bool'=>['scpp::bool_t','bool'],'string'=>['scpp::string_t','std::string']];
 $cpp.="\n#include \"".__DIR__."/cross_identity_support.hpp\"\nusing namespace identity_probe;\n";
 $cpp.='int main(){long cases=0;for(auto s:states<0,'.$native[$payload][1].'>()){auto v=make<0,'.$native[$payload][0].'>(s);bool empty=s.kind==state_kind::null_value;'.$checks.'if(!same(v,s))return 12;++cases;}std::cout<<"cases="<<cases<<"\\n";}';
 file_put_contents($argv[1].'/existing.cpp',$cpp);
}
file_put_contents($argv[1].'/signatures.json',json_encode($rows,JSON_PRETTY_PRINT)."\n");echo count($rows)." existing-path dispositions checked\n";
