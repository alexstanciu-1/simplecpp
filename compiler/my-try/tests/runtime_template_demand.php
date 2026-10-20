<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host integration proof: ordinary source demand, physical recipes and incremental fragments.
[$script,$source,$output,$mode]=$argv;
$composed=$mode==='composed';
$alternate=($argv[4]??'')==='alternate';
$output_proof=($argv[5]??'')==='output';
$metadata=new runtime_catalog_input();
foreach(Model::$runtime_metadata->modules as $module){
	if($alternate && $module->name==='core'){
		$module=Runtime_Catalog_Reader::decode(file_get_contents(dirname(__DIR__,3).'/runtime/metadata/modules/core.json'),'core.json');
		$binding=$module->cpp_types['core.representation.shared'];
		$binding->symbol='fixture::Handle'; $binding->header='binding_shared.hpp';
		$bytes=$module->cpp_types['core.representation.string'];
		$bytes->byte_interface='fixture::Bytes'; $bytes->header='binding_bytes.hpp';
	}
	$metadata->modules->add($module->name,$module);
}
$metadata->modules->add('binding_proof',Runtime_Catalog_Reader::decode(file_get_contents(__DIR__.'/runtime_type_bindings.json'),'binding_proof.json'));
Compiler_Lifecycle::configure_runtime($metadata);
mkdir($source,0777,true);
$program=<<<'PHS'
class Left { public Right $other; public int $number; }
class Right { public Left $other; public int $number; }
function copy_left(const Left &$value): Left { return $value; }
function copy_shared(const shared_closed &$value): shared_closed { return $value; }
function copy_closed(const closed &$value): closed { return $value; }
function &borrow_closed(closed &$value): closed { return $value; }
function overwrite(closed &$target, const closed &$source): void { $target = $source; }
function copy_nested(const duo<closed,int> &$value): duo<closed,int> { return $value; }
PHS;
if($output_proof){$program.= <<<'PHS'
function byte_literal(int $index): string {
 if ($index === 0) { return ""; }
 if ($index === 1) { return "small"; }
 if ($index === 2) { return "a\0b"; }
 return "a long string value that exceeds short string storage";
}
function &byte_borrow(string &$value): string { return $value; }
function byte_copy(const string &$value): string { return $value; }
function output_next(int &$n): int { $n++; echo "p"; return $n; }
function &output_alias(int &$n): int { echo "r"; return $n; }
function output_work(int &$n): void { echo "[", true, false, null, -12, ":", 2.5, "]", output_next($n), ":", output_next($n), ":", $n, output_alias($n); }
function output_fail(int &$n, int $zero): void { echo output_next($n), ":", 1 / $zero, output_next($n); }
PHS;
}
file_put_contents($source.'/main.phs',$program);
$compiler=new Compiler(new cpp_lowering_options(false));
$compiler->cpp_lowering->prelower_templates=$composed;
$compiler->init([$source]);
$compiler->exec_cpp();
function demand_snapshot(): array {
	$rows=[];foreach(Model::$cpp_files as $artifact){$rows[$artifact->file_name]=$artifact->text;}return $rows;
}
function concrete_headers(): array {
	$rows=[];foreach(Model::$cpp_files as $artifact){if(str_starts_with($artifact->file_name,'specializations/concrete_')){$rows[$artifact->file_name]=$artifact->text;}}return $rows;
}
$left=Model::$global_scope->functions_named('copy_left')[0]->syntax()->require_preparation()->signature->return_type;
$forward=CPP_Types::representation_for($left,cpp_type_requirement::declaration);
$expected_wrapper=$alternate?'::fixture::Handle<':'::scpp::shared_p<';
if(!str_starts_with($forward->spelling,$expected_wrapper) || !isset($forward->forward_declarations['Left'])
	|| isset($forward->headers[CPP_Artifacts::type_header('Left')])){throw new \LogicException('Implicit storage lost its binding or incomplete payload requirement');}
