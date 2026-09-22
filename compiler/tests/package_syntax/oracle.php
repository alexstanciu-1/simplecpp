<?php
declare(strict_types=1);
$migrated=class_exists('load_runtime\\Package_Syntax');
if (!$migrated) {
 $base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
 require $base.'04_analyze/type_model/data/callables.php';
 require $base.'01_prepare_inputs/load_runtime/data/runtime.php';
 require $base.'01_prepare_inputs/load_runtime/handlers/package_syntax.php';
 class Retained_Syntax { use \load_runtime\Package_Syntax { integer_abi as public integer; address_abi as public address; } }
}
$kind=$migrated ? \load_runtime\RUNTIME_STORAGE_INTEGER : \load_runtime\runtime_storage_kind::integer;
$type=new \load_runtime\Runtime_Type('signed32',new \load_runtime\Runtime_Storage($kind,4,4),32,true,null);
$attributes=['','noundef','signext','zeroext','signext signext','signext zeroext','noalias','noundef noundef'];
for ($byte=0;$byte<128;$byte++) {
 $c=chr($byte);
 $attributes[]=$c.'signext'.$c;
 $attributes[]='noundef'.$c.'zeroext';
 $attributes[]='zeroext'.$c.'noundef';
 $attributes[]=$c.'noundef'.$c;
}
$out=[];
foreach ($attributes as $attribute) {
 $integer=null;$address=true;
 try {
  if ($migrated) {
   $abi=\load_runtime\Package_Syntax::integer_abi(json_read('"i32"'),json_read(json_quote($attribute)),$type);
   $integer=\type_model\Callable_Modes::extension_name($abi->extension);
  } else { $integer=Retained_Syntax::integer('i32',$attribute,$type)->extension->value; }
 } catch (\RuntimeException $e) {}
 try {
  if ($migrated) { \load_runtime\Package_Syntax::address_abi(json_read(json_encode(['type'=>'ptr','attributes'=>$attribute],JSON_THROW_ON_ERROR))); }
  else { Retained_Syntax::address(['type'=>'ptr','attributes'=>$attribute]); }
 } catch (\RuntimeException $e) { $address=false; }
 $out[]=[$integer,$address];
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
