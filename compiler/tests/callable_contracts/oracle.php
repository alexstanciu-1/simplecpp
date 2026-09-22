<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/data/';
foreach(['type_references.php','semantic_calls.php','resources.php','definitions.php','callables.php'] as $file) { require $base.$file; }
function ref(int $mode): \type_model\type_reference {
 if($mode<3) { return new \type_model\named_type_reference($mode===1?'U':'T',$mode===2?'other':'ns'); }
 if($mode<6) { return new \type_model\provider_type_reference($mode===4?'q':'p',$mode===5?'U':'T'); }
 if($mode<9) { return new \type_model\parameter_type_reference($mode===8?'other':'owner',$mode===7?1:0); }
 $args=[ref(0),ref(6)];
 if($mode===10) {$args[1]=ref(7);} if($mode===11) {$args=[ref(6),ref(0)];} if($mode===12) {$args[]=ref(0);}
 return new \type_model\family_type_reference('Outer',[new \type_model\family_type_reference($mode===13?'OtherFamily':'Family',$args)]);
}
function make(int $mode): \type_model\runtime_callable {
 $provider=$mode===1?'q':'p';$id=$mode===2?'g':'f';$name=$mode===3?'G':'F';$ns=$mode===4?'other':'ns';
 $link=$mode===5?'other_link':'link';$cc=$mode===6?'fastcc':'ccc';
 $type=ref($mode===7?10:9);$result_type=ref($mode===8?1:0);
 $parameters=[new \type_model\semantic_parameter($type,\type_model\argument_passing::value),new \type_model\semantic_parameter($type,$mode===13?\type_model\argument_passing::borrow_mutable:\type_model\argument_passing::borrow_const),new \type_model\semantic_parameter($type,\type_model\argument_passing::byte_span)];
 $physical=[new \type_model\runtime_integer_abi($mode===9?32:64,$mode===10?\type_model\abi_extension::sign:\type_model\abi_extension::none),new \type_model\runtime_borrow_abi($mode===13),new \type_model\runtime_byte_span_abi(new \type_model\runtime_integer_abi($mode===11?32:64,$mode===12?\type_model\abi_extension::zero:\type_model\abi_extension::none))];
 if($mode===14) {$parameters[]=new \type_model\semantic_parameter($type,\type_model\argument_passing::value);$physical[]=new \type_model\runtime_integer_abi(64,\type_model\abi_extension::none);}
 $production=$mode===15?\type_model\result_production::none:($mode===16?\type_model\result_production::owned:\type_model\result_production::value);
 $effect=null;
 if($mode>=19 && $mode<=22) {$effect=new \type_model\allocation_effect($mode>=21?\type_model\allocation_effect_kind::transfer:\type_model\allocation_effect_kind::inspect,$mode===20?1:0,$mode>=21?($mode===21?1:2):null);}
 $signature=new \type_model\semantic_signature($parameters,new \type_model\semantic_result($result_type,$production),$effect);
 $result=($mode===15 || $mode===16)?null:new \type_model\runtime_integer_abi($mode===17?32:64,$mode===18?\type_model\abi_extension::zero:\type_model\abi_extension::none);
 $abi=new \type_model\runtime_callable_abi($link,$cc,$result,$physical,$mode===16?\type_model\result_passing::caller_storage:\type_model\result_passing::direct);
 return new \type_model\runtime_callable($provider,$id,$name,$ns,$signature,$abi,$mode===24?null:($mode===23?\type_model\language_binding::byte_literal:\type_model\language_binding::echo_value),$mode===26,$mode===25?null:\type_model\conversion_purpose::explicit_cast);
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $left=$case['kind']==='reference'?ref($case['left']):make($case['left']);
 $right=$case['kind']==='reference'?ref($case['right']):make($case['right']);
 $out[]=($left == $right)===$case['equal'];
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
