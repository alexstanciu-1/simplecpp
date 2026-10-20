<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$program= <<<'PHS'
function explicitnarrow(int $a,float $b):int{$a += (int)$b;return $a;}
function explicitbool(bool $a,int $b):bool{return (int)$a == $b;}
function explicittext(string $a,int $b):bool{return $a == (string)$b;}
function explicitcarrier(?float $a,?int $b):bool{return (int)(float)$a == (int)$b;}
function powergrouping():int{$a=2;$b=2;$out=($a **= $b **= 1+2);return $out+$b;}
function powersnapshot():int{$a=2;$out=($a **= $a++);return $out+$a;}
function indexinteger():int{$v vector<int> = [2];$v[0] += 1;$v[0] **= 3;return $v[0];}
function indextext():string{$v vector<string> = ["a"];$v[0] .= "b";return $v[0];}
PHS;
$rejected=[];
foreach(['int','?int','result<int>','result_or_false<int>','result_or_bool<int>'] as $t)foreach(['++$v[0]','$v[0]++','--$v[0]','$v[0]--'] as $expression){
 $source='function bad(vector<'.$t.'> &$v):void{'.$expression.';}';guarded_cross_reject($source);$rejected[]=$source;
}
foreach(['?int','result<int>','result_or_false<int>','result_or_bool<int>','float','?float','result<float>','result_or_false<float>','result_or_bool<float>'] as $t){
 $source='function bad(vector<'.$t.'> &$v,'.$t.' $rhs):void{$v[0] += $rhs;}';guarded_cross_reject($source);$rejected[]=$source;
}
foreach(['mixed'] as $other)foreach(['.','.='] as $op){
 $source='function bad(?string $a,'.$other.' $b):void{$a '.$op.' $b;}';guarded_cross_reject($source);$rejected[]=$source;
}
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.= <<<'CPP'

int main(){using I=scpp::int_t<>;using F=scpp::float_t;using B=scpp::bool_t;using S=scpp::string_t;
 if(function_explicitnarrow(I{4},F{2.75}).native_value()!=6)return 1;
 if(!function_explicitbool(B{true},I{1}).native_value()||function_explicitbool(B{true},I{2}).native_value())return 2;
 if(!function_explicittext(S{"17"},I{17}).native_value()||function_explicittext(S{"017"},I{17}).native_value())return 3;
 if(!function_explicitcarrier(scpp::nullable<F>{F{2.75}},scpp::nullable<I>{I{2}}).native_value())return 4;
 if(function_powergrouping().native_value()!=264||function_powersnapshot().native_value()!=8)return 6;
 if(function_indexinteger().native_value()!=27||function_indextext().native_value()!="ab")return 5;
}
CPP;
file_put_contents($argv[1].'/controls.cpp',$cpp);file_put_contents($argv[1].'/rejections.json',json_encode($rejected,JSON_PRETTY_PRINT)."\n");echo "explicit conversions and indexed boundary prepared\n";
