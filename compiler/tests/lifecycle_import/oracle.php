<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
require $base.'04_analyze/type_model/data/lifecycle.php';
require $base.'04_analyze/type_model/data/definitions.php';
require $base.'04_analyze/type_model/data/callables.php';
require $base.'01_prepare_inputs/load_runtime/handlers/package_syntax.php';
require $base.'01_prepare_inputs/load_runtime/handlers/lifecycle.php';
class Retained_Lifecycle {
 use \load_runtime\Package_Syntax;
 use \load_runtime\Lifecycle_Import { lifetime as public import_lifetime; default_constructor as public construct; lifecycle_operation as public operation; }
}
$names=['default_construct','destroy','copy_construct','move_construct','copy_assign'];
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $accepted=true;
 try {
  if($case['role']===0) { $result=Retained_Lifecycle::import_lifetime($case['type'],$case['operations'],'provider'); }
  elseif($case['role']===1) { $result=Retained_Lifecycle::construct($case['type'],$case['operations'],'provider'); }
  else { $result=Retained_Lifecycle::operation($case['type'],$case['operations'],'provider',\type_model\lifecycle_operation_kind::from($names[$case['role']-1])); }
 } catch (\RuntimeException|\InvalidArgumentException $error) { $accepted=false; }
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
