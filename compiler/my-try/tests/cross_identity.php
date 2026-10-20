<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
function wrapper_equality_check(bool $ok, string $message): void {
	if (!$ok) { throw new \LogicException($message); }
}
$carrier=$argv[2]; $right_carrier=$argv[3]; $payload=$argv[4]; $right_payload=$argv[5] ?? $payload;
$ordinary_numeric=($argv[6] ?? '')==='--ordinary-numeric';
$ordinary_cross=($argv[6] ?? '')==='--ordinary-cross';
$scalar_pair=$carrier==='plain' && $right_carrier==='plain';
$wrapped=$carrier==='plain'?$payload:($carrier==='nullable'?'?'.$payload:$carrier.'<'.$payload.'>');
$right_wrapped=$right_carrier==='plain'?$right_payload:($right_carrier==='nullable'?'?'.$right_payload:$right_carrier.'<'.$right_payload.'>');
$types=['n'=>$wrapped,'i'=>$right_wrapped];
$initial=$payload==='bool'?'false':($payload==='string'?'""':($payload==='float'?'0.0':'0'));
$second=$right_payload==='bool'?'true':($right_payload==='string'?'"a"':($right_payload==='float'?'1.0':'1'));
$right_initial=$right_payload==='bool'?'false':($right_payload==='string'?'""':($right_payload==='float'?'0.0':'0'));
$forms=['id'=>'===','nid'=>'!=='];
$operations=$scalar_pair?['id'=>operator_operation::scalar_identical,'nid'=>operator_operation::scalar_not_identical]:['id'=>operator_operation::wrapper_identical,'nid'=>operator_operation::wrapper_not_identical];
if ($ordinary_numeric || $ordinary_cross) {
	$forms=['eq'=>'==','ne'=>'!='];
	$operations=$scalar_pair?['eq'=>operator_operation::floating_equal,'ne'=>operator_operation::floating_not_equal]:['eq'=>operator_operation::wrapper_equal,'ne'=>operator_operation::wrapper_not_equal];
}
$program=''; $observers=''; $signatures=[];
foreach ($types as $tag=>$type) {
	$default=$tag==='n'?$initial:$right_initial;
	$program.='class Box'.$tag.' { public '.$type.' $value='.$default.'; } '
		.'function mark'.$tag.'('.$type.' $v, int &$calls, int &$trace, int $digit, bool $fail): '.$type.' { $calls++; $trace=$trace*10+$digit; if ($fail) { if ($digit===1) { $bad=1/0; } else { $bad=1%0; } } return $v; } ';
}
foreach ($forms as $name=>$symbol) foreach ([['n','i']] as [$lt,$rt]) {
	$id=$lt.$rt.$name; $left=$types[$lt]; $right=$types[$rt]; $invert=$name==='nid' || $name==='ne';
	$expected=($invert?'!':'').'state_matches(sa,sb)';
	$signatures[]=[$left,$symbol,$right,'bool'];
	$syntax=operator_test_source('$a '.$left.' = '.$initial.'; $b '.$right.' = '.$second.'; $out=($a '.$symbol.' $b);');
	$node=$syntax->root->single_body()->body->statements[2]->expression->value;
	$d=$node->require_binary_preparation()->decision;
	wrapper_equality_check($d->operation===$operations[$name] && count($d->operand_extractions)===0 && count($d->operands)===2 && $d->result_type->matches(Language_Types::boolean(Model::$language_scope)), 'Equality must retain states without extraction');
	foreach ($d->operands as $conversion) {
		if ($ordinary_numeric && $scalar_pair) {
			$integer=$conversion->source_type->matches(Language_Types::integer(Model::$language_scope));
			wrapper_equality_check($conversion->operation===($integer?conversion_operation::integer_to_floating:conversion_operation::identity)
				&& $conversion->target_type->matches(Language_Types::floating(Model::$language_scope)),'Scalar mixed equality lost numeric promotion');
		} else {
			wrapper_equality_check($conversion->operation===conversion_operation::identity && $conversion->source_type->matches($conversion->target_type),'Equality changed operand representation');
		}
	}
	Preparation_Cleanup::tree($syntax->root);
	wrapper_equality_check($node->preparation()===null,'Cleanup retained equality facts');
	$program.='function binary'.$id.'('.$left.' $a, '.$right.' $b, int &$lc, int &$rc, int &$trace, bool $lf, bool $rf): bool { return (mark'.$lt.'($a,$lc,$trace,1,$lf) '.$symbol.' mark'.$rt.'($b,$rc,$trace,2,$rf)); } '
		.'function reset'.$id.'('.$left.' &$a, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): '.$right.' { $calls++; $a=$replacement; if ($fail) { $bad=1%0; } return $b; } '
		.'function snap'.$id.'('.$left.' &$a, '.$left.' $replacement, '.$right.' $b, int &$calls, bool $fail): bool { return ($a '.$symbol.' reset'.$id.'($a,$replacement,$b,$calls,$fail)); } '
		.'function resetbox'.$id.'(Box'.$lt.' $box, '.$left.' $replacement, '.$right.' $b, int &$calls): '.$right.' { $calls++; $box->value=$replacement; return $b; } '
		.'function prop'.$id.'('.$left.' $a, '.$left.' $replacement, '.$right.' $b, int &$calls, '.$left.' &$final): bool { $box=new Box'.$lt.'(); $box->value=$a; $out=($box->value '.$symbol.' resetbox'.$id.'($box,$replacement,$b,$calls)); $final=$box->value; return $out; } ';
	$call='function_binary'.$id.'(av,bv,lc,rc,trace,B{lf},B{rf})';
	$observers.='for(auto sa:states'.$lt.') for(auto sb:states'.$rt.') for(bool lf:{false,true}) for(bool rf:{false,true}) { auto av=make'.$lt.'(sa); auto bv=make'.$rt.'(sb); I lc{0},rc{0},trace{0}; int failure=0; try { static_assert(std::is_same_v<decltype('.$call.'),B>); auto out='.$call.'; if(out.native_value()!=('.$expected.')) return 10; } catch(const std::exception& e) { failure=classify(e); } if(failure!=(lf?4:rf?5:0) || lc.native_value()!=1 || rc.native_value()!=(lf?0:1) || trace.native_value()!=(lf?1:12) || !same(av,sa) || !same(bv,sb)) return 11; ++binary_cases; }' . "\n";
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

foreach (['==','!='] as $symbol) {
 if (guarded_fixture_equality_pair($wrapped,$right_wrapped,$symbol)) { continue; }
 guarded_cross_reject('function bad('.$wrapped.' $a, '.$right_wrapped.' $b): bool { return $a '.$symbol.' $b; }'); ++$negative_count;
}
if ($ordinary_numeric && $scalar_pair) {
 foreach (['plain','nullable','result','result_or_false','result_or_bool'] as $a)
 foreach (['plain','nullable','result','result_or_false','result_or_bool'] as $b)
 foreach (['==','!='] as $symbol) {
  $left_type=$a==='plain'?$payload:($a==='nullable'?'?'.$payload:$a.'<'.$payload.'>');
  $right_type=$b==='plain'?$right_payload:($b==='nullable'?'?'.$right_payload:$b.'<'.$right_payload.'>');
  if (guarded_fixture_equality_pair($left_type,$right_type,$symbol)) { continue; }
  guarded_cross_reject('function bad('.$left_type.' $a, '.$right_type.' $b): bool { return $a '.$symbol.' $b; }'); ++$negative_count;
 }
}
foreach ($forms as $symbol) foreach (['int','float','bool','string','int16'] as $other) {
 if ($other===$payload) { continue; }
 $other_type=$right_carrier==='plain'?$other:($right_carrier==='nullable'?'?'.$other:$right_carrier.'<'.$other.'>');
 if (guarded_fixture_equality_pair($wrapped,$other_type,$symbol)) { continue; }
 // Existing plain integer equality already admits signed-width pairs. Preserve
 // that positive control; float/int16 and all carrier/int16 pairs still reject.
 if ($ordinary_numeric && $scalar_pair && $payload==='int' && $other==='int16') {
  operator_test_source('function existing(int $a, int16 $b): bool { return $a '.$symbol.' $b; }');
  continue;
 }
 guarded_cross_reject('function bad('.$wrapped.' $a, '.$other_type.' $b): bool { return $a '.$symbol.' $b; }'); ++$negative_count;
}
if ($payload!==$right_payload && in_array('bool',[$payload,$right_payload],true)) {
 foreach (['&&','||','and','or','xor'] as $symbol) {
  guarded_cross_reject('function bad('.$wrapped.' $a, '.$right_wrapped.' $b): bool { return ($a '.$symbol.' $b); }'); ++$negative_count;
 }
}
operator_test_source($program);
$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$families=['nullable'=>0,'result'=>1,'result_or_false'=>2,'result_or_bool'=>3,'plain'=>4];
$native=['int'=>['scpp::int_t<>','std::int64_t'],'float'=>['scpp::float_t','double'],'bool'=>['scpp::bool_t','bool'],'string'=>['scpp::string_t','std::string']];
$cpp.='
#include "'.__DIR__.($ordinary_cross?'/cross_equality_support.hpp':($ordinary_numeric?'/mixed_equality_support.hpp':'/cross_identity_support.hpp')).'"
using namespace identity_probe;
';
$cpp.='using V='.$native[$payload][0].'; using P='.$native[$payload][1].'; using W='.$native[$right_payload][0].'; using Q='.$native[$right_payload][1].'; using S=State<P>;
';
$cpp.='auto maken(S s) { return make<'.$families[$carrier].',V>(s); } auto makei(State<Q> s) { return make<'.$families[$right_carrier].',W>(s); }
';
$cpp.='bool state_matches(S a,State<Q> b) { return '.($ordinary_cross?'cross_equality_probe':($ordinary_numeric?'mixed_equality_probe':'identity_probe')).'::oracle(a,b); }
';
$cpp.= <<<'CPP'
int classify(const std::exception& e) {
 if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)) {
  if(p->code()=="division_by_zero") return 4;
  if(p->code()=="modulo_by_zero") return 5;
 }
 return 90;
}
int main() {
CPP;
$cpp.='auto statesn=states<'.$families[$carrier].',P>(); auto statesi=states<'.$families[$right_carrier].',Q>(); long binary_cases=0,local_cases=0,property_cases=0;
';
$cpp.=$observers.'std::cout << "binary_cases=" << binary_cases << ";local_cases=" << local_cases << ";property_cases=" << property_cases << "\n"; return 0; }';
file_put_contents($argv[1].'/cross_identity.cpp',$cpp);
file_put_contents($argv[1].'/signatures.json',json_encode($signatures,JSON_PRETTY_PRINT)."\n");
echo count($signatures).' signatures, '.count($signatures).' recovery sequences, '.$negative_count." exclusions passed\n";