// Native representations must also use output bridges in the concrete path.
if ($composed && $output_proof) {
	$output_families = [];
	foreach (Model::$cpp_output_program->fragments as $owner) {
		foreach (Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request) {
			if ($request->kind === cpp_runtime_operation_kind::output) {
				$output_families[Type_Queries::canonical($request->inputs[0])->family()->name] = true;
			}
		}
	}
	foreach ([type_family::nominal, type_family::boolean, type_family::floating, type_family::null_sentinel] as $family) {
		if (!isset($output_families[$family->name])) { throw new \LogicException('Native output bypassed its middleware bridge'); }
	}
}
// Publish production artifacts separately from the instrumented native harness below.
// Each attempt uses the existing publication owner; no fixture-owned manifest logic.
function demand_publish(Compiler $compiler, string $root, string $changed_path = ''): string
{
	$publication = new Output_Publication($root);
	try {
		if ($changed_path === '') { $compiler->cpp($publication); }
		else { $compiler->update_cpp([$changed_path], $publication); }
	}
	finally { $publication->close(); }
	return Output_Publication::require_ready($root);
}
$publication_root = $output . '-demand';
$live_snapshot = demand_snapshot();
$published_manifest = demand_publish($compiler, $publication_root);
if (demand_snapshot() !== $live_snapshot) { throw new \LogicException('First cached publication changed live emission'); }
file_put_contents($publication_root . '/user-note.txt', 'keep');
$before=demand_snapshot();
$expected_concrete=$output_proof?9:6;
$headers=concrete_headers();
if(count($headers)!==($composed?$expected_concrete:0)){throw new \LogicException('Source demand prepared the wrong type closure');}
foreach($headers as $text){if(str_contains($text,'unused')){throw new \LogicException('Unrequested runtime type was prepared');}}
$closed=Model::$global_scope->functions_named('copy_closed')[0]->syntax()->require_preparation()->signature->return_type;
$nested=Model::$global_scope->functions_named('copy_nested')[0]->syntax()->require_preparation()->signature->return_type;
$registry=Model::$type_catalog->registry();
$type_count=$registry->type_count();
$fragments=[];foreach(Model::$cpp_output_program->fragments as $owner){$fragments[]=Model::$cpp_output_program->fragments[$owner];}
$replayed_manifest = demand_publish($compiler, $publication_root);
if ($replayed_manifest !== $published_manifest) { throw new \LogicException('Cached demand changed published manifest'); }
if(demand_snapshot()!==$before || $registry->type_count()!==$type_count){throw new \LogicException('Recipe replay changed output or semantic identity');}
$index=0;foreach(Model::$cpp_output_program->fragments as $owner){if(Model::$cpp_output_program->fragments[$owner]!==$fragments[$index++]){throw new \LogicException('No-op rebuilt a fragment');}}
file_put_contents($source.'/main.phs','function empty_work(): void {}');
demand_publish($compiler, $publication_root, $source.'/main.phs');
if(concrete_headers()!==[]){throw new \LogicException('Removed source demand retained concrete types');}
foreach(Model::$cpp_output_program->fragments as $owner){
 foreach(Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request){
  if($request->kind===cpp_runtime_operation_kind::output){throw new \LogicException('Removed output retained a helper request');}
 }
}
$retired = array_diff_key($before, demand_snapshot());
if ($composed && $retired === []) { throw new \LogicException('Demand retirement proof removed no artifacts'); }
foreach ($retired as $path => $text) {
	if (file_exists($publication_root . '/' . $path)) { throw new \LogicException('Retired demand remains on disk: ' . $path); }
}
if (file_get_contents($publication_root . '/user-note.txt') !== 'keep') { throw new \LogicException('Demand retirement removed user output'); }
file_put_contents($source.'/main.phs',$program);
$restored_manifest = demand_publish($compiler, $publication_root, $source.'/main.phs');
if ($restored_manifest !== $published_manifest) { throw new \LogicException('Restored demand changed published manifest'); }
if(demand_snapshot()!==$before){throw new \LogicException('Restored demand changed output');}
$compiler->cpp_lowering->prelower_templates=!$composed;
$compiler->cpp();
if(count(concrete_headers())!==($composed?0:$expected_concrete)){throw new \LogicException('Option switch did not replace physical selection');}
$compiler->cpp_lowering->prelower_templates=$composed;
$compiler->cpp();
if(demand_snapshot()!==$before){throw new \LogicException('Option restoration changed output');}

