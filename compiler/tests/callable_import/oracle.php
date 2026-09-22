<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['04_analyze/type_model/data/type_references.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/resources.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/records.php','04_analyze/type_model/data/callables.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/callables.php','01_prepare_inputs/load_runtime/handlers/resources.php','01_prepare_inputs/load_runtime/handlers/bindings.php','01_prepare_inputs/load_runtime/utilities/callable_bindings.php','01_prepare_inputs/load_runtime/data/family_preparation.php'] as $file) { require $base.$file; }
class Retained_Callables {
 use \load_runtime\Package_Syntax;
 use \load_runtime\Resource_Import;
 use \load_runtime\Binding_Import;
 use \load_runtime\Callable_Import { callables as public import; }
}
$life=new \type_model\lifetime_contract(\type_model\copy_kind::unavailable,\type_model\cleanup_kind::none);
$word=new \type_model\named_type_definition('Word','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(32)),$life,signed:true,struct_field:true);
$object=new \type_model\named_type_definition('Object','',new \type_model\representation_record(\type_model\representation_kind::opaque_inline,new \type_model\opaque_representation(8,8)),$life);
$span=new \type_model\named_type_definition('Span','',new \type_model\representation_record(\type_model\representation_kind::byte_span,null),$life);
$void=new \type_model\named_type_definition('Void','',new \type_model\representation_record(\type_model\representation_kind::void_type,null),null);
$record=new \type_model\record_declaration('Row','',[],true,\type_model\record_layout_policy::target);
$types=[];
foreach([['word','integer',4,4,32,true,$word,null],['object','opaque_inline',8,8,null,null,$object,null],['span','byte_span',16,8,null,null,$span,null],['void','void',0,1,null,null,$void,null],['record','record',8,8,null,null,null,$record],['hidden','opaque_inline',8,8,null,null,null,null]] as [$id,$kind,$size,$align,$bits,$signed,$definition,$row]) {
 $types[$id]=new \load_runtime\runtime_type($id,new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::from($kind),$size,$align),$bits,$signed,$definition,$row);
}
$other=new \type_model\named_type_definition('Other','',new \type_model\representation_record(\type_model\representation_kind::opaque_inline,new \type_model\opaque_representation(8,8)),$life);
$owner=new \type_model\named_type_definition('Owner','',new \type_model\representation_record(\type_model\representation_kind::opaque_inline,new \type_model\opaque_representation(8,8)),$life,resource:\type_model\resource_kind::allocation);
$types['other']=new \load_runtime\runtime_type('other',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::opaque_inline,8,8),null,null,$other);
$types['owner']=new \load_runtime\runtime_type('owner',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::opaque_inline,8,8),null,null,$owner);
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $fixture) {
 $bindings=[];
 foreach($fixture['bindings'] as $id=>$binding) {
  $bindings[$id]=isset($binding['provided']) ? new \type_model\provider_type_reference('provider','id') : new \type_model\named_type_reference($binding['name'],$binding['namespace']);
 }
 // This consumer tests key membership only; no backend export behavior is simulated.
 $payload_membership=array_fill_keys($fixture['payloads'],true);
 $package_bindings=new \load_runtime\package_bindings(callables:$bindings,sources:$payload_membership);
 $actual='';
 try {
  $accepted=Retained_Callables::import($fixture['rows'],$types,'provider',$package_bindings);
  foreach($accepted as $callable) {
   $role=$callable->language_binding===null?-1:($callable->language_binding===\type_model\language_binding::byte_literal?0:1);
   $purpose=$callable->conversion_purpose===null?-1:($callable->conversion_purpose===\type_model\conversion_purpose::explicit_cast?1:3);
   $effect=$callable->signature->allocation_effect; $effect_kind=$effect===null?0:match($effect->kind->value){'acquire'=>1,'release'=>2,'transfer'=>3,'inspect'=>4,'mutate'=>5,'observe'=>6};
   $actual.=$callable->id.'/'.$callable->name.'/'.$callable->namespace_name.'/'.count($callable->signature->parameters).'/'.($callable->abi->result_passing===\type_model\result_passing::caller_storage?1:0).'/'.$role.'/'.($callable->default_literal?'1':'0').'/'.$purpose.'/'.$effect_kind.';';
  }
 } catch(\RuntimeException|\InvalidArgumentException $error) { $actual='error'; }
 $out[]=$actual;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
