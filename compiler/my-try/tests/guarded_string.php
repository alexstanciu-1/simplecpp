<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/guarded_cross_support.php';

function guarded_string_check(bool $ok, string $message): void
{
	if (!$ok) {
		throw new \LogicException($message);
	}
}

$types = ['n' => '?string', 'r' => 'result<string>', 'f' => 'result_or_false<string>', 'b' => 'result_or_bool<string>', 'i' => 'string'];
$empty = ['n' => 'null', 'r' => 'error::make("kept", 17, "origin")', 'f' => 'false_sentinel', 'b' => 'false_sentinel'];
$spaceship_mode = ($argv[2] ?? '') === '--spaceship';
$binary = ['lt'=>'<','le'=>'<=','gt'=>'>','ge'=>'>='];
if ($spaceship_mode) { $binary = ['cmp'=>'<=>']; guarded_spaceship_exclusions('string'); }
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
	$program .= 'class Box' . $tag . ' { public ' . $type . ' $value = ""; } '
		. 'function reset' . $tag . '(' . $type . ' &$v, ' . $type . ' $replacement, string $out, int &$calls): string { $calls++; $v = $replacement; return $out; } '
		. 'function resetbox' . $tag . '(Box' . $tag . ' $box, ' . $type . ' $replacement, string $out, int &$calls): string { $calls++; $box->value = $replacement; return $out; } ';
	foreach ($binary as $name => $symbol)
	{
		$ret = $spaceship_mode ? 'int' : 'bool'; $cppret = $spaceship_mode ? 'I' : 'B';
		$equal = $spaceship_mode ? 'equal_order' : 'equal_value';
		$expected = $spaceship_mode ? 'byte_order(a,b)' : '(byte_order(a,b) ' . $symbol . ' 0)';
		$pairs = $cross_mode ? guarded_cross_pairs($tag) : [[$tag,$tag],[$tag,'i'],['i',$tag]];
		if ($spaceship_mode) { $pairs = array_merge($pairs, guarded_cross_pairs($tag)); }
		foreach ($pairs as [$lt,$rt])
		{
			$id = $lt . $rt . $name;
			$syntax = operator_test_source('$a ' . $types[$lt] . ' = "a"; $b ' . $types[$rt] . ' = "b"; $out = ($a ' . $symbol . ' $b);');
			$node = $syntax->root->single_body()->body->statements[2]->expression->value;
			$d = $node->require_binary_preparation()->decision;
			guarded_string_check(count($d->operand_extractions) === 2, 'Binary lost ordered extraction stage');
			foreach ($d->operand_extractions as $extraction) {
				guarded_string_check($extraction->target_type->matches(Language_Types::string_type(Model::$language_scope)), 'Extraction did not select string payload');
			}
			foreach ($d->operands as $conversion) { guarded_string_check($conversion->operation === conversion_operation::identity, 'String ordering must not coerce payloads'); }
			guarded_string_check($d->result_type->matches($spaceship_mode ? Language_Types::integer(Model::$language_scope) : Language_Types::boolean(Model::$language_scope)), 'Lifted result is not plain scalar');
			Preparation_Cleanup::tree($syntax->root);
			guarded_string_check($node->preparation() === null, 'Binary cleanup retained facts');
			$program .= 'function binary' . $id . '(' . $types[$lt] . ' $a, ' . $types[$rt] . ' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): ' . $ret
				. ' { return (mark' . $lt . '($a,$lc,$trace,1,$lf) ' . $symbol . ' mark' . $rt . '($b,$rc,$trace,2,$rf)); } ';
			$call = 'function_binary' . $id . '(av,bv,lc,rc,trace,B{lf},B{rf})';
			$la = 'states' . $lt; $rb='states' . $rt;
			$le = $lt==='i'?'0':strval(['n'=>1,'r'=>2,'f'=>3,'b'=>6][$lt]); $re = $rt==='i'?'0':strval(['n'=>1,'r'=>2,'f'=>3,'b'=>6][$rt]);
			$reach = 'true';
			$observers .= 'for(State sa:' . $la . ') for(State sb:' . $rb . ') for(bool lf:{false,true}) for(bool rf:{false,true}) { const auto& a=sa.value; const auto& b=sb.value; auto av=make' . $lt . '(sa); auto bv=make' . $rt . '(sb); I lc{0},rc{0},trace{0}; int expected_error=lf?4:(!sa.payload()?' . $le . ':0); bool reached=expected_error==0 && (' . $reach . '); if(reached) expected_error=rf?4:(!sb.payload()?' . $re . ':0); int failure=0; try { static_assert(std::is_same_v<decltype(' . $call . '),' . $cppret . '>); auto out=' . $call . '; if(expected_error==0 && !' . $equal . '(out.native_value(),' . $expected . ')) return 20; } catch(const std::exception& e) { failure=classify(e); } if(failure!=expected_error || lc.native_value()!=1 || rc.native_value()!=(reached?1:0) || trace.native_value()!=(reached?12:1)) return 21; if(!same(av,sa)||!same(bv,sb)) return 22; ++binary_cases; }' . "\n";
			if ($cross_mode || $spaceship_mode) { guarded_cross_probe($lt,$rt,$types[$lt],$types[$rt],$name,$symbol,$ret,$expected,$reach,$equal,'"a"',false,$program,$observers); }
		}
		// Both local/reference storage and object properties must retain the old byte value.
		$program .= 'function snap' . $tag . $name . '(' . $type . ' &$a, ' . $type . ' $replacement, string $b, int &$calls): ' . $ret . ' { return ($a ' . $symbol . ' reset' . $tag . '($a,$replacement,$b,$calls)); } '
			. 'function prop' . $tag . $name . '(' . $type . ' $a, ' . $type . ' $replacement, string $b, int &$calls, ' . $type . ' &$final): ' . $ret . ' { $box = new Box' . $tag . '(); $box->value = $a; $out = ($box->value ' . $symbol . ' resetbox' . $tag . '($box,$replacement,$b,$calls)); $final = $box->value; return $out; } ';
		$observers .= 'for(State sa:statesi) for(State sb:statesi) for(State replacement:replacements' . $tag . ') { const auto& a=sa.value; const auto& b=sb.value; auto av=make' . $tag . '(sa); auto rv=make' . $tag . '(replacement); I calls{0}; auto out=function_snap' . $tag . $name . '(av,rv,S{b},calls); if(out.native_value()!=' . $expected . ' || calls.native_value()!=1 || !same(av,replacement)) return 30; calls=I{0}; auto final=make' . $tag . '(sa); out=function_prop' . $tag . $name . '(make' . $tag . '(sa),rv,S{b},calls,final); if(out.native_value()!=' . $expected . ' || calls.native_value()!=1 || !same(final,replacement)) return 31; snapshot_cases+=2; }' . "\n";
	}

	// Direct conditions/selectors retain absence behavior independently of guarded !.
	$program .= 'function condition' . $tag . '(' . $type . ' $v): bool { if ($v) { return true; } return false; } '
		. 'function ternary' . $tag . '(' . $type . ' $v): bool { return $v ? true : false; } '
		. 'function elvis' . $tag . '(' . $type . ' $v): ' . $type . ' { return $v ?: "fallback"; } '
		. ($tag === 'b' ? '' : 'function coalesce' . $tag . '(' . $type . ' $v): string { return $v ?? "fallback"; } ')
		. 'function present' . $tag . '(' . $type . ' $v): bool { return isset($v); } ';
	$observers .= 'for(State sa:states' . $tag . ') { auto av=make' . $tag . '(sa); bool truth=sa.truth(); if(function_condition' . $tag . '(av).native_value()!=truth || function_ternary' . $tag . '(av).native_value()!=truth || !same(function_elvis' . $tag . '(av),truth?sa:State{"fallback"}) || ' . ($tag === 'b' ? '' : '!equal_value(function_coalesce' . $tag . '(av).native_value(),(sa.payload()?sa.value:"fallback")) || ') . 'function_present' . $tag . '(av).native_value()!=sa.present() || !same(av,sa)) return 40; }' . "\n";
	if ($tag === 'b') {
		$program .= 'function takeb(result_or_bool<string> $v, string &$out, bool &$flag): bool { return take($out,$flag) = $v; } ';
		$observers .= 'for(State sa:statesb) for(bool initial:{false,true}) { S out{"kept-output"}; B flag{initial}; auto av=makeb(sa); bool taken=function_takeb(av,out,flag).native_value(); if(taken!=sa.present() || !equal_value(out.native_value(),(sa.payload()?sa.value:"kept-output")) || flag.native_value()!=(sa.payload()?initial:sa.kind==Kind::true_sentinel) || !same(av,sa)) return 43; }' . "\n";
		$negatives[] = 'function bad(result_or_bool<string> $v): string { return $v ?? "fallback"; }';
		$negatives[] = 'function bad(result_or_bool<string> $v): string { return "fallback" ?? $v; }';
	}

	elseif ($tag === 'r') {
		$program .= 'function taker(result<string> $v, string &$out, error &$failure): bool { return take($out,$failure) = $v; } ';
		$observers .= 'for(State sa:statesr) { S out{"kept-output"}; scpp::error_t error; auto av=maker(sa); bool taken=function_taker(av,out,error).native_value(); if(taken!=sa.payload() || !equal_value(out.native_value(),sa.payload()?sa.value:"kept-output") || error.has_error().native_value()==sa.payload() || (!sa.payload() && (error.get_message().native_value()!="kept" || error.get_line().native_value()!=17 || error.get_file().native_value()!="origin")) || !same(av,sa)) return 45; }' . "\n";
	}

	else {
		$program .= 'function take' . $tag . '(' . $type . ' $v, string &$out): bool { return take($out) = $v; } ';
		$observers .= 'for(State sa:states' . $tag . ') { S out{"kept-output"}; auto av=make' . $tag . '(sa); bool taken=function_take' . $tag . '(av,out).native_value(); if(taken!=sa.payload() || !equal_value(out.native_value(),sa.payload()?sa.value:"kept-output") || !same(av,sa)) return 44; }' . "\n";
	}

}