// Reuse ordinary mappings and engine requests only after proving source-driven closure.
$closed=Model::$global_scope->functions_named('copy_closed')[0]->syntax()->require_preparation()->signature->return_type;
$nested=Model::$global_scope->functions_named('copy_nested')[0]->syntax()->require_preparation()->signature->return_type;
$engine=new CPP_Specializations();
$context=new cpp_generation_context($composed?$engine:null);
$shared=Model::$global_scope->functions_named('copy_shared')[0]->syntax()->require_preparation()->signature->return_type;
$shared_mapping=CPP_Types::representation($shared,$context);
if($composed && (!$shared_mapping->opaque || str_starts_with($shared_mapping->spelling,'::scpp::shared_p<'))){throw new \LogicException('Concrete stored representation was wrapped a second time');}
$shared_native=$composed?CPP_Types::implementation_representation($shared,$engine):CPP_Types::representation($shared);
$closed_mapping=CPP_Types::representation($closed,$context);
$nested_mapping=CPP_Types::representation($nested,$context);
$closed_native=$composed?CPP_Types::implementation_representation($closed,$engine):CPP_Types::representation($closed);
$nested_native=$composed?CPP_Types::implementation_representation($nested,$engine):CPP_Types::representation($nested);
$includes=$closed_mapping->headers+$nested_mapping->headers+$shared_mapping->headers;
$declarations=CPP_Artifacts::includes($includes,'');
$declarations.='using Closed='.$closed_mapping->spelling.'; using Nested='.$nested_mapping->spelling.";\n";
$declarations.='using Shared='.$shared_mapping->spelling.";\n";
$declarations.='void seed_shared(Shared&); bool same_shared(const Shared&,const Shared&);' . "\n";
$declarations.='void seed(Nested&); bool check(const Nested&, int); Closed& element(Nested&);' . "\n";
$native_headers=$closed_native->headers+$nested_native->headers+$shared_native->headers;
$support='#include "proof.hpp"'."\n".CPP_Artifacts::includes($native_headers,'');
$access=$composed?'(*std::launder(static_cast<'.$nested_native->spelling.'*>(value.address())))':'value';
$const_access=$composed?'(*std::launder(static_cast<const '.$nested_native->spelling.'*>(value.address())))':'value';
$closed_access=$composed?'(*std::launder(static_cast<'.$closed_native->spelling.'*>(slot.address())))':'slot';
$const_closed_access=$composed?'(*std::launder(static_cast<const '.$closed_native->spelling.'*>(slot.address())))':'slot';
$support.='void seed(Nested& value) { auto& pair='.$access.'; pair.first=decltype(pair.first)(11); auto& slot=pair.second; '.$closed_access.'='.$closed_native->spelling.'(7); }' . "\n";
$support.='Closed& element(Nested& value) { return '.$access.'.second; }' . "\n";
$support.='bool check(const Nested& value,int expected) { const auto& pair='.$const_access.'; const auto& slot=pair.second; return pair.first.native_value()==11 && '.$const_closed_access.'.native_value()==expected; }' . "\n";
$shared_access=$composed?'(*std::launder(static_cast<'.$shared_native->spelling.'*>(value.address())))':'value';
$shared_const=$composed?'(*std::launder(static_cast<const '.$shared_native->spelling.'*>(value.address())))':'value';
$other_const=$composed?'(*std::launder(static_cast<const '.$shared_native->spelling.'*>(other.address())))':'other';
$support.='using NativeShared='.$shared_native->spelling.'; void seed_shared(Shared& value){ '.$shared_access.'=NativeShared(std::make_shared<NativeShared::element_type>(9)); }' . "\n";
$support.='bool same_shared(const Shared& value,const Shared& other){ const auto& a='.$shared_const.'; const auto& b='.$other_const.'; return a.get()==b.get() && a->native_value()==9 && a.use_count()==2; }' . "\n";
$names=[];foreach(['copy_shared','copy_closed','copy_nested','borrow_closed','overwrite'] as $name){$definition=Model::$global_scope->functions_named($name)[0];$names[$name]=CPP_Callables::source_reference($definition);$declarations.='#include "'.CPP_Artifacts::callable_header($name).'"'."\n";}
$main='#include "proof.hpp"'."\n".'#include "instrument.hpp"'."\nint main(){ Nested value; seed(value); Shared shared; seed_shared(shared); begin(); { auto shared_copy=".$names['copy_shared'].'(shared); require(same_shared(shared,shared_copy)); auto copied='.$names['copy_nested'].'(value); auto& slot=element(copied); auto& alias='.$names['borrow_closed'].'(slot); require(&slot==&alias); auto old='.$names['copy_closed'].'(alias); '.$names['overwrite'].'(alias,old); require(check(copied,7) && check(value,7)); } report("recipe-boundary"); }'."\n";
if($output_proof){
 $output_type=Model::$global_scope->functions_named('output_work')[0]->syntax()->require_preparation()->signature->parameters[0]->type;
 $output_mapping=CPP_Types::representation($output_type,$context);
 $declarations.=CPP_Artifacts::includes($output_mapping->headers,'').'using OutputInt='.$output_mapping->spelling.";\n";
 foreach(['output_work','output_fail'] as $name){$names[$name]=CPP_Callables::source_reference(Model::$global_scope->functions_named($name)[0]);$declarations.='#include "'.CPP_Artifacts::callable_header($name).'"'."\n";}
 $main=str_replace('int main(){', <<<'CPP'
#include <scpp/runtime_error.hpp>
#include <iostream>
#include <streambuf>
#include <string_view>
struct Capture : std::streambuf {
 char bytes[256];
 Capture(){reset();}
 void reset(){setp(bytes,bytes+sizeof(bytes));}
 std::string_view text() const{return {pbase(),static_cast<std::size_t>(pptr()-pbase())};}
};
int main(){
CPP, $main);
 $proof='Capture capture; auto previous=std::cout.rdbuf(&capture); OutputInt n{0}; '
  .'begin(); '.$names['output_work'].'(n); measuring=false; require(capture.text()=="[1-12:2.5]p1:p2:2r2" && n.native_value()==2); report("output"); '
  .'capture.reset(); n=OutputInt{0}; bool failed=false; try { '.$names['output_fail'].'(n,OutputInt{0}); } catch(const scpp::runtime_error&){failed=true;} '
  .'require(failed && capture.text()=="p1:" && n.native_value()==1); std::cout.rdbuf(previous); ';
 $main=str_replace('int main(){','int main(){'.$proof,$main);
}
// Source-class handles retain native identity while their headers use concrete storage.
$left=Model::$global_scope->functions_named('copy_left')[0]->syntax()->require_preparation()->signature->return_type;
if($output_proof){
 $byte_type=Model::$global_scope->functions_named('byte_literal')[0]->syntax()->require_preparation()->signature->return_type;
 $byte_mapping=CPP_Types::representation($byte_type,$context);
 $declarations.=CPP_Artifacts::includes($byte_mapping->headers,'').'using Bytes='.$byte_mapping->spelling.";\nvoid byte_proof();\n";
 foreach(['byte_literal','byte_borrow','byte_copy'] as $name){$names[$name]=CPP_Callables::source_reference(Model::$global_scope->functions_named($name)[0]);$support.='#include "'.CPP_Artifacts::callable_header($name).'"'."\n";}
 $support.='#include "scpp/support/hash_t.hpp"'."\n#include \"instrument.hpp\"\n";
 $support.='void byte_proof() {
  const std::string_view expected[]={"", "small", std::string_view("a\0b",3), "a long string value that exceeds short string storage"};
  for(int i=0;i<4;++i) {
   begin(); { auto value='.$names['byte_literal'].'(OutputInt{i});
    auto copied='.$names['byte_copy'].'(value); auto moved=Bytes(static_cast<Bytes&&>(copied));
    require(scpp::hash_detail::byte_key_view(value)==expected[i]);
    require(scpp::hash_detail::byte_key_view(moved)==expected[i]);
   } report("byte-lifetime");
   auto value='.$names['byte_literal'].'(OutputInt{i});
   const scpp::string_t native(expected[i]);
   const auto old_hash=scpp::hash_detail::mix64(static_cast<std::uint64_t>(std::hash<std::string>{}(native.native_value())));
   begin(); {
    auto& alias='.$names['byte_borrow'].'(value); require(&alias==&value);
    require(scpp::hash_detail::key_ops<Bytes>::hash(alias)==old_hash);
    require(scpp::hash_detail::key_ops<scpp::string_t>::hash(native)==old_hash);
    require(scpp::hash_detail::key_ops<Bytes>::equal(value,alias));
    require(scpp::hash_detail::byte_key_view(value)==expected[i]);
   } report("byte-borrow-hash");
  }
 }'."\n";
 $main=str_replace(' Nested value;', ' byte_proof(); Nested value;', $main);
}
$left_mapping=CPP_Types::representation($left,$context);
$left_native=$composed?CPP_Types::implementation_representation($left,$engine):CPP_Types::representation($left);
$left_payload=CPP_Types::payload($left,$context);
$declarations.=CPP_Artifacts::includes($left_mapping->headers,'').'using SourceOwner='.$left_mapping->spelling.'; void source_owner_proof();'."\n";
$support.=CPP_Artifacts::includes($left_native->headers+$left_payload->headers,'').'#include "instrument.hpp"'."\n"
 .'#include "'.CPP_Artifacts::callable_header('copy_left').'"'."\n";
