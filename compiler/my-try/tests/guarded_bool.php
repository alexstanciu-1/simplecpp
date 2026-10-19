<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/guarded_cross_support.php';

function guarded_bool_check(bool $ok, string $message): void
{
	if (!$ok) {
		throw new \LogicException($message);
	}
}

$types = ['n' => '?bool', 'r' => 'result<bool>', 'f' => 'result_or_false<bool>', 'b' => 'result_or_bool<bool>', 'i' => 'bool'];
$empty = ['n' => 'null', 'r' => 'error::make("kept", 17, "origin")', 'f' => 'false_sentinel', 'b' => 'false_sentinel'];
$xor_mode = ($argv[2] ?? '') === '--xor';
$binary = $xor_mode ? ['lxor'=>'xor'] : ['land'=>'&&','lor'=>'||','wand'=>'and','wor'=>'or'];
$unary = ['not'=>'!'];
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
	$program .= 'function reset' . $tag . '(' . $type . ' &$v, bool $out, int &$calls, bool $sentinel): bool { $calls++; ' . ($tag === 'b' ? 'if ($sentinel) { $v = true_sentinel; } else { $v = false_sentinel; }' : '$v = ' . $empty[$tag] . ';') . ' return $out; } ';
	foreach ($unary as $name => $symbol)
	{
		$ret = 'bool';
		$cppret = 'B';
		$syntax = operator_test_source('$v ' . $type . ' = false; $out = ' . $symbol . '$v;');
		$node = $syntax->root->single_body()->body->statements[1]->expression->value;
		$d = $node->require_unary_preparation()->decision;
		guarded_bool_check(count($d->operand_extractions) === 1 && $d->operand_extractions[0]->target_type->matches(Language_Types::boolean(Model::$language_scope)), 'Unary lost extraction stage');
		Preparation_Cleanup::tree($syntax->root);
		guarded_bool_check($node->preparation() === null, 'Unary cleanup retained facts');
		$program .= 'function unary' . $tag . $name . '(' . $type . ' $v, int &$calls, int &$trace): ' . $ret
			. ' { return ' . $symbol . 'mark' . $tag . '($v, $calls, $trace, 1, false); } ';
		$call = 'function_unary' . $tag . $name . '(make' . $tag . '(sa),calls,trace)';
		$expect = $symbol . 'a';
		$observers .= 'for(State sa:states' . $tag . ') { bool a=sa.value; I calls{0},trace{0}; int failure=0; try { static_assert(std::is_same_v<decltype(' . $call . '),' . $cppret . '>); auto out=' . $call . '; if(!equal_value(out.native_value(),(' . $expect . '))) return 10; } catch(const std::exception& e) { failure=classify(e); } if(failure!=(!sa.payload()?' . ['n'=>1,'r'=>2,'f'=>3,'b'=>6][$tag] . ':0) || calls.native_value()!=1 || trace.native_value()!=1) return 11; ++unary_cases; }' . "\n";
	}
	foreach ($binary as $name => $symbol)
	{
		$isand = in_array($name, ['land','wand'], true);
		$ret = 'bool'; $cppret = 'B';
		$expected = '(a ' . ($name === 'lxor' ? '!=' : ($isand?'&&':'||')) . ' b)';
		$pairs = $cross_mode ? guarded_cross_pairs($tag) : [[$tag,$tag],[$tag,'i'],['i',$tag]];
		if ($xor_mode) { $pairs = array_merge($pairs, guarded_cross_pairs($tag)); }
		foreach ($pairs as [$lt,$rt])
		{
			$id = $lt . $rt . $name;
			$syntax = operator_test_source('$a ' . $types[$lt] . ' = false; $b ' . $types[$rt] . ' = true; $out = ($a ' . $symbol . ' $b);');
			$node = $syntax->root->single_body()->body->statements[2]->expression->value;
			$d = $node->require_binary_preparation()->decision;
			guarded_bool_check(count($d->operand_extractions) === 2, 'Binary lost ordered extraction stage');
			guarded_bool_check($d->operation === ($name === 'lxor' ? operator_operation::boolean_xor : ($isand ? operator_operation::boolean_and : operator_operation::boolean_or)), 'Bool payload did not select ordinary bool logical operation');
			foreach ($d->operand_extractions as $extraction) {
				guarded_bool_check($extraction->target_type->matches(Language_Types::boolean(Model::$language_scope)), 'Extraction did not select bool payload');
			}
			guarded_bool_check($d->result_type->matches(Language_Types::boolean(Model::$language_scope)), 'Lifted result is not plain scalar');
			Preparation_Cleanup::tree($syntax->root);
			guarded_bool_check($node->preparation() === null, 'Binary cleanup retained facts');
			$program .= 'function binary' . $id . '(' . $types[$lt] . ' $a, ' . $types[$rt] . ' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): ' . $ret
				. ' { return (mark' . $lt . '($a,$lc,$trace,1,$lf) ' . $symbol . ' mark' . $rt . '($b,$rc,$trace,2,$rf)); } ';
			$call = 'function_binary' . $id . '(av,bv,lc,rc,trace,B{lf},B{rf})';
			$la = 'states' . $lt; $rb='states' . $rt;
			$le = $lt==='i'?'0':strval(['n'=>1,'r'=>2,'f'=>3,'b'=>6][$lt]); $re = $rt==='i'?'0':strval(['n'=>1,'r'=>2,'f'=>3,'b'=>6][$rt]);
			$reach = $name === 'lxor' ? 'true' : ($isand ? 'a' : '!a');
			$observers .= 'for(State sa:' . $la . ') for(State sb:' . $rb . ') for(bool lf:{false,true}) for(bool rf:{false,true}) { bool a=sa.value,b=sb.value; auto av=make' . $lt . '(sa); auto bv=make' . $rt . '(sb); I lc{0},rc{0},trace{0}; int expected_error=lf?4:(!sa.payload()?' . $le . ':0); bool reached=expected_error==0 && (' . $reach . '); if(reached) expected_error=rf?4:(!sb.payload()?' . $re . ':0); int failure=0; try { static_assert(std::is_same_v<decltype(' . $call . '),' . $cppret . '>); auto out=' . $call . '; if(expected_error==0 && !equal_value(out.native_value(),' . $expected . ')) return 20; } catch(const std::exception& e) { failure=classify(e); } if(failure!=expected_error || lc.native_value()!=1 || rc.native_value()!=(reached?1:0) || trace.native_value()!=(reached?12:1)) return 21; if(!same(av,sa)||!same(bv,sb)) return 22; ++binary_cases; }' . "\n";
			if ($cross_mode || $xor_mode) { guarded_cross_probe($lt,$rt,$types[$lt],$types[$rt],$name,$symbol,$ret,$expected,$reach,'equal_value','false',false,$program,$observers); }
		}
		// RHS resets the actual LHS storage. Payload extraction must have finished first.
		$program .= 'function snap' . $tag . $name . '(' . $type . ' &$a, bool $b, int &$calls, bool $sentinel): ' . $ret
			. ' { return ($a ' . $symbol . ' reset' . $tag . '($a,$b,$calls,$sentinel)); } ';
		$reach = $name === 'lxor' ? 'true' : ($isand ? 'a' : '!a');
		$observers .= 'for(State sa:statesi) for(State sb:statesi) for(State empty:empty' . $tag . ') { bool a=sa.value,b=sb.value; auto av=make' . $tag . '(sa); I calls{0}; auto out=function_snap' . $tag . $name . '(av,B{b},calls,B{empty.kind==Kind::true_sentinel}); bool reached=' . $reach . '; if(!equal_value(out.native_value(),' . $expected . ') || calls.native_value()!=(reached?1:0) || !same(av,reached?empty:sa)) return 30; ++snapshot_cases; }' . "\n";
	}
	// Direct conditions/selectors retain absence behavior independently of guarded !.
	$program .= 'function condition' . $tag . '(' . $type . ' $v): bool { if ($v) { return true; } return false; } '
		. 'function ternary' . $tag . '(' . $type . ' $v): bool { return $v ? true : false; } '
		. 'function elvis' . $tag . '(' . $type . ' $v): ' . $type . ' { return $v ?: true; } '
		. ($tag === 'b' ? '' : 'function coalesce' . $tag . '(' . $type . ' $v): bool { return $v ?? true; } ')
		. 'function present' . $tag . '(' . $type . ' $v): bool { return isset($v); } ';
	$observers .= 'for(State sa:states' . $tag . ') { auto av=make' . $tag . '(sa); bool truth=sa.truth(); if(function_condition' . $tag . '(av).native_value()!=truth || function_ternary' . $tag . '(av).native_value()!=truth || !same(function_elvis' . $tag . '(av),truth?sa:State{true}) || ' . ($tag === 'b' ? '' : '!equal_value(function_coalesce' . $tag . '(av).native_value(),(sa.payload()?sa.value:true)) || ') . 'function_present' . $tag . '(av).native_value()!=sa.present() || !same(av,sa)) return 40; }' . "\n";
	if ($tag === 'b') {
		$program .= 'function takeb(result_or_bool<bool> $v, bool &$out, bool &$flag): bool { return take($out,$flag) = $v; } ';
		$observers .= 'for(State sa:statesb) for(bool initial_out:{false,true}) for(bool initial:{false,true}) { B out{initial_out}; B flag{initial}; auto av=makeb(sa); bool taken=function_takeb(av,out,flag).native_value(); if(taken!=sa.present() || !equal_value(out.native_value(),(sa.payload()?sa.value:initial_out)) || flag.native_value()!=(sa.payload()?initial:sa.kind==Kind::true_sentinel) || !same(av,sa)) return 43; }' . "\n";
		$negatives[] = 'function bad(result_or_bool<bool> $v): bool { return $v ?? true; }';
		$negatives[] = 'function bad(result_or_bool<bool> $v): bool { return true ?? $v; }';
	}

	elseif ($tag === 'r') {
		$program .= 'function taker(result<bool> $v, bool &$out, error &$failure): bool { return take($out,$failure) = $v; } ';
		$observers .= 'for(State sa:statesr) for(bool initial_out:{false,true}) { B out{initial_out}; scpp::error_t error; auto av=maker(sa); bool taken=function_taker(av,out,error).native_value(); if(taken!=sa.payload() || !equal_value(out.native_value(),sa.payload()?sa.value:initial_out) || error.has_error().native_value()==sa.payload() || (!sa.payload() && (error.get_message().native_value()!="kept" || error.get_line().native_value()!=17 || error.get_file().native_value()!="origin")) || !same(av,sa)) return 45; }' . "\n";
	}

	else {
		$program .= 'function take' . $tag . '(' . $type . ' $v, bool &$out): bool { return take($out) = $v; } ';
		$observers .= 'for(State sa:states' . $tag . ') for(bool initial_out:{false,true}) { B out{initial_out}; auto av=make' . $tag . '(sa); bool taken=function_take' . $tag . '(av,out).native_value(); if(taken!=sa.payload() || !equal_value(out.native_value(),sa.payload()?sa.value:initial_out) || !same(av,sa)) return 44; }' . "\n";
	}

	$program .= 'function construct' . $tag . '(bool $input): ' . $type . ' { $v ' . $type . ' = $input; return $v; } '
		. 'function assign' . $tag . '(' . $type . ' &$v, bool $input): void { $v = $input; } '
		. 'function literalfalse' . $tag . '(): ' . $type . ' { $v ' . $type . ' = false; return $v; } '
		. 'function literaltrue' . $tag . '(): ' . $type . ' { $v ' . $type . ' = true; return $v; } ';
	$observers .= 'for(bool input:{false,true}) { if(!same(function_construct' . $tag . '(B{input}),State{input})) return 46; for(State old:states' . $tag . ') { auto v=make' . $tag . '(old); function_assign' . $tag . '(v,B{input}); if(!same(v,State{input})) return 47; } } if(!same(function_literalfalse' . $tag . '(),State{false}) || !same(function_literaltrue' . $tag . '(),State{true})) return 48;' . "\n";

	if ($tag === 'f' || $tag === 'b') {
		$program .= 'function sentinel' . $tag . '(bool $on): ' . $type . ' { ' . ($tag === 'b' ? 'if ($on) { return true_sentinel; } ' : '') . 'return false_sentinel; } ';
		$observers .= 'if(!same(function_sentinel' . $tag . '(B{false}),State{Kind::false_sentinel})) return 49;' . "\n";
		if ($tag === 'b') { $observers .= 'if(!same(function_sentinelb(B{true}),State{Kind::true_sentinel})) return 50;' . "\n"; }
	}

	if ($xor_mode) {
		$program .= 'function xorword'.$tag.'('.$type.' $a, '.$type.' $b, '.$type.' &$saved): bool { return ($saved = $a xor $b); } '
			. 'function xorgroup'.$tag.'('.$type.' $a, '.$type.' $b, bool &$saved): void { $saved = ($a xor $b); } ';
		$observers .= 'for(State sa:statesi) for(State sb:statesi) { auto saved=make'.$tag.'(State{!sa.value}); auto out=function_xorword'.$tag.'(make'.$tag.'(sa),make'.$tag.'(sb),saved); if(out.native_value()!=(sa.value!=sb.value) || !same(saved,sa)) return 70; B grouped{true}; function_xorgroup'.$tag.'(make'.$tag.'(sa),make'.$tag.'(sb),grouped); if(grouped.native_value()!=(sa.value!=sb.value)) return 71; }' . "\n";
	}

	$program .= 'function word' . $tag . '(' . $type . ' &$v): bool { return ($v = false or true); } '
		. 'function grouped' . $tag . '(' . $type . ' $v, bool &$out): void { $out = ($v and true); } ';
	$observers .= '{ auto av=make' . $tag . '(true); B out{true}; if(!function_word' . $tag . '(av).native_value() || !same(av,State{false})) return 41; function_grouped' . $tag . '(av,out); if(out.native_value()) return 42; }' . "\n";
}

