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
$numeric_xor_mode = ($argv[2] ?? '') === '--numeric-xor';
$binary = ['add'=>'+','sub'=>'-','mul'=>'*','div'=>'/','land'=>'&&','lor'=>'||','wand'=>'and','wor'=>'or'];
if ($numeric_xor_mode) { $binary = ['lxor'=>'xor']; }
$program = ''; $observers = ''; $helpers = ''; $states = ''; $negatives = [];
foreach ($types as $tag => $type)
{
	$program .= 'function mark' . $tag . '(' . $type . ' $v, int &$calls, int &$trace, int $digit, bool $fail): ' . $type
		. ' { $calls++; $trace = $trace*10+$digit; if ($fail) { $bad = 1/0; } return $v; } ';
	$floating = str_ends_with($tag, 'd');
	$native = $floating ? 'double' : 'std::int64_t'; $scalar = $floating ? 'D' : 'I';
	$values = $floating ? '-2.5,0.0,-0.0,1.5,inf,-inf,nan' : '-3,0,2,9007199254740993LL,-9007199254740993LL';
	if (strlen($tag) === 1) {
		$helpers .= $scalar . ' make' . $tag . '(State<' . $native . '> s) { return ' . $scalar . '{s.value}; }' . "\n";
		$states .= 'const State<' . $native . '> states' . $tag . '[]={' . $values . '};' . "\n";
		continue;
	}
	$family = $tag[0];
	$wrapper = ['n'=>'nullable','r'=>'result','f'=>'result_or_false','b'=>'result_or_bool'][$family];
	$cpp = 'scpp::' . $wrapper . '<' . $scalar . '>';
	$kind = ['n'=>'absent','r'=>'error','f'=>'false_sentinel','b'=>'false_sentinel'][$family];
	$missing = 'Kind::' . $kind . ($family === 'b' ? ',Kind::true_sentinel' : '');
	$states .= 'const State<' . $native . '> states' . $tag . '[]={' . $missing . ',' . $values . '}; const State<' . $native . '> empty' . $tag . '[]={' . $missing . '};' . "\n";
	$sentinel = ['n'=>'scpp::null','r'=>'scpp::error_t{scpp::string_t{"kept"},I{17},scpp::string_t{"origin"}}','f'=>'scpp::false_sentinel','b'=>'scpp::false_sentinel'][$family];
	$missing_ctor = $cpp . '{' . $sentinel . '}';
	if ($family === 'b') { $missing_ctor = '(s.kind==Kind::true_sentinel?' . $cpp . '{scpp::true_sentinel}:' . $missing_ctor . ')'; }
	$helpers .= $cpp . ' make' . $tag . '(State<' . $native . '> s) { return s.payload()?' . $cpp . '{' . $scalar . '{s.value}}:' . $missing_ctor . '; }' . "\n";
	$missing_check = ['n'=>'!v.has_value().native_value()', 'r'=>'v.has_error().native_value() && v.error()->get_message().native_value()=="kept" && v.error()->get_line().native_value()==17 && v.error()->get_file().native_value()=="origin"', 'f'=>'v.is_false().native_value()', 'b'=>'(s.kind==Kind::true_sentinel?v.is_true().native_value():v.is_false().native_value())'][$family];
	$helpers .= 'bool same(const ' . $cpp . '& v,State<' . $native . '> s) { return s.payload()?v.has_value().native_value() && number_equal(v.require_value("observe").native_value(),s.value):(' . $missing_check . '); }' . "\n";
	// RHS may reset LHS to either boolean sentinel, after its old numeric value is captured.
	$reset = $family === 'b' ? 'if ($sentinel) { $a = true_sentinel; } else { $a = false_sentinel; }' : '$a = ' . $empty[$family] . ';';
	$other = $floating ? 'int' : 'float';
	$program .= 'function reset' . $tag . '(' . $type . ' &$a, ' . $other . ' $b, int &$calls, bool $sentinel): ' . $other . ' { $calls++; ' . $reset . ' return $b; } ';
	$code = ['n'=>1,'r'=>2,'f'=>3,'b'=>6][$family];
	$observers .= 'for(auto s:empty' . $tag . ') { int code=0; try { make' . $tag . '(s).require_value("control"); } catch(const std::exception& e) { code=classify(e); } if(code!=' . $code . ') return 1; }' . "\n";
}
$iterations = $families;
if ($numeric_xor_mode) { $iterations[''] = '%s'; }
foreach ($iterations as $family => $format)
{
	$pairs = [[$family.'i',$family.'d'],[$family.'d',$family.'i'],[$family.'i','d'],['d',$family.'i'],[$family.'d','i'],['i',$family.'d']];
	if ($cross_mode) {
		$pairs=[];
		foreach (guarded_cross_pairs($family) as [$left_family,$right_family]) {
			$pairs[]=[$left_family.'i',$right_family.'d'];
			$pairs[]=[$left_family.'d',$right_family.'i'];
		}
	}
	if ($numeric_xor_mode) {
		$pairs=[];
		foreach (['i','d'] as $lp) foreach ($types as $rt => $right_type) { $pairs[]=[$family.$lp,$rt]; }
	}
	foreach ($binary as $name => $symbol)
	{
		$logical = in_array($name, ['land','lor','wand','wor','lxor'], true);
		$isand = in_array($name, ['land','wand'], true);
		$ret = $logical ? 'bool' : 'float'; $cppret = $logical ? 'B' : 'D';
		$expected = $logical ? '(a!=0 ' . ($isand?'&&':'||') . ' b!=0)' : '(static_cast<double>(a) ' . $symbol . ' static_cast<double>(b))';
		$reach = $logical ? ($isand?'a!=0':'a==0') : 'true';
		if ($numeric_xor_mode) { $expected='((a!=0)!=(b!=0))'; $reach='true'; }
		foreach ($pairs as [$lt,$rt])
		{
			$id = $lt . $rt . $name;
			$left_float = str_ends_with($lt, 'd'); $right_float = str_ends_with($rt, 'd');
			$source = 'function probe(' . $types[$lt] . ' $a, ' . $types[$rt] . ' $b): ' . $ret . ' { return ($a ' . $symbol . ' $b); }';
			$syntax = operator_test_source($source);
			// Prepare a local expression as well, for direct retained-fact inspection.
			$syntax = operator_test_source('$a ' . $types[$lt] . ' = ' . ($left_float?'1.5':'2') . '; $b ' . $types[$rt] . ' = ' . ($right_float?'1.5':'2') . '; $out = ($a ' . $symbol . ' $b);');
			$node = $syntax->root->single_body()->body->statements[2]->expression->value;
			$d = $node->require_binary_preparation()->decision;
			$guarded = strlen($lt) > 1 || strlen($rt) > 1;
			guarded_mixed_check(count($d->operand_extractions) === ($guarded?2:0), 'Incorrect extraction facts');
			if ($numeric_xor_mode) { guarded_mixed_check($d->operation === operator_operation::condition_xor, 'Missing eager numeric XOR decision'); }
			foreach ([$left_float,$right_float] as $index => $floating) {
				$payload = $floating ? Language_Types::floating(Model::$language_scope) : Language_Types::integer(Model::$language_scope);
				if ($guarded) { guarded_mixed_check($d->operand_extractions[$index]->target_type->matches($payload), 'Extraction lost operand payload identity'); }
				if ($logical) { guarded_mixed_check($d->operand_truthiness[$index]->source_type->matches($payload), 'Truth conversion lost actual payload identity'); }
				if (!$logical) {
					guarded_mixed_check($d->operands[$index]->target_type->matches(Language_Types::floating(Model::$language_scope))
						&& $d->operands[$index]->operation === ($floating ? conversion_operation::identity : conversion_operation::integer_to_floating), 'Arithmetic did not select the exact payload conversion');
				}
			}
			guarded_mixed_check($d->result_type->matches($logical?Language_Types::boolean(Model::$language_scope):Language_Types::floating(Model::$language_scope)), 'Incorrect scalar result');
			if ($logical) { guarded_mixed_check(count($d->operand_truthiness) === 2 && count($d->operands) === 0, 'Logical operands must have independent truth conversion'); }
			Preparation_Cleanup::tree($syntax->root);
			guarded_mixed_check($node->preparation() === null, 'Cleanup retained mixed facts');
			$program .= 'function binary' . $id . '(' . $types[$lt] . ' $a, ' . $types[$rt] . ' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): ' . $ret
				. ' { return (mark' . $lt . '($a,$lc,$trace,1,$lf) ' . $symbol . ' mark' . $rt . '($b,$rc,$trace,2,$rf)); } ';
			$call = 'function_binary' . $id . '(av,bv,lc,rc,trace,B{lf},B{rf})';
			$le = strlen($lt)===1?0:['n'=>1,'r'=>2,'f'=>3,'b'=>6][$lt[0]]; $re = strlen($rt)===1?0:['n'=>1,'r'=>2,'f'=>3,'b'=>6][$rt[0]];
			$operror = $name==='div'?'(b==0?4:0)':'0';
			$observers .= 'for(auto sa:states' . $lt . ') for(auto sb:states' . $rt . ') for(bool lf:{false,true}) for(bool rf:{false,true}) { auto a=sa.value; auto b=sb.value; auto av=make' . $lt . '(sa); auto bv=make' . $rt . '(sb); I lc{0},rc{0},trace{0}; int expected_error=lf?4:(!sa.payload()?' . $le . ':0); bool reached=expected_error==0 && (' . $reach . '); if(reached) expected_error=rf?4:(!sb.payload()?' . $re . ':' . $operror . '); int failure=0; try { static_assert(std::is_same_v<decltype(' . $call . '),' . $cppret . '>); auto out=' . $call . '; if(expected_error==0 && !number_equal(out.native_value(),' . $expected . ')) return 10; } catch(const std::exception& e) { failure=classify(e); } if(failure!=expected_error || lc.native_value()!=1 || rc.native_value()!=(reached?1:0) || trace.native_value()!=(reached?12:1)) return 11; if(!same(av,sa) || !same(bv,sb)) return 12; ++binary_cases; }' . "\n";
			if ($cross_mode || $numeric_xor_mode) { guarded_cross_probe($lt,$rt,$types[$lt],$types[$rt],$name,$symbol,$ret,$expected,$reach,'number_equal',$left_float?'2.5':'2',$left_float!==$right_float,$program,$observers); }
			if ($numeric_xor_mode) {
				$program .= 'function word'.$id.'('.$types[$lt].' $a, '.$types[$rt].' $b, '.$types[$lt].' &$saved): bool { return ($saved = $a xor $b); } '
					. 'function grouped'.$id.'('.$types[$lt].' $a, '.$types[$rt].' $b, bool &$saved): void { $saved = ($a xor $b); } ';
				$observers .= 'for(auto sa:states'.$lt.') for(auto sb:states'.$rt.') { if(!sa.payload() || !sb.payload()) continue; auto saved=make'.$lt.'(sa); auto out=function_word'.$id.'(make'.$lt.'(sa),make'.$rt.'(sb),saved); if(out.native_value()!=((sa.value!=0)!=(sb.value!=0)) || !same(saved,sa)) return 70; B grouped{false}; function_grouped'.$id.'(make'.$lt.'(sa),make'.$rt.'(sb),grouped); if(grouped.native_value()!=out.native_value()) return 71; ++precedence_cases; }' . "\n";
			}
		}
		if ($numeric_xor_mode) { continue; }
		foreach (['i','d'] as $left_payload)
		{
			$lt=$family.$left_payload; $rt=$left_payload==='i'?'d':'i';
			$cppscalar=$rt==='d'?'D':'I';
			$program .= 'function snap' . $lt . $name . '(' . $types[$lt] . ' &$a, ' . $types[$rt] . ' $b, int &$calls, bool $sentinel): ' . $ret
				. ' { return ($a ' . $symbol . ' reset' . $lt . '($a,$b,$calls,$sentinel)); } ';
			$observers .= 'for(auto sa:states' . $left_payload . ') for(auto sb:states' . $rt . ') for(auto empty:empty' . $lt . ') { auto a=sa.value; auto b=sb.value; if(' . ($name==='div'?'b==0':'false') . ') continue; auto av=make' . $lt . '(sa); I calls{0}; auto out=function_snap' . $lt . $name . '(av,' . $cppscalar . '{b},calls,B{empty.kind==Kind::true_sentinel}); bool reached=' . $reach . '; if(!number_equal(out.native_value(),' . $expected . ') || calls.native_value()!=(reached?1:0) || !same(av,reached?empty:sa)) return 20; ++snapshot_cases; }' . "\n";
		}
	}
	if ($numeric_xor_mode) { continue; }
	// Equality, spaceship and nonnumeric mixed forms remain separate from ordering.
	foreach ($pairs as [$lt,$rt]) {
		foreach (['==','!=','<=>','%','**','&','|','^','<<','>>'] as $symbol) {
			$negatives[] = 'function bad(' . $types[$lt] . ' $a, ' . $types[$rt] . ' $b): void { $out = ($a ' . $symbol . ' $b); }';
		}
	}
	foreach (['i','d'] as $payload)
	{
		$type=$types[$family.$payload]; $literal=$payload==='i'?'2':'2.5'; $other=$payload==='i'?'float':'int'; $zero=$payload==='i'?'0':'0.0';
		$other_zero=$payload==='i'?'0.0':'0'; $other_nonzero=$payload==='i'?'2.5':'2';
		$tag=$family.$payload;
		$program .= 'function wordand' . $tag . '(' . $type . ' &$a): bool { return ($a = ' . $literal . ' and ' . $other_zero . '); } ';
		$program .= 'function wordor' . $tag . '(' . $type . ' &$a): bool { return ($a = ' . $zero . ' or ' . $other_nonzero . '); } ';
		$observers .= '{ auto av=make' . $tag . '(State<' . ($payload==='i'?'std::int64_t':'double') . '>{Kind::' . ($family==='n'?'absent':($family==='r'?'error':'false_sentinel')) . '}); if(function_wordand' . $tag . '(av).native_value() || !same(av,State<' . ($payload==='i'?'std::int64_t':'double') . '>{' . $literal . '})) return 30; if(!function_wordor' . $tag . '(av).native_value() || !same(av,State<' . ($payload==='i'?'std::int64_t':'double') . '>{' . $zero . '})) return 31; }' . "\n";
		if ($payload==='i') foreach (['+=','-=','*=','/='] as $symbol) { $negatives[]='function bad(' . $type . ' &$a, ' . $other . ' $b): void { $a ' . $symbol . ' $b; }'; }
		$negatives[]='$a ' . $type . ' = ' . $zero . '; $a && missing();';
		$negatives[]='$a ' . $type . ' = ' . $literal . '; $a || "bad";';
		foreach (['+','/','&&','||'] as $symbol)
		{
			$directory=sys_get_temp_dir().'/scpp_guarded_mixed_'.bin2hex(random_bytes(6)); mkdir($directory); $path=$directory.'/main.phs';
			$valid='function probe(' . $type . ' $a, ' . $other . ' $b): void { $out = ($a ' . $symbol . ' $b); }';
			try {
				file_put_contents($path,$valid); Compiler_Lifecycle::reset(); $compiler=new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
				file_put_contents($path,str_replace($other.' $b','string $b',$valid));
				foreach ([[$path],[]] as $changes) { $failed=false; try { $compiler->update_cpp($changes); } catch (\RuntimeException $e) { $failed=true; } guarded_mixed_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(),'Failed/repeated mixed edit retained artifacts'); }
				$repair=str_replace('($a '.$symbol.' $b)','($b '.$symbol.' $a)',$valid); file_put_contents($path,$repair); $compiler->update_cpp([$path]); $output=CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]); guarded_mixed_check($output===CPP_Probe::text(Model::$cpp_files),'No-op mixed repair changed output');
				Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->exec_cpp(); guarded_mixed_check($output===CPP_Probe::text(Model::$cpp_files),'Mixed repair differs from fresh');
			} finally { unlink($path); rmdir($directory); }
		}
	}

}

