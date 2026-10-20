<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$widening=$widening??false;$payload=$payload??'int';$zero=$payload==='float'?'0.0':'0';$ten=$payload==='float'?'10.0':'10';$three=$payload==='float'?'3.0':'3';
$carrier=$argv[2];if(!in_array($carrier,['nullable','result','result_or_false','result_or_bool','plain'],true))throw new \InvalidArgumentException('Expected carrier');
$wrapped=$carrier==='nullable'?'?'.$payload:$carrier.'<'.$payload.'>';$types=['n'=>$wrapped,'i'=>$payload];
$group=$argv[3]??'arithmetic';if(!in_array($group,['arithmetic','bitwise','cross','scalar','widening'],true))throw new \InvalidArgumentException('Expected compound group');
$forms=($group==='arithmetic'||$group==='scalar')?['add'=>'+','sub'=>'-','mul'=>'*','div'=>'/']:['mod'=>'%','band'=>'&','bor'=>'|','bxor'=>'^','shl'=>'<<','shr'=>'>>'];
$carriers=['nullable','result','result_or_false','result_or_bool'];$carrier_roles=['n'=>$carrier];
$pairs=[['n','n'],['n','i'],['i','n']];
if($group==='cross'){
 $right_carrier=$argv[4]??'';if(!in_array($right_carrier,$carriers,true)||$right_carrier===$carrier)throw new \InvalidArgumentException('Expected distinct right carrier');
 $types['r']=$right_carrier==='nullable'?'?'.$payload:$right_carrier.'<'.$payload.'>';$carrier_roles['r']=$right_carrier;$pairs=[['n','r']];
 $forms=['add'=>'+','sub'=>'-','mul'=>'*','div'=>'/','mod'=>'%','band'=>'&','bor'=>'|','bxor'=>'^','shl'=>'<<','shr'=>'>>'];
}
$rhs_failure=$group==='arithmetic'?'1%0':'1/0';$rhs_error=$group==='arithmetic'?5:4;
if($payload==='float'){$forms=['add'=>'+','sub'=>'-','mul'=>'*','div'=>'/'];$rhs_failure='1%0';$rhs_error=5;}
if($group==='scalar'){$types=['i'=>$payload];$wrapped=$payload;$carrier_roles=[];$pairs=[['i','i']];}
$tag_payloads=array_fill_keys(array_keys($types),$payload);
if($widening){
 $right_carrier=$argv[4]??'';if(!in_array($right_carrier,[...$carriers,'plain'],true))throw new \InvalidArgumentException('Expected RHS family');
 $wrapped=$carrier==='plain'?'float':($carrier==='nullable'?'?float':$carrier.'<float>');
 $right_type=$right_carrier==='plain'?'int':($right_carrier==='nullable'?'?int':$right_carrier.'<int>');
 $ltag=$carrier==='plain'?'i':'n';$rtag=$right_carrier==='plain'?'j':'r';$types=[$ltag=>$wrapped,$rtag=>$right_type];$pairs=[[$ltag,$rtag]];
 $carrier_roles=[];if($carrier!=='plain')$carrier_roles[$ltag]=$carrier;if($right_carrier!=='plain')$carrier_roles[$rtag]=$right_carrier;
 $tag_payloads=[$ltag=>'float',$rtag=>'int'];$three='3';
}
$scalar_pair=count($carrier_roles)===0;
$program='';$observe='';$signatures=[];$negative=0;
function compound_check(bool $ok,string $why):void{if(!$ok)throw new \LogicException($why);}
foreach($types as $tag=>$type){$zero=$tag_payloads[$tag]==='float'?'0.0':'0';$program.='class Box'.$tag.' { public '.$type.' $v = '.$zero.'; public static '.$type.' $s = '.$zero.'; } function box'.$tag.'('.$type.' $v): Box'.$tag.' { $b=new Box'.$tag.'(); $b->v=$v; return $b; } function read'.$tag.'(Box'.$tag.' $b): '.$type.' { return $b->v; } function nullbox'.$tag.'(): Box'.$tag.' { $b Box'.$tag.'; return $b; } function qualifier'.$tag.'(int &$calls): Box'.$tag.' { $calls++; return new Box'.$tag.'(); } function stateread'.$tag.'(): '.$type.' { return Box'.$tag.'::$s; } ';}
foreach($forms as $name=>$op)foreach($pairs as [$lt,$rt]){
 if($group==='cross'&&$payload==='int'){$rhs_failure=$name==='div'?'1%0':'1/0';$rhs_error=$name==='div'?5:4;}
 $left=$types[$lt];$right=$types[$rt];$id=$lt.$rt.$name;$signatures[]=[$left,$op.'=',$right,$left];
 $syntax=operator_test_source('$a '.$left.' = '.$ten.'; $b '.$right.' = '.$three.'; $r=($a '.$op.'= $b);');$node=$syntax->root->single_body()->body->statements[2]->expression->value;$facts=$node->require_compound_assignment_preparation();
 $payload_type=$payload==='float'?Language_Types::floating(Model::$language_scope):Language_Types::integer(Model::$language_scope);
 compound_check($facts->type->matches($node->target->require_preparation()->type) && !$facts->addressable() && count($facts->decision->operand_extractions)===($scalar_pair?0:2) && $facts->decision->result_type->matches($payload_type),'Compound facts lost result/extraction');
 compound_check($facts->write_back->target_type->matches($facts->type),'Writeback destination lost');
 if(!$scalar_pair)foreach([$lt,$rt] as $index=>$tag){$expected_extraction=!isset($carrier_roles[$tag])?conversion_operation::identity:($carrier_roles[$tag]==='nullable'?conversion_operation::nullable_extract:conversion_operation::result_extract);compound_check($facts->decision->operand_extractions[$index]->operation===$expected_extraction,'Wrong ordered extraction kind');$source_payload=$tag_payloads[$tag]==='float'?Language_Types::floating(Model::$language_scope):Language_Types::integer(Model::$language_scope);compound_check($facts->decision->operand_extractions[$index]->target_type->matches($source_payload),'Extraction lost native payload identity');}
 foreach([$lt,$rt] as $index=>$tag){$promotion=$widening&&$index===1?conversion_operation::integer_to_floating:conversion_operation::identity;compound_check($facts->decision->operands[$index]->operation===$promotion&&$facts->decision->operands[$index]->target_type->matches($payload_type),'Compound payload conversion lost');}
 $expected_writeback=$lt==='i'?conversion_operation::identity:($carrier_roles[$lt]==='nullable'?conversion_operation::nullable_wrap:conversion_operation::result_wrap);compound_check($facts->write_back->operation===$expected_writeback,'Wrong destination wrapping');Preparation_Cleanup::tree($syntax->root);compound_check($node->preparation()===null,'Stale compound facts');
 $program.='function plain'.$id.'('.$left.' &$a, '.$right.' $b): '.$left.' { return $a '.$op.'= $b; } '
 .'function rhs'.$id.'('.$left.' &$a, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$right.' { $calls++; $a=$replacement; if($fail){$bad='.$rhs_failure.';} return $b; } '
 .'function run'.$id.'('.$left.' &$a, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$left.' { return $a '.$op.'= rhs'.$id.'($a,$replacement,$b,$calls,$fail); } '
 .'function local'.$id.'('.$left.' $input, '.$right.' $b, '.$left.' &$final): '.$left.' { $a=$input; $r=($a '.$op.'= $b); $final=$a; return $r; } '
 .'function saved'.$id.'('.$left.' &$a, '.$right.' $b, '.$left.' $replacement): '.$left.' { $r=($a '.$op.'= $b); $a=$replacement; return $r; } '
 .'function propeffect'.$id.'(Box'.$lt.' $box, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$right.' { $calls++; $box->v=$replacement; if($fail){$bad='.$rhs_failure.';} return $b; } '
 .'function property'.$id.'(Box'.$lt.' $box, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$left.' { return $box->v '.$op.'= propeffect'.$id.'($box,$replacement,$b,$calls,$fail); } '
 .'function rebind'.$id.'(Box'.$lt.' &$box, Box'.$lt.' $replacement, '.$right.' $b, int &$calls,bool $fail): '.$right.' { $calls++; $box=$replacement; if($fail){$bad='.$rhs_failure.';} return $b; } '
 .'function owner'.$id.'(Box'.$lt.' &$box, Box'.$lt.' $replacement, '.$right.' $b, int &$calls,bool $fail): '.$left.' { return $box->v '.$op.'= rebind'.$id.'($box,$replacement,$b,$calls,$fail); } '
 .'function staticeffect'.$id.'('.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$right.' { $calls++; Box'.$lt.'::$s=$replacement; if($fail){$bad='.$rhs_failure.';} return $b; } '
 .'function static'.$id.'('.$left.' $a, '.$left.' $replacement, '.$right.' $b,int &$targets,int &$calls,bool $fail): '.$left.' { Box'.$lt.'::$s=$a; return qualifier'.$lt.'($targets)::$s '.$op.'= staticeffect'.$id.'($replacement,$b,$calls,$fail); } ';
 $le=$lt==='i'?'0':'unwrap_'.$lt;$re=isset($carrier_roles[$rt])?'unwrap_'.$rt:'0';
 $right_state=$tag_payloads[$rt]!==$payload?'IntState':'State';$compute='sa.value'.$op.($widening?'static_cast<double>(sb.value)':'sb.value');$computed_tail=$payload==='float'?',0,false':'';$arithmetic_error=in_array($name,['div','mod'],true)?'sb.value==0':'false';$compute_error=$name==='mod'?5:4;
 $safe=$name==='shl'?'sb.value>=0 && sb.value<64 && sa.value>=0 && sa.value<=(INT64_MAX>>sb.value)':($name==='shr'?'sb.value>=0 && sb.value<64':'true');
 $observe.='for(auto sa:states'.$lt.')for(auto sb:states'.$rt.') { if(!sa.kind && !sb.kind && !('.$safe.'))continue; bool left_ok=sa.kind==0; int basic_error=!left_ok?'.$le.':sb.kind?'.$re.':('.$arithmetic_error.'?'.$compute_error.':0); State computed=basic_error?sa:State{0,'.$compute.$computed_tail.'}; auto a=make'.$lt.'(sa); auto b=make'.$rt.'(sb); int failure=0; try { static_assert(std::is_same_v<decltype(function_plain'.$id.'(a,b)),'.($lt==='i'?'D':strtoupper($lt)).'>);auto r=function_plain'.$id.'(a,b);if(!same(r,computed))return 10;}catch(const std::exception& e){failure=classify(e);}if(failure!=basic_error || !same(a,basic_error?sa:computed) || !same(b,sb))return 11;++basic_cases;' . "\n";
 $observe.='if(!basic_error){ auto final=make'.$lt.'(sa);auto r=function_local'.$id.'(make'.$lt.'(sa),b,final);if(!same(r,computed)||!same(final,computed))return 12; } for(auto replacement:states'.$lt.')for(bool fail:{false,true}){ int expected=!left_ok?'.$le.':fail?'.$rhs_error.':basic_error; State final_state=!left_ok?sa:expected?replacement:computed; a=make'.$lt.'(sa); I calls{0};failure=0;try{auto r=function_run'.$id.'(a,make'.$lt.'(replacement),b,calls,B{fail});if(!same(r,computed))return 13;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||calls.native_value()!=(left_ok?1:0)||!same(a,final_state)||!same(b,sb))return 14;++rhs_cases;' . "\n";
 $observe.='auto box=function_box'.$lt.'(make'.$lt.'(sa));auto alias=box;calls=I{0};failure=0;try{auto r=function_property'.$id.'(box,make'.$lt.'(replacement),b,calls,B{fail});if(!same(r,computed))return 15;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||calls.native_value()!=(left_ok?1:0)||!same(function_read'.$lt.'(alias),final_state))return 16;++property_cases;' . "\n";
 $observe.='box=function_box'.$lt.'(make'.$lt.'(sa));auto original=box;auto other=function_box'.$lt.'(make'.$lt.'(replacement));calls=I{0};failure=0;try{auto r=function_owner'.$id.'(box,other,b,calls,B{fail});if(!same(r,computed))return 17;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||calls.native_value()!=(left_ok?1:0)||!same(function_read'.$lt.'(original),expected?sa:computed)||!same(function_read'.$lt.'(other),replacement)||!same(function_read'.$lt.'(box),left_ok?replacement:sa))return 18;++owner_cases;' . "\n";
 $observe.='calls=I{0};I targets{0};failure=0;try{auto r=function_static'.$id.'(make'.$lt.'(sa),make'.$lt.'(replacement),b,targets,calls,B{fail});if(!same(r,computed))return 19;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||targets.native_value()!=1||calls.native_value()!=(left_ok?1:0)||!same(function_stateread'.$lt.'(),final_state))return 20;++static_cases;' . "\n";
 $observe.='if(!basic_error){a=make'.$lt.'(sa);auto r=function_saved'.$id.'(a,b,make'.$lt.'(replacement));if(!same(r,computed)||!same(a,replacement))return 21;++snapshot_cases;} } } { auto box=function_nullbox'.$lt.'(); I calls{0};bool failed=false;try{(void)function_property'.$id.'(box,make'.$lt.'(State{0,99}),make'.$rt.'('.$right_state.'{0,3}),calls,B{false});}catch(const std::exception&){failed=true;}if(!failed||calls.native_value()!=0)return 22; }' . "\n";
 $dir=sys_get_temp_dir().'/scpp_compounds_'.bin2hex(random_bytes(6));mkdir($dir);$path=$dir.'/main.phs';$valid='function probe('.$left.' &$a, '.$right.' $b): '.$left.' {return $a '.$op.'= $b;}';
 try{file_put_contents($path,$valid);Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$dir]);$compiler->exec_cpp();file_put_contents($path,str_replace($left.' &$a','const '.$left.' &$a',$valid));foreach([[$path],[]]as$changes){$failed=false;try{$compiler->update_cpp($changes);}catch(\RuntimeException$e){$failed=true;}compound_check($failed&&Model::$cpp_files->is_empty()&&Model::$prepared_files->is_empty(),'Failed edit retained artifacts');}file_put_contents($path,$valid);$compiler->update_cpp([$path]);$out=CPP_Probe::text(Model::$cpp_files);$compiler->update_cpp([]);compound_check($out===CPP_Probe::text(Model::$cpp_files),'Noop mismatch');Compiler_Lifecycle::reset();$compiler->init([$dir]);$compiler->exec_cpp();compound_check($out===CPP_Probe::text(Model::$cpp_files),'Fresh mismatch');}finally{unlink($path);rmdir($dir);}
 foreach(['function bad(const '.$left.' &$a, '.$right.' $b):void{$a '.$op.'= $b;}','function sink('.$left.' &$a):void{} $a '.$left.' = '.$ten.'; $b '.$right.' = '.$three.'; sink($a '.$op.'= $b);','$a vector<'.$left.'> = ['.$ten.']; $b '.$right.' = '.$three.'; $a[0] '.$op.'= $b;'] as $bad){guarded_cross_reject($bad);++$negative;}
}
if(!$widening&&$group!=='cross'&&$group!=='scalar')foreach($forms as $name=>$op){$program.='function alias'.$name.'('.$wrapped.' &$a): '.$wrapped.' {return $a '.$op.'= $a;} function nested'.$name.'('.$wrapped.' &$a): '.$wrapped.' {return $a '.$op.'= $a++;} ';
 $error=in_array($name,['div','mod'],true)?'s.value==0':'false';$compute_error=$name==='mod'?5:4;
 $safe=$name==='shl'?'s.value>=0 && s.value<64 && s.value<=(INT64_MAX>>s.value)':($name==='shr'?'s.value>=0 && s.value<64':'true');$compute='s.value'.$op.'s.value';$computed_tail=$payload==='float'?',0,false':'';
 $observe.='for(auto s:statesn){if(!s.kind && !('.$safe.'))continue;int expected=s.kind?unwrap_n:('.$error.'?'.$compute_error.':0);State computed=expected?s:State{0,'.$compute.$computed_tail.'};auto a=maken(s);int failure=0;try{auto r=function_alias'.$name.'(a);if(!same(r,computed))return 30;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected||!same(a,expected?s:computed))return 31;a=maken(s);failure=0;try{auto r=function_nested'.$name.'(a);if(!same(r,computed))return 32;}catch(const std::exception&e){failure=classify(e);}State final_state=expected?(s.kind?s:State{0,s.value+1,0,false}):computed;if(failure!=expected||!same(a,final_state))return 33;++alias_cases;}' . "\n";
}
foreach(['**=']as$op){guarded_cross_reject('function bad('.$wrapped.' &$a, int $b):void{$a '.$op.' $b;}');++$negative;}
$excluded=$payload==='float'?['int','int16','bool','string','?int','result<int>','result_or_false<int>','result_or_bool<int>']:['float','int16','bool','string','?float','result<float>','?int16'];
foreach($excluded as $other)foreach($forms as $op){if(!($payload==='float'&&in_array($other,['int','?int','result<int>','result_or_false<int>','result_or_bool<int>'],true))){guarded_cross_reject('function bad('.$wrapped.' &$a, '.$other.' $b):void{$a '.$op.'= $b;}');++$negative;}if($payload==='float'){guarded_cross_reject('function bad('.$other.' &$a, '.$wrapped.' $b):void{$a '.$op.'= $b;}');++$negative;}}
if($payload==='float')foreach(['%=','&=','|=','^=','<<=','>>='] as $op){guarded_cross_reject('function bad('.$wrapped.' &$a, float $b):void{$a '.$op.' $b;}');++$negative;}

