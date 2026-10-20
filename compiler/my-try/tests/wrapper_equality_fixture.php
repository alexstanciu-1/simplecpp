<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
function wrapper_equality_check(bool $ok, string $message): void {
	if (!$ok) { throw new \LogicException($message); }
}
// Payload selected by the bounded entry-point fixtures.
$carrier=$carrier ?? 'nullable';
$sentinel_carrier=in_array($carrier,['result_or_false','result_or_bool'],true);
$bool_sentinel=$sentinel_carrier && $payload==='bool';
$nonbool_sentinel=$sentinel_carrier && !$bool_sentinel;
$wrapped=$carrier==='nullable'?'?'.$payload:$carrier.'<'.$payload.'>';
$types=['n'=>$wrapped,'i'=>$payload];
$initial=$payload==='bool'?'false':($payload==='string'?'""':($payload==='float'?'0.0':'0'));
$second=$payload==='bool'?'true':($payload==='string'?'"a"':($payload==='float'?'1.0':'1'));
$forms=['eq'=>'==','ne'=>'!=','id'=>'===','nid'=>'!=='];
$operations=['eq'=>operator_operation::wrapper_equal,'ne'=>operator_operation::wrapper_not_equal,'id'=>operator_operation::wrapper_identical,'nid'=>operator_operation::wrapper_not_identical];
$program=''; $observers=''; $signatures=[];
foreach ($types as $tag=>$type) {
	$program.='class Box'.$tag.' { public '.$type.' $value='.$initial.'; } '
		.'function mark'.$tag.'('.$type.' $v, int &$calls, int &$trace, int $digit, bool $fail): '.$type.' { $calls++; $trace=$trace*10+$digit; if ($fail) { if ($digit===1) { $bad=1/0; } else { $bad=1%0; } } return $v; } ';
}
foreach ($forms as $name=>$symbol) foreach ([['n','n'],['n','i'],['i','n']] as [$lt,$rt]) {
	$id=$lt.$rt.$name; $left=$types[$lt]; $right=$types[$rt]; $invert=in_array($name,['ne','nid'],true);
	$oracle=in_array($name,['id','nid'],true)?'state_identical':'state_equal';
	if ($bool_sentinel && $lt!==$rt) { $oracle='state_identical'; }
	$expected=($invert?'!':'').$oracle.'(sa,sb)';
	$signatures[]=[$left,$symbol,$right,'bool'];
	$syntax=operator_test_source('$a '.$left.' = '.$initial.'; $b '.$right.' = '.$second.'; $out=($a '.$symbol.' $b);');
	$node=$syntax->root->single_body()->body->statements[2]->expression->value;
	$d=$node->require_binary_preparation()->decision;
	wrapper_equality_check($d->operation===$operations[$name] && count($d->operand_extractions)===0 && count($d->operands)===2 && $d->result_type->matches(Language_Types::boolean(Model::$language_scope)), 'Equality must retain states without extraction');
	foreach ($d->operands as $conversion) {
		wrapper_equality_check($conversion->operation===conversion_operation::identity && $conversion->source_type->matches($conversion->target_type),'Equality changed operand representation');
	}
	Preparation_Cleanup::tree($syntax->root);
	wrapper_equality_check($node->preparation()===null,'Cleanup retained equality facts');
	$program.='function binary'.$id.'('.$left.' $a, '.$right.' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): bool { return (mark'.$lt.'($a,$lc,$trace,1,$lf) '.$symbol.' mark'.$rt.'($b,$rc,$trace,2,$rf)); } '
		.'function reset'.$id.'('.$left.' &$a, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$right.' { $calls++; $a=$replacement; if ($fail) { $bad=1%0; } return $b; } '
		.'function snap'.$id.'('.$left.' &$a, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): bool { return ($a '.$symbol.' reset'.$id.'($a,$replacement,$b,$calls,$fail)); } '
		.'function resetbox'.$id.'(Box'.$lt.' $box, '.$left.' $replacement, '.$right.' $b, int &$calls): '.$right.' { $calls++; $box->value=$replacement; return $b; } '
		.'function prop'.$id.'('.$left.' $a, '.$left.' $replacement, '.$right.' $b, int &$calls, '.$left.' &$final): bool { $box=new Box'.$lt.'(); $box->value=$a; $out=($box->value '.$symbol.' resetbox'.$id.'($box,$replacement,$b,$calls)); $final=$box->value; return $out; } ';
	$call='function_binary'.$id.'(av,bv,lc,rc,trace,B{lf},B{rf})';
	$observers.='for(auto sa:states'.$lt.') for(auto sb:states'.$rt.') for(bool lf:{false,true}) for(bool rf:{false,true}) { auto av=make'.$lt.'(sa), saved=av; auto bv=make'.$rt.'(sb); I lc{0},rc{0},trace{0}; int failure=0; try { static_assert(std::is_same_v<decltype('.$call.'),B>); auto out='.$call.'; if(out.native_value()!=('.$expected.')) return 10; } catch(const std::exception& e) { failure=classify(e); } if(failure!=(lf?4:rf?5:0) || lc.native_value()!=1 || rc.native_value()!=(lf?0:1) || trace.native_value()!=(lf?1:12) || !same(av,sa) || !same(bv,sb)) return 11; ++binary_cases; }' . "\n";
	$observers.='for(auto sa:states'.$lt.') for(auto sb:states'.$rt.') for(auto replacement:states'.$lt.') { auto rv=make'.$lt.'(replacement); auto bv=make'.$rt.'(sb); for(bool fail:{false,true}) { auto av=make'.$lt.'(sa); I calls{0}; int failure=0; try { auto out=function_snap'.$id.'(av,rv,bv,calls,B{fail}); if(out.native_value()!=('.$expected.')) return 20; } catch(const std::exception& e) { failure=classify(e); } if(failure!=(fail?5:0) || calls.native_value()!=1 || !same(av,replacement) || !same(rv,replacement) || !same(bv,sb)) return 21; ++local_cases; } auto final=make'.$lt.'(sa); I calls{0}; auto out=function_prop'.$id.'(make'.$lt.'(sa),rv,bv,calls,final); if(out.native_value()!=('.$expected.') || calls.native_value()!=1 || !same(final,replacement)) return 22; ++property_cases; }' . "\n";
	$dir=sys_get_temp_dir().'/scpp_wrapper_equality_'.bin2hex(random_bytes(6)); mkdir($dir); $path=$dir.'/main.phs';
	$valid='function probe('.$left.' $a, '.$right.' $b): bool { return ($a '.$symbol.' $b); }';
	try {
		file_put_contents($path,$valid); Compiler_Lifecycle::reset(); $compiler=new Compiler(new cpp_lowering_options(false)); $compiler->init([$dir]); $compiler->exec_cpp();
		file_put_contents($path,str_replace('($a '.$symbol.' $b)','missing()',$valid));
		foreach ([[$path],[]] as $changes) { $failed=false; try { $compiler->update_cpp($changes); } catch (\RuntimeException $e) { $failed=true; } wrapper_equality_check($failed && Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(),'Failed edit retained artifacts'); }
		file_put_contents($path,str_replace('($a '.$symbol.' $b)','($b '.$symbol.' $a)',$valid)); $compiler->update_cpp([$path]); $output=CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]); wrapper_equality_check($output===CPP_Probe::text(Model::$cpp_files),'No-op changed output');
		Compiler_Lifecycle::reset(); $compiler->init([$dir]); $compiler->exec_cpp(); wrapper_equality_check($output===CPP_Probe::text(Model::$cpp_files),'Repair differs from fresh');
	} finally { unlink($path); rmdir($dir); }
}
$negative_count=0;
foreach ($forms as $symbol) {
	foreach (['int','bool','string','float','int16','?int','?bool','?string','?float','?int16','result<int>','result<float>','result<bool>','result<string>','result<int16>','error','result_or_false<'.$payload.'>','result_or_bool<'.$payload.'>'] as $other) {
		if ($other===$payload || $other===$wrapped) { continue; }
		if (guarded_fixture_equality_pair($wrapped,$other,$symbol)) { continue; }
		foreach ([[$wrapped,$other],[$other,$wrapped]] as [$left,$right]) {
			guarded_cross_reject('function bad('.$left.' $a, '.$right.' $b): bool { return ($a '.$symbol.' $b); }'); ++$negative_count;
		}
	}
	foreach (['int16'] as $excluded) foreach ([['?'.$excluded,'?'.$excluded],['?'.$excluded,$excluded],[$excluded,'?'.$excluded]] as [$left,$right]) {
		guarded_cross_reject('function bad('.$left.' $a, '.$right.' $b): bool { return ($a '.$symbol.' $b); }'); ++$negative_count;
	}
}
if ($sentinel_carrier) {
	foreach ($forms as $name=>$symbol) {
		foreach (['int','float','string'] as $excluded) foreach ([[$carrier.'<'.$excluded.'>',$carrier.'<'.$excluded.'>'],[$carrier.'<'.$excluded.'>',$excluded],[$excluded,$carrier.'<'.$excluded.'>']] as [$left,$right]) {
			if (guarded_fixture_equality_pair($left,$right)) { continue; }
			guarded_cross_reject('function bad('.$left.' $a, '.$right.' $b): bool { return $a '.$symbol.' $b; }'); ++$negative_count;
		}
		foreach (($carrier==='result_or_bool'?['false_sentinel','true_sentinel']:['false_sentinel']) as $sentinel) foreach (['$a '.$symbol.' '.$sentinel,$sentinel.' '.$symbol.' $a'] as $expression) {
			guarded_cross_reject('function bad('.$wrapped.' $a): bool { return '.$expression.'; }'); ++$negative_count;
		}
		if ($bool_sentinel) { $program.='function literal'.$name.'('.$wrapped.' $a): bool { return $a '.$symbol.' false; } function trueliteral'.$name.'('.$wrapped.' $a): bool { return $a '.$symbol.' true; } '; }
	}
	$program.='function explicitsentinel(): '.$wrapped.' { return false_sentinel; } ';
	if ($bool_sentinel) { $program.='function falsepayload(): '.$wrapped.' { return false; } function truepayload(): '.$wrapped.' { return true; } '; }
	else { $program.='function zeropayload(): '.$wrapped.' { return '.$initial.'; } '; }
	if ($carrier==='result_or_bool') { $program.='function explicittruesentinel(): '.$wrapped.' { return true_sentinel; } '; }
}
if ($payload==='float') { foreach (['===','!=='] as $symbol) { guarded_cross_reject('function bad(float $a, float $b): bool { return $a '.$symbol.' $b; }'); ++$negative_count; } }
if ($carrier==='nullable') { $program.='function nullid(?'.$payload.' $a): bool { return $a === null; } function nullnid(?'.$payload.' $a): bool { return null !== $a; } ';
}
$guard=$payload==='bool'?'!$a':('$a < '.$second);
$program.='function guarded('.$wrapped.' $a): bool { return '.$guard.'; } ';
if ($payload==='int' && $carrier==='nullable') { $program.='function guardedadd(?int $a): int { return $a+1; } '; }
operator_test_source($program);
$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.= <<<'CPP'