if ($numeric_xor_mode) {
	foreach ($types as $type) foreach (['bool','string','int16','uint32','?bool','result<bool>','result_or_false<bool>','result_or_bool<bool>','?string','result<string>','?int16','result<int16>'] as $other) {
		$negatives[]='function bad('.$type.' $a, '.$other.' $b): void { $out=($a xor $b); }';
		$negatives[]='function bad('.$other.' $a, '.$type.' $b): void { $out=($a xor $b); }';
	}
}
foreach ($negatives as $source) {
	$failed=false; try { operator_test_source($source); } catch (\RuntimeException $e) { $failed=true; }
	guarded_mixed_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(),'Excluded mixed source admitted: '.$source);
}
if ($cross_mode || $numeric_xor_mode) { $observers .= 'std::cout << "cross_snapshot_cases=" << cross_snapshot_cases << ";cross_property_cases=" << cross_property_cases << "\n";'; }
operator_test_source($program);
$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
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
 Kind kind; T value;
 State(T v):kind(Kind::payload),value(v) {}
 State(Kind k):kind(k),value(0) {}
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
$cpp .= $helpers . "\nint main() {\nconst double inf=std::numeric_limits<double>::infinity(), nan=std::numeric_limits<double>::quiet_NaN();\nlong binary_cases=0,snapshot_cases=0,precedence_cases=0;\n" . $states;
$cpp .= 'for(double zero:{0.0,-0.0}) { int code=0; try { auto v=scpp::detail::generated_operator_detail::div(I{3},D{zero}); (void)v; } catch(const std::exception& e) { code=classify(e); } if(code!=4) return 2; }' . "\n";
$cpp .= 'long cross_snapshot_cases=0,cross_property_cases=0;' . $observers . 'std::cout << "binary_cases=" << binary_cases << ";snapshot_cases=" << snapshot_cases << ";precedence_cases=" << precedence_cases << "\n"; return 0;' . "\n}\n";
file_put_contents($argv[1].'/guarded_mixed.cpp',$cpp);
echo ($numeric_xor_mode ? 'Numeric XOR: 100 signatures, ' : 'Guarded mixed numeric: 192 signatures, ') . count($negatives) . " exclusions and 32 recovery sequences passed\n";

if ($cross_mode || $numeric_xor_mode) { guarded_cross_finish($argv[1]); }
