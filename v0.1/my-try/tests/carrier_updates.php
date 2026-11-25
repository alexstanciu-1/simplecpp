<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
require_once __DIR__.'/guarded_cross_support.php';
$carrier=$argv[2];$payload=$argv[3];
if (!in_array($carrier,['nullable','result','result_or_false','result_or_bool'],true) || !in_array($payload,['int','float'],true)) { throw new \InvalidArgumentException('Expected numeric carrier'); }
$type=$carrier==='nullable'?'?'.$payload:$carrier.'<'.$payload.'>';
$zero=$payload==='int'?'0':'0.0';
$forms=['preinc'=>['++$x',operator_operation::wrapper_pre_increment,1,false], 'postinc'=>['$x++',operator_operation::wrapper_post_increment,1,true], 'predec'=>['--$x',operator_operation::wrapper_pre_decrement,-1,false], 'postdec'=>['$x--',operator_operation::wrapper_post_decrement,-1,true]];
function update_check(bool $ok,string $why):void { if(!$ok) throw new \LogicException($why); }
$program='class Box { public '.$type.' $v='.$zero.'; public static '.$type.' $s='.$zero.'; } function qualifier(int &$calls): Box { $calls++; return new Box(); } function staticread(): '.$type.' { return Box::$s; } function explode(int &$calls): '.$type.' { $calls++; $bad=1/0; return '.$zero.'; } ';
$observers='';$signatures=[];$negatives=0;
foreach($forms as $name=>[$expression,$operation,$delta,$post]) {
 $syntax=operator_test_source('$x '.$type.' = '.$zero.'; $r='.$expression.';');
 $node=$syntax->root->single_body()->body->statements[1]->expression->value;$facts=$node->require_mutation_preparation();
 update_check($facts->decision->operation===$operation && $facts->type->matches($node->target->require_preparation()->type) && !$facts->addressable() && $node->target->require_preparation()->addressable() && count($facts->decision->operand_extractions)===0 && $facts->decision->operands[0]->operation===conversion_operation::identity,'Lost carrier mutation facts');
 Preparation_Cleanup::tree($syntax->root);update_check($node->preparation()===null && $node->target->preparation()===null,'Stale mutation facts');
 $signatures[]=[$type,$name,$type];
 $program.='function '.$name.'('.$type.' &$x): '.$type.' { return '.$expression.'; } '
  .'function local'.$name.'('.$type.' $input, '.$type.' &$final): '.$type.' { $x=$input; $r='.$expression.'; $final=$x; return $r; } '
  .'function snap'.$name.'('.$type.' &$x, '.$type.' $replacement): '.$type.' { $r='.$expression.'; $x=$replacement; return $r; } '
  .'function prop'.$name.'('.$type.' &$x, '.$type.' $replacement, '.$type.' &$written): '.$type.' { $box=new Box(); $alias=$box; $box->v=$x; $r='.str_replace('$x','$box->v',$expression).'; $written=$alias->v; $box->v=$replacement; $x=$alias->v; return $r; } '
  .'function static'.$name.'('.$type.' $input,int &$calls): '.$type.' { Box::$s=$input; return '.str_replace('$x','qualifier($calls)::$s',$expression).'; } '
  .'function null'.$name.'(int &$calls): '.$type.' { $box Box; $r='.str_replace('$x','$box->v',$expression).'; $calls++; return $r; } '
  .'function after'.$name.'('.$type.' &$x,int &$calls): bool { return ('.$expression.') === explode($calls); } '
  .'function before'.$name.'('.$type.' &$x,int &$calls): bool { return explode($calls) === ('.$expression.'); } ';
 $native=$post?'x'.($delta===1?'++':'--'):($delta===1?'++':'--').'x';
 $observers.='for(auto s:states) { if(!valid(s,'.$delta.')) continue; State next=s; if(s.kind==0) next.value+=P{'.$delta.'}; State returned='.($post?'s':'next').'; auto x=make(s); int failure=0; try { static_assert(std::is_same_v<decltype(function_'.$name.'(x)),N>); auto result=function_'.$name.'(x); if(!same(result,returned)) return 10; } catch(const std::exception& e) {failure=classify(e);} if(failure!=(s.kind?unwrap_error:0) || !same(x,next)) return 11; ++source_cases;' . "\n";
 $observers.='x=make(s); failure=0; try { '.($post?'static_assert(std::is_same_v<decltype('.$native.'),N>); auto result='.$native.';':'static_assert(std::is_same_v<decltype('.$native.'),N&>); auto& ref='.$native.'; if(&ref!=&x) return 12; auto result=ref;').' if(!same(result,returned)) return 13; x=make(State{0,P{42}}); if(!same(result,returned)) return 14; } catch(const std::exception& e) {failure=classify(e);} if(failure!=(s.kind?unwrap_error:0) || (s.kind && !same(x,s))) return 15; ++native_cases;' . "\n";
 $observers.='I calls{0}; failure=0; try { auto result=function_static'.$name.'(make(s),calls); if(!same(result,returned)) return 16; } catch(const std::exception& e) {failure=classify(e);} if(failure!=(s.kind?unwrap_error:0) || calls.native_value()!=1 || !same(function_staticread(),next)) return 17; ++static_cases;' . "\n";
 $observers.='x=make(s); calls=I{0}; failure=0; try { (void)function_after'.$name.'(x,calls); } catch(const std::exception& e) {failure=classify(e);} if(failure!=(s.kind?unwrap_error:4) || calls.native_value()!=(s.kind?0:1) || !same(x,next)) return 18; x=make(s); calls=I{0}; failure=0; try { (void)function_before'.$name.'(x,calls); } catch(const std::exception& e) {failure=classify(e);} if(failure!=4 || calls.native_value()!=1 || !same(x,s)) return 19; ++order_cases;' . "\n";
 $observers.='if(s.kind==0) { auto final=make(s); auto result=function_local'.$name.'(make(s),final); if(!same(result,returned) || !same(final,next)) return 20; for(auto replacement:states) { x=make(s); auto r=function_snap'.$name.'(x,make(replacement)); if(!same(r,returned) || !same(x,replacement)) return 21; x=make(s); auto written=make(s); r=function_prop'.$name.'(x,make(replacement),written); if(!same(r,returned) || !same(written,next) || !same(x,replacement)) return 22; ++snapshot_cases; } } else { x=make(s); auto written=make(State{0,P{42}}); failure=0; try { (void)function_prop'.$name.'(x,make(State{0,P{42}}),written); } catch(const std::exception& e) {failure=classify(e);} if(failure!=unwrap_error || !same(x,s) || !same(written,State{0,P{42}})) return 23; } }' . "\n";
 $observers.='{ I calls{0}; bool threw=false; try { (void)function_null'.$name.'(calls); } catch(const std::exception& e) { threw=true; } if(!threw || calls.native_value()!=0) return 24; }';
 foreach(['function bad(const '.$type.' &$x): void { '.$expression.'; }','function sink('.$type.' &$x): void {} $x '.$type.' = '.$zero.'; sink('.$expression.');','$x '.$type.' = '.$zero.'; ('.$expression.')++;','$v vector<'.$type.'> = ['.$zero.']; '.str_replace('$x','$v[0]',$expression).';','class C { private '.$type.' $x; } $c=new C(); '.str_replace('$x','$c->x',$expression).';'] as $bad) { guarded_cross_reject($bad);++$negatives; }
 foreach(['?bool','?string','?int16','result<bool>','result<string>','result<int16>','result_or_false<bool>','result_or_bool<string>','result<?int>'] as $badtype) { guarded_cross_reject('function bad('.$badtype.' &$x): void { '.$expression.'; }');++$negatives; }
 $dir=sys_get_temp_dir().'/scpp_updates_'.bin2hex(random_bytes(6));mkdir($dir);$path=$dir.'/main.phs';
 $valid='function probe('.$type.' &$x): '.$type.' { return '.$expression.'; }';
 try { file_put_contents($path,$valid);Compiler_Lifecycle::reset();$compiler=new Compiler(new cpp_lowering_options(false));$compiler->init([$dir]);$compiler->exec_cpp();file_put_contents($path,str_replace($type.' &$x','const '.$type.' &$x',$valid));
 foreach([[$path],[]] as $changes) { $failed=false;try{$compiler->update_cpp($changes);}catch(\RuntimeException $e){$failed=true;} update_check($failed && Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(),'Failed edit retained artifacts'); }
 file_put_contents($path,$valid);$compiler->update_cpp([$path]);$output=CPP_Probe::text(Model::$cpp_files);$compiler->update_cpp([]);update_check($output===CPP_Probe::text(Model::$cpp_files),'No-op changed output');Compiler_Lifecycle::reset();$compiler->init([$dir]);$compiler->exec_cpp();update_check($output===CPP_Probe::text(Model::$cpp_files),'Fresh mismatch');
 }finally{unlink($path);rmdir($dir);}
}
operator_test_source($program);$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.="\n#include \"scpp/generated/operators.hpp\"\n#include <limits>\n#include <bit>\n#include <vector>\n#include <iostream>\nusing I=scpp::int_t<>; using B=scpp::bool_t;\n";
$cpp.=$payload==='int'?'using V=I; using P=std::int64_t;':'using V=scpp::float_t; using P=double;';
$cpp.='using N=scpp::'.$carrier.'<V>; struct State {int kind;P value;int error=0;};';
$cpp.=$payload==='int'?'bool bits(P a,P b){return a==b;} bool valid(State s,int d){return s.kind || !((d==1 && s.value==std::numeric_limits<P>::max()) || (d==-1 && s.value==std::numeric_limits<P>::min()));}':'bool bits(P a,P b){return std::bit_cast<std::uint64_t>(a)==std::bit_cast<std::uint64_t>(b);} bool valid(State,int){return true;}';
$cpp.='N make(State s){if(!s.kind)return N{V{s.value}};';
if($carrier==='nullable'){$cpp.='return N{scpp::null};';}
elseif($carrier==='result'){$cpp.='return N{scpp::error_t{scpp::string_t{s.error?"other":"failure"},I{s.error?19:17},scpp::string_t{s.error?"second":"origin"}}};';}
elseif($carrier==='result_or_bool'){$cpp.='return s.kind==2?N{scpp::true_sentinel}:N{scpp::false_sentinel};';}
else{$cpp.='return N{scpp::false_sentinel};';}
$cpp.='} bool same(const N& n,State s){if(!s.kind)return n.has_value().native_value() && bits(n.require_value("observe").native_value(),s.value); if(n.has_value().native_value())return false;';
if($carrier==='result'){$cpp.='const auto* e=n.error();return e && e->get_message().native_value()==(s.error?"other":"failure") && e->get_line().native_value()==(s.error?19:17) && e->get_file().native_value()==(s.error?"second":"origin");';}
elseif($carrier==='result_or_bool'){$cpp.='return s.kind==2?n.is_true().native_value():n.is_false().native_value();';}
else{$cpp.='return true;';}
$cpp.='} int classify(const std::exception& e){ if(auto p=dynamic_cast<const scpp::runtime_error*>(&e)){if(p->code()=="division_by_zero")return 4;if(p->code()=="invalid_nullable_unwrap_empty")return 1;} std::string m=e.what();if(m.find("requires a present")!=std::string::npos){if(m.starts_with("scpp::result:"))return 2;if(m.starts_with("scpp::result_or_false:"))return 3;if(m.starts_with("scpp::result_or_bool:"))return 6;}return 90;}';
$err=['nullable'=>1,'result'=>2,'result_or_false'=>3,'result_or_bool'=>6][$carrier];
$cpp.='int main(){ constexpr int unwrap_error='.$err.'; std::vector<State> states{{1,P{}}};';
if($carrier==='result_or_bool')$cpp.='states.push_back({2,P{}});';
if($carrier==='result')$cpp.='states.push_back({1,P{},1});';
$values=$payload==='int'?'std::vector<P>{std::numeric_limits<P>::min(),std::numeric_limits<P>::min()+1,-1,0,1,std::numeric_limits<P>::max()-1,std::numeric_limits<P>::max()}':'std::vector<P>{0.0,-0.0,1.5,-1.5,1e20,std::numeric_limits<P>::infinity(),-std::numeric_limits<P>::infinity(),std::bit_cast<P>(std::uint64_t{0x7ff8000000000001ULL})}';
$cpp.='for(auto v:'.$values.')states.push_back({0,v});long source_cases=0,native_cases=0,static_cases=0,order_cases=0,snapshot_cases=0;'.$observers.'std::cout<<"source_cases="<<source_cases<<";native_cases="<<native_cases<<";static_cases="<<static_cases<<";order_cases="<<order_cases<<";snapshot_cases="<<snapshot_cases<<"\n";return 0;}';
file_put_contents($argv[1].'/carrier_updates.cpp',$cpp);file_put_contents($argv[1].'/signatures.json',json_encode($signatures,JSON_PRETTY_PRINT)."\n");echo "carrier updates: 4 signatures, 4 recoveries, $negatives exclusions passed\n";
