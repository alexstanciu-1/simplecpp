<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/guarded_cross_support.php';

function guarded_check(bool $ok, string $message): void
{
	if (!$ok) {
		throw new \LogicException($message);
	}
}

$types = ['n' => '?int', 'r' => 'result<int>', 'f' => 'result_or_false<int>', 'b' => 'result_or_bool<int>', 'i' => 'int'];
$empty = ['n' => 'null', 'r' => 'error::make("kept", 17, "origin")', 'f' => 'false_sentinel', 'b' => 'false_sentinel'];
$spaceship_mode = ($argv[2] ?? '') === '--spaceship';
$binary = ['add'=>'+','sub'=>'-','mul'=>'*','div'=>'/','mod'=>'%','pow'=>'**',
	'lt'=>'<','le'=>'<=','gt'=>'>','ge'=>'>=', 'band'=>'&','bor'=>'|','bxor'=>'^','shl'=>'<<','shr'=>'>>',
	'land'=>'&&','lor'=>'||','wand'=>'and','wor'=>'or'];
$unary = ['pos'=>'+','neg'=>'-','inv'=>'~','not'=>'!'];
if ($spaceship_mode) { $binary = ['cmp'=>'<=>']; guarded_spaceship_exclusions('int'); }
$negatives = [];
$program = '';
$observers = '';
foreach ($types as $tag => $type)
{
	$program .= 'function mark' . $tag . '(' . $type . ' $v, int &$calls, int &$trace, int $digit, bool $fail): ' . $type
		. ' { $calls++; $trace = $trace * 10 + $digit; if ($fail) { $bad = 1 / 0; } return $v; } ';
	if ($tag === 'i') {
		continue;
	}
	$program .= 'function reset' . $tag . '(' . $type . ' &$v, int $out, int &$calls, bool $sentinel): int { $calls++; ' . ($tag === 'b' ? 'if ($sentinel) { $v = true_sentinel; } else { $v = false_sentinel; }' : '$v = ' . $empty[$tag] . ';') . ' return $out; } ';
	foreach ($unary as $name => $symbol)
	{
		$ret = $name === 'not' ? 'bool' : 'int';
		$cppret = $name === 'not' ? 'B' : 'I';
		$syntax = operator_test_source('$v ' . $type . ' = 2; $out = ' . $symbol . '$v;');
		$node = $syntax->root->single_body()->body->statements[1]->expression->value;
		$d = $node->require_unary_preparation()->decision;
		guarded_check(count($d->operand_extractions) === 1 && $d->operand_extractions[0]->target_type->matches(Language_Types::integer(Model::$language_scope)), 'Unary lost extraction stage');
		Preparation_Cleanup::tree($syntax->root);
		guarded_check($node->preparation() === null, 'Unary cleanup retained facts');
		$program .= 'function unary' . $tag . $name . '(' . $type . ' $v, int &$calls, int &$trace): ' . $ret
			. ' { return ' . $symbol . 'mark' . $tag . '($v, $calls, $trace, 1, false); } ';
		$call = 'function_unary' . $tag . $name . '(make' . $tag . '(sa),calls,trace)';
		$expect = $symbol . 'a';
		$observers .= 'for(State sa:states' . $tag . ') { long a=sa.value; I calls{0},trace{0}; int failure=0; try { static_assert(std::is_same_v<decltype(' . $call . '),' . $cppret . '>); auto out=' . $call . '; if(out.native_value()!=(' . $expect . ')) return 10; } catch(const std::exception& e) { failure=classify(e); } if(failure!=(!sa.payload()?' . ['n'=>1,'r'=>2,'f'=>3,'b'=>6][$tag] . ':0) || calls.native_value()!=1 || trace.native_value()!=1) return 11; }' . "\n";
	}
	foreach ($binary as $name => $symbol)
	{
		$logical = in_array($name, ['land','lor','wand','wor'], true);
		$isand = in_array($name, ['land','wand'], true);
		$bool = $logical || in_array($name, ['lt','le','gt','ge'], true);
		$ret = $bool ? 'bool' : 'int'; $cppret = $bool ? 'B' : 'I';
		$expected = $name === 'pow' ? 'power(a,b)' : ($logical ? '(a!=0 ' . ($isand?'&&':'||') . ' b!=0)' : '(a ' . $symbol . ' b)');
		if ($spaceship_mode) { $expected = '(a<b?-1:(a>b?1:0))'; }
		$pairs = $cross_mode ? guarded_cross_pairs($tag) : [[$tag,$tag],[$tag,'i'],['i',$tag]];
		if ($spaceship_mode) { $pairs = array_merge($pairs, guarded_cross_pairs($tag)); }
		foreach ($pairs as [$lt,$rt])
		{
			$id = $lt . $rt . $name;
			$syntax = operator_test_source('$a ' . $types[$lt] . ' = 2; $b ' . $types[$rt] . ' = 3; $out = ($a ' . $symbol . ' $b);');
			$node = $syntax->root->single_body()->body->statements[2]->expression->value;
			$d = $node->require_binary_preparation()->decision;
			guarded_check(count($d->operand_extractions) === 2, 'Binary lost ordered extraction stage');
			foreach ($d->operand_extractions as $extraction) {
				guarded_check($extraction->target_type->matches(Language_Types::integer(Model::$language_scope)), 'Extraction did not select int payload');
			}
			guarded_check($d->result_type->matches($bool ? Language_Types::boolean(Model::$language_scope) : Language_Types::integer(Model::$language_scope)), 'Lifted result is not plain scalar');
			Preparation_Cleanup::tree($syntax->root);
			guarded_check($node->preparation() === null, 'Binary cleanup retained facts');
			$program .= 'function binary' . $id . '(' . $types[$lt] . ' $a, ' . $types[$rt] . ' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): ' . $ret
				. ' { return (mark' . $lt . '($a,$lc,$trace,1,$lf) ' . $symbol . ' mark' . $rt . '($b,$rc,$trace,2,$rf)); } ';
			$call = 'function_binary' . $id . '(av,bv,lc,rc,trace,B{lf},B{rf})';
			$la = ($spaceship_mode?'boundary':'states') . $lt; $rb=($spaceship_mode?'boundary':'states') . $rt;
			$le = $lt==='i'?'0':strval(['n'=>1,'r'=>2,'f'=>3,'b'=>6][$lt]); $re = $rt==='i'?'0':strval(['n'=>1,'r'=>2,'f'=>3,'b'=>6][$rt]);
			$reach = $logical ? ($isand?'a!=0':'a==0') : 'true';
			$operror = $name==='div'?'(b==0?4:0)':($name==='mod'?'(b==0?5:0)':'0');
			$observers .= 'for(State sa:' . $la . ') for(State sb:' . $rb . ') for(bool lf:{false,true}) for(bool rf:{false,true}) { long a=sa.value,b=sb.value; if(!valid("' . $name . '",a,b)) continue; auto av=make' . $lt . '(sa); auto bv=make' . $rt . '(sb); I lc{0},rc{0},trace{0}; int expected_error=lf?4:(!sa.payload()?' . $le . ':0); bool reached=expected_error==0 && (' . $reach . '); if(reached) expected_error=rf?4:(!sb.payload()?' . $re . ':' . $operror . '); int failure=0; try { static_assert(std::is_same_v<decltype(' . $call . '),' . $cppret . '>); auto out=' . $call . '; if(expected_error==0 && out.native_value()!=' . $expected . ') return 20; } catch(const std::exception& e) { failure=classify(e); } if(failure!=expected_error || lc.native_value()!=1 || rc.native_value()!=(reached?1:0) || trace.native_value()!=(reached?12:1)) return 21; if(!same(av,sa)||!same(bv,sb)) return 22; ++binary_cases; }' . "\n";
			if ($cross_mode || $spaceship_mode) { guarded_cross_probe($lt,$rt,$types[$lt],$types[$rt],$name,$symbol,$ret,$expected,$reach,'integer','2',false,$program,$observers); }
		}
		// RHS resets the actual LHS storage. Payload extraction must have finished first.
		$program .= 'function snap' . $tag . $name . '(' . $type . ' &$a, int $b, int &$calls, bool $sentinel): ' . $ret
			. ' { return ($a ' . $symbol . ' reset' . $tag . '($a,$b,$calls,$sentinel)); } ';
		$reach = $logical ? ($isand?'a!=0':'a==0') : 'true';
		$observers .= 'for(State sa:statesi) for(State sb:statesi) for(State empty:empty' . $tag . ') { long a=sa.value,b=sb.value; if(!valid("' . $name . '",a,b)) continue; if((' . ($name==='div'||$name==='mod'?'b==0':'false') . ')) continue; auto av=make' . $tag . '(sa); I calls{0}; auto out=function_snap' . $tag . $name . '(av,I{b},calls,B{empty.kind==Kind::true_sentinel}); bool reached=' . $reach . '; if(out.native_value()!=' . $expected . ' || calls.native_value()!=(reached?1:0) || !same(av,reached?empty:sa)) return 30; }' . "\n";
	}
	// Direct conditions/selectors retain absence behavior independently of guarded !.
	$program .= 'function condition' . $tag . '(' . $type . ' $v): bool { if ($v) { return true; } return false; } '
		. 'function ternary' . $tag . '(' . $type . ' $v): int { return $v ? 7 : 9; } '
		. 'function elvis' . $tag . '(' . $type . ' $v): ' . $type . ' { return $v ?: 9; } '
		. ($tag === 'b' ? '' : 'function coalesce' . $tag . '(' . $type . ' $v): int { return $v ?? 9; } ')
		. 'function present' . $tag . '(' . $type . ' $v): bool { return isset($v); } ';
	$observers .= 'for(State sa:states' . $tag . ') { auto av=make' . $tag . '(sa); bool truth=sa.truth(); if(function_condition' . $tag . '(av).native_value()!=truth || function_ternary' . $tag . '(av).native_value()!=(truth?7:9) || !same(function_elvis' . $tag . '(av),truth?sa:State{9}) || ' . ($tag === 'b' ? '' : 'function_coalesce' . $tag . '(av).native_value()!=(sa.payload()?sa.value:9) || ') . 'function_present' . $tag . '(av).native_value()!=sa.present() || !same(av,sa)) return 40; }' . "\n";
	if ($tag === 'b') {
		$program .= 'function takeb(result_or_bool<int> $v, int &$out, bool &$flag): bool { return take($out,$flag) = $v; } ';
		$observers .= 'for(State sa:statesb) for(bool initial:{false,true}) { I out{99}; B flag{initial}; auto av=makeb(sa); bool taken=function_takeb(av,out,flag).native_value(); if(taken!=sa.present() || out.native_value()!=(sa.payload()?sa.value:99) || flag.native_value()!=(sa.payload()?initial:sa.kind==Kind::true_sentinel) || !same(av,sa)) return 43; }' . "\n";
		$negatives[] = 'function bad(result_or_bool<int> $v): int { return $v ?? 9; }';
		$negatives[] = 'function bad(result_or_bool<int> $v): int { return 9 ?? $v; }';
	}

	$program .= 'function word' . $tag . '(' . $type . ' &$v): bool { return ($v = 0 or 2); } '
		. 'function grouped' . $tag . '(' . $type . ' $v, bool &$out): void { $out = ($v and 2); } ';
	$observers .= '{ auto av=make' . $tag . '(3); B out{true}; if(!function_word' . $tag . '(av).native_value() || !same(av,0)) return 41; function_grouped' . $tag . '(av,out); if(out.native_value()) return 42; }' . "\n";
}