if($widening)$program.='function roundedcompound():float{$x float = 0.0;$x += 9007199254740993;return $x;}';
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.="\n#include <iostream>\n#include <vector>\nusing I=scpp::int_t<>;using B=scpp::bool_t;\n";
$cpp.="\n#include <cmath>\n#include <bit>\n#include <limits>\n";
$cpp.='using D='.($payload==='float'?'scpp::float_t':'I').';struct State{int kind;'.($payload==='float'?'double':'std::int64_t').' value;int error=0;bool exact=true;};';
$cpp.=$payload==='float'?'bool equal_value(double a,State s){if(s.exact)return std::bit_cast<std::uint64_t>(a)==std::bit_cast<std::uint64_t>(s.value);return (std::isnan(a)&&std::isnan(s.value))||(a==s.value&&(a!=0||std::signbit(a)==std::signbit(s.value)));}':'bool equal_value(std::int64_t a,State s){return a==s.value;}';
$cpp.='D makei(State s){return D{s.value};}bool same(const D&v,State s){return !s.kind&&equal_value(v.native_value(),s);}';

if($widening){$cpp.='struct IntState{int kind;std::int64_t value;int error=0;};bool equal_value(std::int64_t a,IntState s){return a==s.value;}I makej(IntState s){return I{s.value};}bool same(const I&v,IntState s){return !s.kind&&equal_value(v.native_value(),s);}';}
foreach($carrier_roles as $tag=>$role){
 $native=strtoupper($tag);$scalar_native=$tag_payloads[$tag]==='float'?'D':'I';$state_native=$tag_payloads[$tag]!==$payload?'IntState':'State';$cpp.='using '.$native.'=scpp::'.$role.'<'.$scalar_native.'>;'.$native.' make'.$tag.'('.$state_native.' s){if(!s.kind)return '.$native.'{'.$scalar_native.'{s.value}};';
 if($role==='nullable')$cpp.='return '.$native.'{scpp::null};';elseif($role==='result')$cpp.='return '.$native.'{scpp::error_t{scpp::string_t{s.error?"other":"failure"},I{s.error?19:17},scpp::string_t{s.error?"second":"origin"}}};';elseif($role==='result_or_bool')$cpp.='return s.kind==2?'.$native.'{scpp::true_sentinel}:'.$native.'{scpp::false_sentinel};';else$cpp.='return '.$native.'{scpp::false_sentinel};';
 $cpp.='}bool same(const '.$native.'&v,'.$state_native.' s){if(!s.kind)return v.has_value().native_value()&&equal_value(v.value().native_value(),s);if(v.has_value().native_value())return false;';
 if($role==='result')$cpp.='const auto*e=v.error();return e&&e->get_message().native_value()==(s.error?"other":"failure")&&e->get_line().native_value()==(s.error?19:17)&&e->get_file().native_value()==(s.error?"second":"origin");';elseif($role==='result_or_bool')$cpp.='return s.kind==2?v.is_true().native_value():v.is_false().native_value();';else$cpp.='return true;';
 $cpp.='}';
}
$cpp.='int classify(const std::exception&e){if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)){if(p->code()=="division_by_zero")return 4;if(p->code()=="modulo_by_zero")return 5;if(p->code()=="invalid_nullable_unwrap_empty")return 1;}std::string m=e.what();if(m.find("requires a present")!=std::string::npos){if(m.starts_with("scpp::result:"))return 2;if(m.starts_with("scpp::result_or_false:"))return 3;if(m.starts_with("scpp::result_or_bool:"))return 6;}return 90;}';
$cpp.='int main(){std::vector<State> statesi;';
$values=$group==='arithmetic'?'{-4,0,3,10}':'{-4,-1,0,1,3,10,31,62,63}';
if($payload==='float')$values='{0.0,-0.0,1.5,-2.5,std::numeric_limits<double>::infinity(),-std::numeric_limits<double>::infinity(),std::bit_cast<double>(std::uint64_t{0x7ff8000000000011}),std::bit_cast<double>(std::uint64_t{0xfff8000000000022})}';
$int_values='{INT64_MIN,INT64_MIN+1,-9007199254740993LL,-9007199254740992LL,-3LL,-1LL,0LL,1LL,3LL,9007199254740992LL,9007199254740993LL,INT64_MAX-1,INT64_MAX}';
if($widening){$cpp.='std::vector<IntState> statesj;for(std::int64_t v:std::vector<std::int64_t>'.$int_values.'){statesj.push_back({0,v});}';}
$cpp.='for(auto v:'.$values.'){statesi.push_back({0,v});}';
foreach($carrier_roles as $tag=>$role){
 $state_native=$tag_payloads[$tag]!==$payload?'IntState':'State';$tag_values=$tag_payloads[$tag]!==$payload?'std::vector<std::int64_t>'.$int_values:$values;
 $cpp.='constexpr int unwrap_'.$tag.'='.['nullable'=>1,'result'=>2,'result_or_false'=>3,'result_or_bool'=>6][$role].';std::vector<'.$state_native.'> states'.$tag.'{{1,0}};';
 if($role==='result_or_bool')$cpp.='states'.$tag.'.push_back({2,0});';if($role==='result')$cpp.='states'.$tag.'.push_back({1,0,1});';
 $cpp.='for(auto v:'.$tag_values.'){states'.$tag.'.push_back({0,v});}';
}
if($widening)$cpp.='if(function_roundedcompound().native_value()!=9007199254740992.0)return 40;';
$cpp.='long basic_cases=0,rhs_cases=0,property_cases=0,owner_cases=0,static_cases=0,snapshot_cases=0,alias_cases=0;'.$observe.'std::cout<<"basic_cases="<<basic_cases<<";rhs_cases="<<rhs_cases<<";property_cases="<<property_cases<<";owner_cases="<<owner_cases<<";static_cases="<<static_cases<<";snapshot_cases="<<snapshot_cases<<";alias_cases="<<alias_cases<<"\n";return 0;}';
file_put_contents($argv[1].'/int_carrier_compounds.cpp',$cpp);file_put_contents($argv[1].'/signatures.json',json_encode($signatures,JSON_PRETTY_PRINT)."\n");$count=count($signatures);echo "int carrier compounds: $count signatures, $count recoveries, $negative exclusions passed\n";