#include <limits>
#include <bit>
#include <cstdint>
#include <vector>
#include <type_traits>
#include <iostream>
using I=scpp::int_t<>; using B=scpp::bool_t;
CPP;
$cpp.= $payload==='float' ? <<<'CPP'
using V=scpp::float_t; using P=double;
bool payload_equal(P a,P b) { return a==b; }
V make_value(P v) { return V{v}; }
P observe(const V& v) { return v.native_value(); }
CPP
: ($payload==='int' ? <<<'CPP'
using V=I; using P=std::int64_t;
bool payload_equal(P a,P b) { return a==b; }
V make_value(P v) { return V{v}; }
P observe(const V& v) { return v.native_value(); }
CPP
: ($payload==='bool' ? <<<'CPP'
using V=B; using P=bool;
bool payload_equal(P a,P b) { return a==b; }
V make_value(P v) { return V{v}; }
P observe(const V& v) { return v.native_value(); }
CPP
: <<<'CPP'
using V=scpp::string_t; using P=std::string;
bool payload_equal(const P& a,const P& b) { if(a.size()!=b.size()) return false; for(std::size_t i=0;i<a.size();++i) if(static_cast<unsigned char>(a[i])!=static_cast<unsigned char>(b[i])) return false; return true; }
V make_value(const P& v) { return V{v}; }
P observe(const V& v) { return std::string(v.native_value()); }
CPP));
// Storage observation must distinguish signed zero and preserve NaN bits, unlike equality.
$cpp.= $payload==='float' ? 'bool storage_same(P a,P b) { return std::bit_cast<std::uint64_t>(a)==std::bit_cast<std::uint64_t>(b); }' : 'bool storage_same(const P& a,const P& b) { return payload_equal(a,b); }';
if ($bool_sentinel) {
	$cpp.='using N=scpp::'.$carrier.'<V>;';
	$cpp.= <<<'CPP'
struct State { bool present; P value; };
bool state_equal(const State& a,const State& b) { return a.present==b.present && a.value==b.value; }
bool state_identical(const State& a,const State& b) { return a.value==b.value; }
bool same(const N& v,State s) { return v.has_value().native_value()==s.present && (s.present?storage_same(observe(v.require_value("observe")),s.value):v.is_false().native_value()==!s.value); }
CPP;
	$cpp.='N maken(State s) { return s.present?N{make_value(s.value)}:'.($carrier==='result_or_bool'?'s.value?N{scpp::true_sentinel}:':'').'N{scpp::false_sentinel}; }';
} elseif ($nonbool_sentinel) {
	$cpp.='using N=scpp::'.$carrier.'<V>;';
	$cpp.= <<<'CPP'
struct State { bool present; P value; bool true_sentinel=false; };
bool state_equal(const State& a,const State& b) { return a.present==b.present && (a.present?payload_equal(a.value,b.value):a.true_sentinel==b.true_sentinel); }
bool state_identical(const State& a,const State& b) { return state_equal(a,b); }
bool same(const N& v,State s) { return v.has_value().native_value()==s.present && (s.present?storage_same(observe(v.require_value("observe")),s.value):v.is_false().native_value()==!s.true_sentinel); }
CPP;
	$cpp.='N maken(State s) { return s.present?N{make_value(s.value)}:'.($carrier==='result_or_bool'?'s.true_sentinel?N{scpp::true_sentinel}:':'').'N{scpp::false_sentinel}; }';
} else {
	$cpp.= ($carrier==='nullable' ? <<<'CPP'
using N=scpp::nullable<V>;
struct State { bool present; P value; };
bool state_equal(const State& a,const State& b) { return a.present==b.present && (!a.present || payload_equal(a.value,b.value)); }
bool state_identical(const State& a,const State& b) { return state_equal(a,b); }
N maken(State s) { return s.present?N{make_value(s.value)}:N{scpp::null}; }
bool same(const N& v,State s) { return v.has_value().native_value()==s.present && (!s.present || storage_same(observe(v.require_value("observe")),s.value)); }
CPP
: <<<'CPP'
using N=scpp::result<V>;
struct Failure { std::string message="failure"; std::int64_t line=17; std::string file="origin"; };
struct State { bool present; P value; Failure error{}; };
bool error_same(const Failure& a,const Failure& b) { return a.message==b.message && a.line==b.line && a.file==b.file; }
bool state_equal(const State& a,const State& b) { return a.present==b.present && (!a.present || payload_equal(a.value,b.value)); }
bool state_identical(const State& a,const State& b) { return a.present==b.present && (a.present?payload_equal(a.value,b.value):error_same(a.error,b.error)); }
N maken(State s) { return s.present?N{make_value(s.value)}:N{scpp::error_t{scpp::string_t{s.error.message},I{s.error.line},scpp::string_t{s.error.file}}}; }
bool same(const N& v,State s) { if(s.present) return v.has_value().native_value() && storage_same(observe(v.require_value("observe")),s.value); if(!v.has_error().native_value()) return false; const auto* e=v.error(); return e && e->has_error().native_value() && e->get_message().native_value()==s.error.message && e->get_line().native_value()==s.error.line && e->get_file().native_value()==s.error.file; }
CPP);
}
$cpp.= <<<'CPP'
V makei(State s) { return make_value(s.value); }
bool same(const V& v,State s) { return s.present && storage_same(observe(v),s.value); }
int classify(const std::exception& e) { if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)) { if(p->code()=="division_by_zero") return 4; if(p->code()=="modulo_by_zero") return 5; if(p->code()=="invalid_nullable_unwrap_empty") return 1; } std::string message=e.what(); if(message.starts_with("scpp::result:") && message.find("requires a present value")!=std::string::npos) return 2; if(message.starts_with("scpp::result_or_false:") && message.find("requires a present value")!=std::string::npos) return 3; if(message.starts_with("scpp::result_or_bool:") && message.find("requires a present value")!=std::string::npos) return 6; return 90; }
int main() {
 std::vector<State> statesi,statesn={{false,P{}}};
CPP;
if ($carrier==='result_or_bool') { $cpp.=$bool_sentinel?'statesn.push_back({false,true});':'statesn.push_back({false,P{},true});'; }
if ($carrier==='result') { $cpp.='statesn.push_back({false,P{},Failure{"different",17,"origin"}}); statesn.push_back({false,P{},Failure{"failure",18,"origin"}}); statesn.push_back({false,P{},Failure{"failure",17,"elsewhere"}}); statesn.push_back({false,P{},Failure{"",0,""}});'; }
$values=$payload==='int'?'std::vector<P>{std::numeric_limits<std::int64_t>::min(),std::numeric_limits<std::int64_t>::max(),-9007199254740993LL,9007199254740993LL,-1,0,1}':($payload==='bool'?'std::vector<P>{false,true}': 'std::vector<P>{"","a","ab","b","A","0","01","1","10","2",P{"a\0b",3},P{"a\0c",3},P{"\0",1},P{"\x7f",1},P{"\x80",1},P{"\xff",1},"é","é"}');
if ($payload==='float') { $values='std::vector<P>{0.0,-0.0,1.0,-1.0,1.5,std::numeric_limits<P>::max(),std::numeric_limits<P>::lowest(),std::numeric_limits<P>::min(),std::numeric_limits<P>::denorm_min(),std::numeric_limits<P>::infinity(),-std::numeric_limits<P>::infinity(),std::bit_cast<P>(std::uint64_t{0x7ff8000000000001ULL}),std::bit_cast<P>(std::uint64_t{0x7ff8000000000002ULL})}'; }
$cpp.='for(P value:'.$values.') { statesi.push_back({true,value}); statesn.push_back({true,value}); }';
$cpp.='long binary_cases=0,local_cases=0,property_cases=0;';
if ($carrier==='nullable') {
	$cpp.='for(auto s:statesn) { auto v=maken(s); if(function_nullid(v).native_value()==s.present || function_nullnid(v).native_value()!=s.present || !same(v,s)) return 30; }';
} elseif ($carrier==='result') {
	$cpp.='bool rejected=false; try { N invalid{scpp::error_t::no_error}; } catch(const std::invalid_argument&) { rejected=true; } if(!rejected) return 33;';
}
$cpp.=' for(auto s:statesn) { if(s.present) continue; int failure=0; try { (void)function_guarded(maken(s)); } catch(const std::exception& e) { failure=classify(e); } if(failure!='.($carrier==='nullable'?'1':($carrier==='result'?'2':($carrier==='result_or_false'?'3':'6'))).') return 31; }';
if ($payload==='int' && $carrier==='nullable') { $cpp.=' { int failure=0; try { (void)function_guardedadd(N{scpp::null}); } catch(const std::exception& e) { failure=classify(e); } if(failure!=1) return 32; } '; }
if ($bool_sentinel) {
	$cpp.='if(!same(function_explicitsentinel(),State{false,false}) || !same(function_falsepayload(),State{true,false}) || !same(function_truepayload(),State{true,true})) return 34; for(auto s:statesn) { auto v=maken(s); bool expected=!s.value; if(function_literaleq(v).native_value()!=expected || function_literalid(v).native_value()!=expected || function_literalne(v).native_value()==expected || function_literalnid(v).native_value()==expected || function_trueliteraleq(v).native_value()==expected || function_trueliteralid(v).native_value()==expected || function_trueliteralne(v).native_value()!=expected || function_trueliteralnid(v).native_value()!=expected || !same(v,s)) return 35; }';
}
if ($nonbool_sentinel) { $cpp.='if(!same(function_explicitsentinel(),State{false,P{}}) || !same(function_zeropayload(),State{true,P{}})) return 34;'; }
if ($carrier==='result_or_bool') { $cpp.='if(!same(function_explicittruesentinel(),'.($bool_sentinel?'State{false,true}':'State{false,P{},true}').')) return 36;'; }
$cpp.=$observers.'std::cout << "binary_cases=" << binary_cases << ";local_cases=" << local_cases << ";property_cases=" << property_cases << "\n"; return 0; }';
file_put_contents($argv[1].'/wrapper_equality.cpp',$cpp);
file_put_contents($argv[1].'/signatures.json',json_encode($signatures,JSON_PRETTY_PRINT)."\n");
echo $carrier.' '.$payload.' equality: '.count($signatures).' signatures, '.count($signatures).' recovery sequences, '.$negative_count." exclusions passed\n";