foreach (['?int','result<int>','result_or_false<int>','result_or_bool<int>'] as $type)
{
	foreach (['+=','-=','*=','/=','%=','**=','&=','|=','^=','<<=','>>='] as $symbol) {
		if (in_array($symbol,['+=', '-=', '*=', '/=', '%=', '&=', '|=', '^=', '<<=', '>>='],true)) { continue; }
		$negatives[] = '$a ' . $type . ' = 1; $a ' . $symbol . ' 2;';
		$negatives[] = '$a ' . $type . ' = 1; $b = 2; $b ' . $symbol . ' $a;';
	}
	foreach (['bool','int16','string'] as $other) {
		$negatives[] = 'function bad(' . $type . ' $a, ' . $other . ' $b): void { $out = $a + $b; }';
		$negatives[] = 'function bad(' . $other . ' $a, ' . $type . ' $b): void { $out = $a + $b; }';
	}

	$negatives[] = '$a ' . $type . ' = 0; $a && missing();';
	$negatives[] = '$a ' . $type . ' = 1; $a || "x";';
	foreach (['+','<','&&','||'] as $symbol)
	{
		$directory = sys_get_temp_dir() . '/scpp_guarded_' . bin2hex(random_bytes(6));
		mkdir($directory); $path = $directory . '/main.phs';
		$valid = '$a ' . $type . ' = 2; $b = 3; $c = ($a ' . $symbol . ' $b);';
		try
		{
			file_put_contents($path, $valid); Compiler_Lifecycle::reset();
			$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
			file_put_contents($path, str_replace('$b = 3', '$b = "bad"', $valid));
			foreach ([[$path], []] as $changes)
			{
				$failed = false;
				try { $compiler->update_cpp($changes); }
				catch (\RuntimeException $e) { $failed = true; }
				guarded_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Failed guarded edit retained artifacts');
			}
			file_put_contents($path, str_replace('$b = 3', '$b = 7', $valid)); $compiler->update_cpp([$path]);
			$repaired = CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]);
			guarded_check($repaired === CPP_Probe::text(Model::$cpp_files), 'No-op guarded output changed');
			Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->exec_cpp();
			guarded_check($repaired === CPP_Probe::text(Model::$cpp_files), 'Guarded repair differs from fresh');
		}
		finally { unlink($path); rmdir($directory); }
	}
}
foreach ($negatives as $source)
{
	$failed = false;
	try { operator_test_source($source); }
	catch (\RuntimeException $error) { $failed = true; }
	guarded_check($failed && Model::$cpp_files->is_empty(), 'Excluded guarded expression admitted: ' . $source);
}
$program .= <<<'PHS'
function nested(?int $a, ?int $b, ?int $c, int &$calls, int &$trace): bool {
 return (markn($a,$calls,$trace,1,false) && markn($b,$calls,$trace,2,false)) || (markn($c,$calls,$trace,3,false) || markn($b,$calls,$trace,4,false));
}
function presence(?int $v): bool { return $v === null; }
PHS;
if ($cross_mode || $spaceship_mode) { $observers .= 'std::cout << "cross_snapshot_cases=" << cross_snapshot_cases << ";cross_property_cases=" << cross_property_cases << "\n";'; }
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <iostream>
#include <type_traits>
#include <limits>
#include <vector>
using I=scpp::int_t<>; using B=scpp::bool_t;
using N=scpp::nullable<I>; using R=scpp::result<I>; using F=scpp::result_or_false<I>; using RB=scpp::result_or_bool<I>;
enum class Kind { payload, absent, error, false_sentinel, true_sentinel };
struct State {
 Kind kind; long value;
 State(long v):kind(Kind::payload),value(v) {}
 State(Kind k):kind(k),value(0) {}
 bool payload() const { return kind==Kind::payload; }
 bool truth() const { return kind==Kind::true_sentinel || (payload() && value!=0); }
 bool present() const { return payload() || kind==Kind::true_sentinel; }
};
N maken(State s) { return s.payload()?N{I{s.value}}:N{scpp::null}; }
R maker(State s) { return s.payload()?R{I{s.value}}:R{scpp::error_t{scpp::string_t{"kept"},I{17},scpp::string_t{"origin"}}}; }
F makef(State s) { return s.payload()?F{I{s.value}}:F{scpp::false_sentinel}; }
RB makeb(State s) { return s.payload()?RB{I{s.value}}:s.kind==Kind::true_sentinel?RB{scpp::true_sentinel}:RB{scpp::false_sentinel}; }
I makei(State s) { return I{s.value}; }
bool same(const I& v,State s) { return s.payload() && v.native_value()==s.value; }
bool same(const N& v,State s) { return !s.payload()?!v.has_value().native_value():v.has_value().native_value()&&v.require_value("observe").native_value()==s.value; }
bool same(const R& v,State s) { return !s.payload()?v.has_error().native_value()&&v.error()->get_message().native_value()=="kept"&&v.error()->get_line().native_value()==17&&v.error()->get_file().native_value()=="origin":v.has_value().native_value()&&v.require_value("observe").native_value()==s.value; }
bool same(const F& v,State s) { return !s.payload()?v.is_false().native_value():v.has_value().native_value()&&v.require_value("observe").native_value()==s.value; }
bool same(const RB& v,State s) { return s.payload()?v.has_value().native_value()&&v.require_value("observe").native_value()==s.value:s.kind==Kind::true_sentinel?v.is_true().native_value():v.is_false().native_value(); }
// Avoid unrelated negative-exponent semantics and native shift-domain behavior in the oracle.
bool valid(std::string_view op,long a,long b) { return !((op=="pow" && b<0) || ((op=="shl" || op=="shr") && (a<0 || b<0))); }
long power(long a,long b) { long out=1; for(long i=0;i<b;++i) out*=a; return out; }
int classify(const std::exception& e) {
 if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)) {
  if(p->code()=="invalid_nullable_unwrap_empty") return 1;
  if(p->code()=="division_by_zero") return 4;
  if(p->code()=="modulo_by_zero") return 5;
  return 90;
 }
 std::string text=e.what();
 if(text.starts_with("scpp::result:") && text.find("requires a present value")!=std::string::npos) return 2;
 if(text.starts_with("scpp::result_or_false:") && text.find("requires a present value")!=std::string::npos) return 3;
 if(text.starts_with("scpp::result_or_bool:") && text.find("requires a present value")!=std::string::npos) return 6;
 return 91;
}
int main() {
 long binary_cases=0;
 const State statesi[]={-2,0,2,3};
 const State statesn[]={Kind::absent,-2,0,2,3}, emptyn[]={Kind::absent};
 const State statesr[]={Kind::error,-2,0,2,3}, emptyr[]={Kind::error};
 const State statesf[]={Kind::false_sentinel,-2,0,2,3}, emptyf[]={Kind::false_sentinel};
 const State statesb[]={Kind::false_sentinel,Kind::true_sentinel,-2,0,2,3}, emptyb[]={Kind::false_sentinel,Kind::true_sentinel};
 // Boundary arrays are used only by spaceship, never unary minus/arithmetic.
 std::vector<State> boundaryi(std::begin(statesi),std::end(statesi)), boundaryn(std::begin(statesn),std::end(statesn)), boundaryr(std::begin(statesr),std::end(statesr)), boundaryf(std::begin(statesf),std::end(statesf)), boundaryb(std::begin(statesb),std::end(statesb));
 for(long v:{std::numeric_limits<long>::min(),std::numeric_limits<long>::max(),9007199254740993L,-9007199254740993L}) { boundaryi.emplace_back(v); boundaryn.emplace_back(v); boundaryr.emplace_back(v); boundaryf.emplace_back(v); boundaryb.emplace_back(v); }
 for(State s:emptyb) { int code=0; try { makeb(s).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=6) return 6; }
 // Direct runtime controls prove the exact existing extraction errors, independently of lowering.
 for(int kind:{1,2,3}) { int code=0; try { if(kind==1) maken(Kind::absent).require_value("control"); if(kind==2) maker(Kind::error).require_value("control"); if(kind==3) makef(Kind::false_sentinel).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=kind) return 1; }
 { I calls{0},trace{0}; if(!function_nested(maken(0),maken(Kind::absent),maken(2),calls,trace).native_value() || calls.native_value()!=2 || trace.native_value()!=13) return 2; }
 if(!function_presence(maken(Kind::absent)).native_value() || function_presence(maken(0)).native_value()) return 3;
 { I calls{0},trace{0}; if(function_unarynnot(maken(7),calls,trace).native_value() || calls.native_value()!=1 || trace.native_value()!=1) return 4; }
 { I calls{0},trace{0}; if(function_unaryrneg(R{I{-7}},calls,trace).native_value()!=7 || calls.native_value()!=1 || trace.native_value()!=1) return 5; }
CPP;
$cpp .= 'long cross_snapshot_cases=0,cross_property_cases=0;' . $observers . 'std::cout << "binary_cases=" << binary_cases << "\n";' . "return 0;\n}\n";
file_put_contents($argv[1] . '/guarded_operators.cpp', $cpp);
echo "Guarded operators: four carriers, scalar decisions, exclusions and recovery passed\n";

if ($cross_mode || $spaceship_mode) { guarded_cross_finish($argv[1]); }