foreach (['?string','result<string>','result_or_false<string>','result_or_bool<string>'] as $type)
{
	foreach (['+','-','*','/','==','!=','===','!==','xor','&&','||','and','or','%','**','&','|','^','<<','>>'] as $symbol) {
		if (in_array($symbol, ['==','!=','===','!=='], true)) { continue; }
		$negatives[] = '$a ' . $type . ' = "a"; $b = $a ' . $symbol . ' $a;';
	}
	foreach (['++$a','$a++','--$a','$a--','~$a','+$a','-$a'] as $expression) {
		$negatives[] = '$a ' . $type . ' = "a"; ' . $expression . ';';
	}
	foreach (['+=','-=','*=','/=','%=','**=','&=','|=','^=','<<=','>>='] as $symbol) {
		$negatives[] = '$a ' . $type . ' = "a"; $a ' . $symbol . ' "b";';
		$negatives[] = '$a ' . $type . ' = "a"; $b = "a"; $b ' . $symbol . ' $a;';
	}
	foreach (['int','float','bool','int16','?int','result<int>','result_or_false<int>','result_or_bool<int>','?float','result<float>','?bool','result<bool>'] as $other) {
		foreach ($binary as $symbol) {
			$negatives[] = 'function bad(' . $type . ' $a, ' . $other . ' $b): void { $out = ($a ' . $symbol . ' $b); }';
			$negatives[] = 'function bad(' . $other . ' $a, ' . $type . ' $b): void { $out = ($a ' . $symbol . ' $b); }';
		}
	}

	$negatives[] = '$a ' . $type . ' = ""; $a && missing();';
	$negatives[] = '$a ' . $type . ' = "a"; $a || "x";';
	foreach (['<','<=','>','>='] as $symbol)
	{
		$directory = sys_get_temp_dir() . '/scpp_guarded_string_' . bin2hex(random_bytes(6));
		mkdir($directory); $path = $directory . '/main.phs';
		$valid = '$a ' . $type . ' = "a"; $b = "b"; $c = ($a ' . $symbol . ' $b);';
		try
		{
			file_put_contents($path, $valid); Compiler_Lifecycle::reset();
			$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([$directory]); $compiler->exec_cpp();
			file_put_contents($path, str_replace('$b = "b"', '$b = 7', $valid));
			foreach ([[$path], []] as $changes)
			{
				$failed = false;
				try { $compiler->update_cpp($changes); }
				catch (\RuntimeException $e) { $failed = true; }
				guarded_string_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Failed guarded edit retained artifacts');
			}
			file_put_contents($path, str_replace('$b = "b"', '$b = "repaired"', $valid)); $compiler->update_cpp([$path]);
			$repaired = CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]);
			guarded_string_check($repaired === CPP_Probe::text(Model::$cpp_files), 'No-op guarded output changed');
			Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->exec_cpp();
			guarded_string_check($repaired === CPP_Probe::text(Model::$cpp_files), 'Guarded repair differs from fresh');
		}
		finally { unlink($path); rmdir($directory); }
	}
}
foreach ($negatives as $source)
{
	$failed = false;
	try { operator_test_source($source); }
	catch (\RuntimeException $error) { $failed = true; }
	guarded_string_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(), 'Excluded guarded expression admitted: ' . $source);
}
// This slice must not redirect the existing nullable-string condition-negation path.
$program .= 'function preservednot(?string $v): bool { return !$v; } ';
$observers .= 'for(State sa:statesn) { if(function_preservednot(maken(sa)).native_value()!=!sa.truth()) return 46; }' . "\n";
if ($cross_mode || $spaceship_mode) { $observers .= 'std::cout << "cross_snapshot_cases=" << cross_snapshot_cases << ";cross_property_cases=" << cross_property_cases << "\n";'; }
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