$left_access=$composed?'(*std::launder(static_cast<'.$left_native->spelling.'*>(owner.address())))':'owner';
$left_copy=CPP_Callables::source_reference(Model::$global_scope->functions_named('copy_left')[0]);
$support.='void source_owner_proof(){ SourceOwner owner; '.$left_access.'='.$left_native->spelling.'(std::make_shared<'.$left_payload->spelling.'>()); '
 .'auto* original=owner.operator->(); begin(); { auto copied='.$left_copy.'(owner); require(copied.operator->()==original && '.$left_access.'.use_count()==2); '
 .'require(&*copied==original); } require('.$left_access.'.use_count()==1); report("source-owner"); measuring=false; '
 .'SourceOwner empty; bool failed=false; try { (void)empty.operator->(); } catch(const scpp::runtime_error&){failed=true;} require(failed); }'."\n";
$main=str_replace(' Nested value;', ' source_owner_proof(); Nested value;', $main);
$artifacts=new Storage /** Storage<cpp_artifact> */();
foreach(Model::$cpp_files as $artifact){if($artifact->file_name!=='entry.cpp'){$artifacts->append($artifact);}}
if($alternate){$artifacts->append(new cpp_artifact('proof:byte-binding',cpp_artifact_kind::header,'binding_bytes.hpp',"#pragma once\n#include <scpp/support/string_representation.hpp>\nnamespace fixture { using Bytes=scpp::string_representation; }\n"));$artifacts->append(new cpp_artifact('proof:storage-binding',cpp_artifact_kind::header,'binding_shared.hpp',"#pragma once\n#include <scpp/shared_p.hpp>\nnamespace fixture { template<class T> using Handle=scpp::shared_p<T>; }\n"));}
$artifacts->append(new cpp_artifact('proof:header',cpp_artifact_kind::header,'proof.hpp',$declarations));
$artifacts->append(new cpp_artifact('proof:support',cpp_artifact_kind::translation_unit,'support.cpp',$support));
$artifacts->append(new cpp_artifact('proof:main',cpp_artifact_kind::translation_unit,'entry.cpp',$main));
foreach(['instrument.hpp','instrument.cpp'] as $path){$artifacts->append(new cpp_artifact('proof:'.$path,$path==='instrument.hpp'?cpp_artifact_kind::header:cpp_artifact_kind::translation_unit,$path,file_get_contents(__DIR__.'/fixtures/prelower_boundary/'.$path)));}
$publisher=new Output_Publication($output);
try{$publisher->publish($artifacts,new runtime_cpp_build_spec());}finally{$publisher->close();}
echo "Closed-template source demand, nested substitution, disk retirement/restoration, replay and option switching passed\n";
