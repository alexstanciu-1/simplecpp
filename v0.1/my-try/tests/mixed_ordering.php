<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/guarded_cross_support.php';

function guarded_mixed_check(bool $ok, string $message): void
{
	if (!$ok) { throw new \LogicException($message); }
}

$families = ['n'=>'?%s', 'r'=>'result<%s>', 'f'=>'result_or_false<%s>', 'b'=>'result_or_bool<%s>'];
$empty = ['n'=>'null', 'r'=>'error::make("kept",17,"origin")', 'f'=>'false_sentinel', 'b'=>'false_sentinel'];
$types = ['i'=>'int', 'd'=>'float'];
foreach ($families as $tag => $format) {
	$types[$tag . 'i'] = sprintf($format, 'int');
	$types[$tag . 'd'] = sprintf($format, 'float');
}
$lfamily=$argv[2];$rfamily=$argv[3];if(!in_array($lfamily,['','n','r','f','b'],true)||!in_array($rfamily,['','n','r','f','b'],true))throw new \InvalidArgumentException('Invalid family');
$binary=['lt'=>'<','le'=>'<=','gt'=>'>','ge'=>'>='];
$program = ''; $observers = ''; $helpers = ''; $states = ''; $negatives = [];
foreach ($types as $tag => $type)
{
	$program .= 'function mark' . $tag . '(' . $type . ' $v, int &$calls, int &$trace, int $digit, bool $fail): ' . $type
		. ' { $calls++; $trace = $trace*10+$digit; if ($fail) { if ($digit === 1) { $bad = 1%0; } else { $bad = 1/0; } } return $v; } ';
	$floating = str_ends_with($tag, 'd');
	$native = $floating ? 'double' : 'std::int64_t'; $scalar = $floating ? 'D' : 'I';
	$values = $floating ? '-2.5,0.0,-0.0,1.5,inf,-inf,nan,-nan,9007199254740992.0,std::nextafter(9007199254740992.0,inf),std::nextafter(9007199254740992.0,-inf),-9007199254740992.0,std::nextafter(-9007199254740992.0,inf),std::nextafter(-9007199254740992.0,-inf),static_cast<double>(INT64_MAX),std::nextafter(static_cast<double>(INT64_MAX),-inf),static_cast<double>(INT64_MIN),std::nextafter(static_cast<double>(INT64_MIN),inf),std::nextafter(static_cast<double>(INT64_MIN),-inf)' : 'INT64_MIN,INT64_MIN+1,-9007199254740993LL,-9007199254740992LL,-9007199254740991LL,-3,0,2,9007199254740991LL,9007199254740992LL,9007199254740993LL,INT64_MAX-1,INT64_MAX';
	if (strlen($tag) === 1) {
		$helpers .= $scalar . ' make' . $tag . '(State<' . $native . '> s) { return ' . $scalar . '{s.value}; }' . "\n";
		$states .= 'const State<' . $native . '> states' . $tag . '[]={' . $values . '};' . "\n";
		continue;
	}
	$family = $tag[0];
	$wrapper = ['n'=>'nullable','r'=>'result','f'=>'result_or_false','b'=>'result_or_bool'][$family];
	$cpp = 'scpp::' . $wrapper . '<' . $scalar . '>';
	$kind = ['n'=>'absent','r'=>'error','f'=>'false_sentinel','b'=>'false_sentinel'][$family];
	$missing = 'Kind::' . $kind . ($family === 'b' ? ',Kind::true_sentinel' : ($family==='r'?',State<'.$native.'>{Kind::error,1}':''));
	$states .= 'const State<' . $native . '> states' . $tag . '[]={' . $missing . ',' . $values . '}; const State<' . $native . '> empty' . $tag . '[]={' . $missing . '};' . "\n";
	$sentinel = ['n'=>'scpp::null','r'=>'scpp::error_t{scpp::string_t{s.error?"other":"kept"},I{s.error?19:17},scpp::string_t{s.error?"second":"origin"}}','f'=>'scpp::false_sentinel','b'=>'scpp::false_sentinel'][$family];
	$missing_ctor = $cpp . '{' . $sentinel . '}';
	if ($family === 'b') { $missing_ctor = '(s.kind==Kind::true_sentinel?' . $cpp . '{scpp::true_sentinel}:' . $missing_ctor . ')'; }
	$helpers .= $cpp . ' make' . $tag . '(State<' . $native . '> s) { return s.payload()?' . $cpp . '{' . $scalar . '{s.value}}:' . $missing_ctor . '; }' . "\n";
	$missing_check = ['n'=>'!v.has_value().native_value()', 'r'=>'v.has_error().native_value() && v.error()->get_message().native_value()==(s.error?"other":"kept") && v.error()->get_line().native_value()==(s.error?19:17) && v.error()->get_file().native_value()==(s.error?"second":"origin")', 'f'=>'v.is_false().native_value()', 'b'=>'(s.kind==Kind::true_sentinel?v.is_true().native_value():v.is_false().native_value())'][$family];
	$helpers .= 'bool same(const ' . $cpp . '& v,State<' . $native . '> s) { return s.payload()?v.has_value().native_value() && number_equal(v.require_value("observe").native_value(),s.value):(' . $missing_check . '); }' . "\n";
	// RHS may reset LHS to either boolean sentinel, after its old numeric value is captured.
	$reset = $family === 'b' ? 'if ($sentinel) { $a = true_sentinel; } else { $a = false_sentinel; }' : '$a = ' . $empty[$family] . ';';
	$other = $floating ? 'int' : 'float';
	$program .= 'function reset' . $tag . '(' . $type . ' &$a, ' . $other . ' $b, int &$calls, bool $sentinel): ' . $other . ' { $calls++; ' . $reset . ' return $b; } ';
	$code = ['n'=>1,'r'=>2,'f'=>3,'b'=>6][$family];
	$observers .= 'for(auto s:empty' . $tag . ') { int code=0; try { make' . $tag . '(s).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=' . $code . ') return 1; }' . "\n";
}
$pairs=[[$lfamily.'i',$rfamily.'d'],[$lfamily.'d',$rfamily.'i']];
foreach($pairs as [$lt,$rt])foreach($binary as $name=>$symbol){
 $left=$types[$lt];$right=$types[$rt];$id=$lt.$rt.$name;$left_float=str_ends_with($lt,'d');$right_float=str_ends_with($rt,'d');
 $syntax=operator_test_source('$a '.$left.' = '.($left_float?'1.5':'2').'; $b '.$right.' = '.($right_float?'1.5':'2').'; $out=($a '.$symbol.' $b);');
 $node=$syntax->root->single_body()->body->statements[2]->expression->value;$d=$node->require_binary_preparation()->decision;$guarded=strlen($lt)>1||strlen($rt)>1;
 guarded_mixed_check(count($d->operand_extractions)===($guarded?2:0)&&count($d->operands)===2&&count($d->operand_truthiness)===0,'Ordering conversion shape');
 guarded_mixed_check($d->result_type->matches(Language_Types::boolean(Model::$language_scope))&&!$node->require_preparation()->addressable(),'Ordering result must be bool value');
 foreach([$left_float,$right_float] as $index=>$floating){$payload=$floating?Language_Types::floating(Model::$language_scope):Language_Types::integer(Model::$language_scope);if($guarded)guarded_mixed_check($d->operand_extractions[$index]->target_type->matches($payload),'Extraction payload lost');guarded_mixed_check($d->operands[$index]->target_type->matches(Language_Types::floating(Model::$language_scope))&&$d->operands[$index]->operation===($floating?conversion_operation::identity:conversion_operation::integer_to_floating),'Ordering promotion lost');}
 Preparation_Cleanup::tree($syntax->root);guarded_mixed_check($node->preparation()===null,'Ordering cleanup retained facts');
 $program.='function binary'.$id.'('.$left.' $a, '.$right.' $b,int &$lc,int &$rc,int &$trace,bool $lf,bool $rf):bool{return mark'.$lt.'($a,$lc,$trace,1,$lf) '.$symbol.' mark'.$rt.'($b,$rc,$trace,2,$rf);} ';
 $le=strlen($lt)===1?0:['n'=>1,'r'=>2,'f'=>3,'b'=>6][$lt[0]];$re=strlen($rt)===1?0:['n'=>1,'r'=>2,'f'=>3,'b'=>6][$rt[0]];
 $expected='(static_cast<double>(a) '.$symbol.' static_cast<double>(b))';
 $observers.='for(auto sa:states'.$lt.')for(auto sb:states'.$rt.')for(bool lf:{false,true})for(bool rf:{false,true}){auto a=sa.value;auto b=sb.value;auto av=make'.$lt.'(sa);auto bv=make'.$rt.'(sb);I lc{0},rc{0},trace{0};int expected_error=lf?5:(!sa.payload()?'.$le.':0);bool reached=expected_error==0;if(reached)expected_error=rf?4:(!sb.payload()?'.$re.':0);int failure=0;try{static_assert(std::is_same_v<decltype(function_binary'.$id.'(av,bv,lc,rc,trace,B{lf},B{rf})),B>);auto out=function_binary'.$id.'(av,bv,lc,rc,trace,B{lf},B{rf});if(!expected_error&&out.native_value()!='.$expected.')return 10;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected_error||lc.native_value()!=1||rc.native_value()!=(reached?1:0)||trace.native_value()!=(reached?12:1)||!same(av,sa)||!same(bv,sb))return 11;++binary_cases;}' . "\n";
 guarded_cross_probe($lt,$rt,$left,$right,$name,$symbol,'bool',$expected,'true','number_equal',$left_float?'1.5':'2',true,$program,$observers);
 $program.='function box'.$id.'('.$left.' $a):CrossBox'.$id.'{$box=new CrossBox'.$id.'();$box->value=$a;return $box;} function read'.$id.'(CrossBox'.$id.' $box):'.$left.'{return $box->value;} '
 .'function rebind'.$id.'(CrossBox'.$id.' &$box,CrossBox'.$id.' $replacement,'.$right.' $b,int &$calls):'.$right.'{$calls++;$box=$replacement;return $b;} '
 .'function owner'.$id.'(CrossBox'.$id.' &$box,CrossBox'.$id.' $replacement,'.$right.' $b,int &$calls):bool{return $box->value '.$symbol.' rebind'.$id.'($box,$replacement,$b,$calls);} ';
 $observers.='for(auto sa:states'.$lt.')for(auto sb:states'.$rt.') {auto a=sa.value;auto b=sb.value;auto replacement=states'.$lt.'[0];auto box=function_box'.$id.'(make'.$lt.'(sa));auto original=box;auto other=function_box'.$id.'(make'.$lt.'(replacement));I calls{0};int expected_error=!sa.payload()?'.$le.':(!sb.payload()?'.$re.':0);int failure=0;try{auto out=function_owner'.$id.'(box,other,make'.$rt.'(sb),calls);if(!expected_error&&out.native_value()!='.$expected.')return 20;}catch(const std::exception&e){failure=classify(e);}if(failure!=expected_error||calls.native_value()!=(sa.payload()?1:0)||!same(function_read'.$id.'(original),sa)||!same(function_read'.$id.'(other),replacement)||!same(function_read'.$id.'(box),sa.payload()?replacement:sa))return 21;++owner_cases;}' . "\n";
}
foreach($pairs as [$lt,$rt]){
 foreach(['==','!='] as $symbol){if(guarded_fixture_equality_pair($types[$lt],$types[$rt],$symbol)){continue;}guarded_cross_reject('function bad('.$types[$lt].' $a,'.$types[$rt].' $b):bool{return $a '.$symbol.' $b;}');}
 foreach(['bool','string','int16','uint32','?bool','result<bool>','?int16','result<int16>'] as $other)foreach(['<','<=','>','>='] as $symbol){if(in_array($other,['int16','uint32'],true)&&str_ends_with($lt,'i'))continue;guarded_cross_reject('function bad('.$types[$lt].' $a,'.$other.' $b):bool{return $a '.$symbol.' $b;}');guarded_cross_reject('function bad('.$other.' $a,'.$types[$lt].' $b):bool{return $a '.$symbol.' $b;}');}
}
$program.='function roundedgt():bool{return 9007199254740993 > 9007199254740992.0;} function roundedle():bool{return 9007199254740993 <= 9007199254740992.0;}';
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <cmath>
#include <cstdint>
#include <limits>
#include <iostream>
#include <type_traits>
#include <iostream>
#include "scpp/generated/operator_detail.hpp"
using I=scpp::int_t<>; using D=scpp::float_t; using B=scpp::bool_t;
enum class Kind { payload, absent, error, false_sentinel, true_sentinel };
template<class T> struct State {
 Kind kind; T value; int error=0;
 State(T v):kind(Kind::payload),value(v) {}
 State(Kind k,int e=0):kind(k),value(0),error(e) {}
 bool payload() const { return kind==Kind::payload; }
};
template<class A,class C> bool number_equal(A a,C b) {
 if constexpr(std::is_floating_point_v<A> || std::is_floating_point_v<C>) {
  return (std::isnan(a)&&std::isnan(b)) || (a==b && (a!=0 || std::signbit(a)==std::signbit(b)));
 } else { return a==b; }
}
bool same(const I& v,State<std::int64_t> s) { return s.payload() && number_equal(v.native_value(),s.value); }
bool same(const D& v,State<double> s) { return s.payload() && number_equal(v.native_value(),s.value); }
int classify(const std::exception& e) {
 if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)) {
  if(p->code()=="invalid_nullable_unwrap_empty") return 1;
  if(p->code()=="division_by_zero") return 4;
  if(p->code()=="modulo_by_zero") return 5;
  return 90;
 }
 std::string text=e.what();
 if(text.find("requires a present value")==std::string::npos) return 91;
 if(text.starts_with("scpp::result:")) return 2;
 if(text.starts_with("scpp::result_or_false:")) return 3;
 if(text.starts_with("scpp::result_or_bool:")) return 6;
 return 92;
}
CPP;
$cpp.=$helpers."\nint main(){const double inf=std::numeric_limits<double>::infinity(),nan=std::numeric_limits<double>::quiet_NaN();long binary_cases=0,cross_snapshot_cases=0,cross_property_cases=0,owner_cases=0;\n".$states;
$cpp.='if(function_roundedgt().native_value()||!function_roundedle().native_value())return 30;'.$observers.'std::cout<<"binary_cases="<<binary_cases<<";snapshot_cases="<<cross_snapshot_cases<<";property_cases="<<cross_property_cases<<";owner_cases="<<owner_cases<<"\n";return 0;}';
file_put_contents($argv[1].'/mixed_ordering.cpp',$cpp);guarded_cross_finish($argv[1]);echo "Mixed ordering: 8 signatures and 2 recovery sequences passed\n";
