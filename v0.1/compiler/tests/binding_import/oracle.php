<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['04_analyze/type_model/data/type_references.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/callables.php','01_prepare_inputs/load_runtime/handlers/bindings.php'] as $file) { require $base.$file; }
class Retained_Bindings { use \load_runtime\Binding_Import { call_language_binding as public binding; call_conversion as public conversion; } }
$passing=['value','borrow_const','borrow_mutable','byte_span'];$production=['none','value','owned','dependent_value'];$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $fixture) {
 $source=new \type_model\named_type_reference($fixture['source'],$fixture['source_ns']);
 $target=new \type_model\named_type_reference($fixture['target'],$fixture['target_ns']);
 $parameters=[];
 for($i=0;$i<$fixture['count'];$i++) { $parameters[]=new \type_model\semantic_parameter($source,\type_model\argument_passing::from($passing[$fixture['passing']])); }
 $signature=new \type_model\semantic_signature($parameters,new \type_model\semantic_result($target,\type_model\result_production::from($production[$fixture['production']])));
 $actual='none';
 try {
  if($fixture['mode']===0) { [$binding,$default]=Retained_Bindings::binding($fixture['row'],$signature);$actual=($binding?->value??'none').($default?':true':':false'); }
  else { $conversion=Retained_Bindings::conversion($fixture['row'],$signature);$actual=$conversion?->value??'none'; }
 } catch(\RuntimeException|\TypeError $error) { $actual='error'; }
 $out[]=$actual;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