foreach (['?bool','result<bool>','result_or_false<bool>','result_or_bool<bool>'] as $type)
{
	foreach (['+','-','*','/','<','<=','>','>=','==','!=','===','!==','<=>','%','**','&','|','^','<<','>>'] as $symbol) {
		if (in_array($type, ['?bool','result<bool>','result_or_false<bool>','result_or_bool<bool>'], true) && in_array($symbol, ['==','!=','===','!=='], true)) { continue; }
		$negatives[] = '$a ' . $type . ' = true; $b = $a ' . $symbol . ' $a;';
	}
	foreach (['++$a','$a++','--$a','$a--','~$a','+$a','-$a'] as $expression) {
		$negatives[] = '$a ' . $type . ' = true; ' . $expression . ';';
	}
	foreach (['+=','-=','*=','/=','%=','**=','&=','|=','^=','<<=','>>='] as $symbol) {
		$negatives[] = '$a ' . $type . ' = true; $a ' . $symbol . ' true;';
		$negatives[] = '$a ' . $type . ' = true; $b = false; $b ' . $symbol . ' $a;';
	}
	foreach (['int','float','int16','string','?int','result<int>','result_or_false<int>','result_or_bool<int>','?float','result<float>','result_or_false<float>','result_or_bool<float>'] as $other) {
		foreach ($binary as $symbol) {
			$negatives[] = 'function bad(' . $type . ' $a, ' . $other . ' $b): void { $out = ($a ' . $symbol . ' $b); }';
			$negatives[] = 'function bad(' . $other . ' $a, ' . $type . ' $b): void { $out = ($a ' . $symbol . ' $b); }';
		}
	}

	$negatives[] = '$a ' . $type . ' = false; $a && missing();';
	$negatives[] = '$a ' . $type . ' = true; $a || "x";';
	foreach (['&&','||','and','or'] as $symbol)
	{
		$directory = sys_get_temp_dir() . '/scpp_guarded_bool_' . bin2hex(random_bytes(6));
		mkdir($directory); $path = $directory . '/main.phs';
		$valid = '$a ' . $type . ' = false; $b = true; $c = ($a ' . $symbol . ' $b);';
		try
		{
			file_put_contents($path, $valid); Compiler_Lifecycle::reset();
			$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
			file_put_contents($path, str_replace('$b = true', '$b = "bad"', $valid));
			foreach ([[$path], []] as $changes)
			{
				$failed = false;
				try { $compiler->update_cpp($changes); }
				catch (\RuntimeException $e) { $failed = true; }
				guarded_bool_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Failed guarded edit retained artifacts');
			}
			file_put_contents($path, str_replace('$b = true', '$b = false', $valid)); $compiler->update_cpp([$path]);
			$repaired = CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]);
			guarded_bool_check($repaired === CPP_Probe::text(Model::$cpp_files), 'No-op guarded output changed');
			Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->exec_cpp();
			guarded_bool_check($repaired === CPP_Probe::text(Model::$cpp_files), 'Guarded repair differs from fresh');
		}
		finally { unlink($path); rmdir($directory); }
	}
}
foreach ($negatives as $source)
{
	$failed = false;
	try { operator_test_source($source); }
	catch (\RuntimeException $error) { $failed = true; }
	guarded_bool_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Excluded guarded expression admitted: ' . $source);
}
$program .= <<<'PHS'
function nested(?bool $a, ?bool $b, ?bool $c, int &$calls, int &$trace): bool {
 return (markn($a,$calls,$trace,1,false) && markn($b,$calls,$trace,2,false)) || (markn($c,$calls,$trace,3,false) || markn($b,$calls,$trace,4,false));
}
function presence(?bool $v): bool { return $v === null; }
PHS;
if ($cross_mode || $xor_mode) { $observers .= 'std::cout << "cross_snapshot_cases=" << cross_snapshot_cases << ";cross_property_cases=" << cross_property_cases << "\n";'; }
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <iostream>
#include <type_traits>
using I=scpp::int_t<>; using B=scpp::bool_t;
using N=scpp::nullable<B>; using R=scpp::result<B>; using F=scpp::result_or_false<B>; using RB=scpp::result_or_bool<B>;
enum class Kind { payload, absent, error, false_sentinel, true_sentinel };
struct State {
 Kind kind; bool value;
 State(bool v):kind(Kind::payload),value(v) {}
 State(Kind k):kind(k),value(false) {}
 bool payload() const { return kind==Kind::payload; }
 bool truth() const { return kind==Kind::true_sentinel || (payload() && value); }
 bool present() const { return payload() || kind==Kind::true_sentinel; }
};
bool equal_value(bool a,bool b) { return a==b; }
N maken(State s) { return s.payload()?N{B{s.value}}:N{scpp::null}; }
R maker(State s) { return s.payload()?R{B{s.value}}:R{scpp::error_t{scpp::string_t{"kept"},I{17},scpp::string_t{"origin"}}}; }
F makef(State s) { return s.payload()?F{B{s.value}}:F{scpp::false_sentinel}; }
RB makeb(State s) { return s.payload()?RB{B{s.value}}:s.kind==Kind::true_sentinel?RB{scpp::true_sentinel}:RB{scpp::false_sentinel}; }
B makei(State s) { return B{s.value}; }
bool same(const B& v,State s) { return s.payload() && equal_value(v.native_value(),s.value); }
bool same(const N& v,State s) { return !s.payload()?!v.has_value().native_value():v.has_value().native_value()&&equal_value(v.require_value("observe").native_value(),s.value); }
bool same(const R& v,State s) { return !s.payload()?v.has_error().native_value()&&v.error()->get_message().native_value()=="kept"&&v.error()->get_line().native_value()==17&&v.error()->get_file().native_value()=="origin":v.has_value().native_value()&&equal_value(v.require_value("observe").native_value(),s.value); }
bool same(const F& v,State s) { return !s.payload()?v.is_false().native_value():v.has_value().native_value()&&equal_value(v.require_value("observe").native_value(),s.value); }
bool same(const RB& v,State s) { return s.payload()?v.has_value().native_value()&&equal_value(v.require_value("observe").native_value(),s.value):s.kind==Kind::true_sentinel?v.is_true().native_value():v.is_false().native_value(); }
int classify(const std::exception& e) {
 if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)) {
  if(p->code()=="invalid_nullable_unwrap_empty") return 1;
  if(p->code()=="division_by_zero") return 4;
  return 90;
 }
 std::string text=e.what();
 if(text.starts_with("scpp::result:") && text.find("requires a present value")!=std::string::npos) return 2;
 if(text.starts_with("scpp::result_or_false:") && text.find("requires a present value")!=std::string::npos) return 3;
 if(text.starts_with("scpp::result_or_bool:") && text.find("requires a present value")!=std::string::npos) return 6;
 return 91;
}
int main() {
 long unary_cases=0,binary_cases=0,snapshot_cases=0;
 const State statesi[]={false,true};
 const State statesn[]={Kind::absent,false,true}, emptyn[]={Kind::absent};
 const State statesr[]={Kind::error,false,true}, emptyr[]={Kind::error};
 const State statesf[]={Kind::false_sentinel,false,true}, emptyf[]={Kind::false_sentinel};
 const State statesb[]={Kind::false_sentinel,Kind::true_sentinel,false,true}, emptyb[]={Kind::false_sentinel,Kind::true_sentinel};
 for(State s:emptyb) { int code=0; try { makeb(s).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=6) return 6; }
 // Direct runtime controls prove the exact existing extraction errors, independently of lowering.
 for(int kind:{1,2,3}) { int code=0; try { if(kind==1) maken(Kind::absent).require_value("control"); if(kind==2) maker(Kind::error).require_value("control"); if(kind==3) makef(Kind::false_sentinel).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=kind) return 1; }
 { I calls{0},trace{0}; if(!function_nested(maken(false),maken(Kind::absent),maken(true),calls,trace).native_value() || calls.native_value()!=2 || trace.native_value()!=13) return 2; }
 if(!function_presence(maken(Kind::absent)).native_value() || function_presence(maken(false)).native_value()) return 3;
 { I calls{0},trace{0}; if(function_unarynnot(maken(true),calls,trace).native_value() || calls.native_value()!=1 || trace.native_value()!=1) return 4; }

CPP;
$cpp .= 'long cross_snapshot_cases=0,cross_property_cases=0;' . $observers . 'std::cout << "unary_cases=" << unary_cases << ";binary_cases=" << binary_cases << ";snapshot_cases=" << snapshot_cases << "\n"; return 0;' . "\n}\n";
file_put_contents($argv[1] . '/guarded_bool.cpp', $cpp);
echo 'Guarded bool operators: ' . ($xor_mode ? '24 XOR' : '48 AND/OR') . ' binary, four unary, ' . count($negatives) . " exclusions and 16 recovery sequences passed\n";

if ($cross_mode || $xor_mode) { guarded_cross_finish($argv[1]); }