#include <iostream>
#include <type_traits>
#include <vector>
#include <iostream>
using I=scpp::int_t<>; using B=scpp::bool_t; using S=scpp::string_t;
using N=scpp::nullable<S>; using R=scpp::result<S>; using F=scpp::result_or_false<S>; using RB=scpp::result_or_bool<S>;
enum class Kind { payload, absent, error, false_sentinel, true_sentinel };
struct State {
 Kind kind; std::string value;
 State(std::string v):kind(Kind::payload),value(v) {}
 State(const char* v):State(std::string(v)) {}
 State(Kind k):kind(k),value() {}
 bool payload() const { return kind==Kind::payload; }
 bool truth() const { return kind==Kind::true_sentinel || (payload() && !value.empty() && value!="0"); }
 bool present() const { return payload() || kind==Kind::true_sentinel; }
};
// Independent unsigned-byte oracle, not runtime comparison operators.
int byte_order(const std::string& a,const std::string& b) {
 for(size_t i=0;i<a.size() && i<b.size();++i) {
  auto x=static_cast<unsigned char>(a[i]), y=static_cast<unsigned char>(b[i]);
  if(x<y) return -1; if(x>y) return 1;
 }
 return a.size()<b.size()?-1:a.size()>b.size()?1:0;
}
bool equal_value(const std::string& a,const std::string& b) { return byte_order(a,b)==0; }
bool equal_order(long a,long b) { return a==b; }
bool equal_value(bool a,bool b) { return a==b; }
N maken(State s) { return s.payload()?N{S{s.value}}:N{scpp::null}; }
R maker(State s) { return s.payload()?R{S{s.value}}:R{scpp::error_t{scpp::string_t{"kept"},I{17},scpp::string_t{"origin"}}}; }
F makef(State s) { return s.payload()?F{S{s.value}}:F{scpp::false_sentinel}; }
RB makeb(State s) { return s.payload()?RB{S{s.value}}:s.kind==Kind::true_sentinel?RB{scpp::true_sentinel}:RB{scpp::false_sentinel}; }
S makei(State s) { return S{s.value}; }
bool same(const S& v,State s) { return s.payload() && equal_value(v.native_value(),s.value); }
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
 long binary_cases=0,snapshot_cases=0;
 const std::vector<std::string> values={"", "a", "ab", "b", "A", "0", "01", "1", "10", "2",
  std::string("a\0b",3), std::string("a\0c",3), std::string("\0",1),
  std::string("\x7f",1), std::string("\x80",1), std::string("\xff",1), "é", "é"};
 std::vector<State> statesi,statesn={Kind::absent},statesr={Kind::error},statesf={Kind::false_sentinel},statesb={Kind::false_sentinel,Kind::true_sentinel};
 for(const auto& v:values) { statesi.emplace_back(v); statesn.emplace_back(v); statesr.emplace_back(v); statesf.emplace_back(v); statesb.emplace_back(v); }
 const State emptyb[]={Kind::false_sentinel,Kind::true_sentinel};
 const State replacementsn[]={"changed",Kind::absent}, replacementsr[]={"changed",Kind::error}, replacementsf[]={"changed",Kind::false_sentinel}, replacementsb[]={"changed",Kind::false_sentinel,Kind::true_sentinel};
 for(State s:emptyb) { int code=0; try { makeb(s).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=6) return 6; }
 // Direct runtime controls prove the exact existing extraction errors, independently of lowering.
 for(int kind:{1,2,3}) { int code=0; try { if(kind==1) maken(Kind::absent).require_value("control"); if(kind==2) maker(Kind::error).require_value("control"); if(kind==3) makef(Kind::false_sentinel).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=kind) return 1; }

CPP;
$cpp .= 'long cross_snapshot_cases=0,cross_property_cases=0;' . $observers . 'std::cout << "binary_cases=" << binary_cases << ";snapshot_cases=" << snapshot_cases << "\n"; return 0;' . "\n}\n";
file_put_contents($argv[1] . '/guarded_string.cpp', $cpp);
echo 'Guarded string: 48 signatures, ' . count($negatives) . " exclusions and 16 recovery sequences passed\n";

if ($cross_mode || $spaceship_mode) { guarded_cross_finish($argv[1]); }
