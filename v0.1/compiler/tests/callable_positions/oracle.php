<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/records.php','04_analyze/type_model/data/callables.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/callables.php'] as $file) { require $base.$file; }
class Retained_Positions {
 use \load_runtime\Package_Syntax;
 use \load_runtime\Callable_Import { call_result as public result; call_parameter as public parameter; }
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
function abi_text($abi): string {
 if($abi===null)return 'none';
 if($abi instanceof \type_model\runtime_integer_abi)return 'integer:'.$abi->bits.':'.match($abi->extension){\type_model\abi_extension::none=>0,\type_model\abi_extension::sign=>1,\type_model\abi_extension::zero=>2};
 if($abi instanceof \type_model\runtime_borrow_abi)return $abi->mutable?'mutable':'const';
 return 'span:'.$abi->length->bits;
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $fixture) {
 $actual='error';
 try {
  if($fixture['mode']===0) {
   [$definition,$passing,$abi,$next]=Retained_Positions::result($fixture['row'],$types,$fixture['positions']);
   $production=$passing===\type_model\result_passing::caller_storage?2:($definition->representation->kind===\type_model\representation_kind::void_type?0:1);
   $actual=$definition->name.':'.($passing===\type_model\result_passing::caller_storage?1:0).':'.$production.':'.abi_text($abi).':'.$next;
  } else {
   $offset=$fixture['offset'];[$definition,$abi]=Retained_Positions::parameter($fixture['row'],$types,$fixture['positions'],$offset);
   $mode=match($fixture['row']['passing']){'direct'=>0,'const_address'=>1,'mutable_address'=>2,'byte_span'=>3};
   $actual=$definition->name.':'.$mode.':'.abi_text($abi).':'.$offset;
  }
 } catch(\RuntimeException|\TypeError $error) { $actual='error'; }
 $out[]=$actual;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
