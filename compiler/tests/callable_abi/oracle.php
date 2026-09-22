<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/data/';
foreach (['type_references.php','semantic_calls.php','callables.php'] as $file) { require $base.$file; }
$type=new \type_model\provider_type_reference('runtime','value');
$integer=new \type_model\runtime_integer_abi(32,\type_model\abi_extension::sign);
$borrow=new \type_model\runtime_borrow_abi(true);
$span=new \type_model\runtime_byte_span_abi(new \type_model\runtime_integer_abi(64,\type_model\abi_extension::zero));
$shapes=[$integer,new \type_model\runtime_borrow_abi(false),$borrow,$span];
$passing=[\type_model\argument_passing::value,\type_model\argument_passing::borrow_const,\type_model\argument_passing::borrow_mutable,\type_model\argument_passing::byte_span];
$out=['passing'=>[],'results'=>[]];
foreach ($passing as $mode) {
 foreach ($shapes as $shape) {
  $signature=new \type_model\semantic_signature([new \type_model\semantic_parameter($type,$mode)],new \type_model\semantic_result($type,\type_model\result_production::none));
  $abi=new \type_model\runtime_callable_abi('link','c',null,[$shape]);$accepted=true;
  try { $abi->validate($signature); } catch (\InvalidArgumentException $e) { $accepted=false; }
  $out['passing'][]=$accepted;
 }
}
foreach (\type_model\result_production::cases() as $production) {
 foreach (\type_model\result_passing::cases() as $transport) {
  foreach ([null,$integer] as $result) {
   $signature=new \type_model\semantic_signature([],new \type_model\semantic_result($type,$production));
   $abi=new \type_model\runtime_callable_abi('link','c',$result,[],$transport);$accepted=true;
   try { $abi->validate($signature); } catch (\InvalidArgumentException $e) { $accepted=false; }
   $out['results'][]=$accepted;
  }
 }
}
$out['direct_slots']=(new \type_model\runtime_callable_abi('link','c',null,[$integer,$span,$borrow]))->parameter_indices;
$out['owned_slots']=(new \type_model\runtime_callable_abi('link','c',null,[$integer,$span,$borrow],\type_model\result_passing::caller_storage))->parameter_indices;
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
