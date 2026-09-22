<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
require $base.'04_analyze/type_model/data/lifecycle.php';
require $base.'05_generate_code/prepare_backend/data/source_exports.php';
$role_names=[1=>'default_construct',2=>'destroy',3=>'copy_construct',4=>'move_construct',5=>'copy_assign',6=>'move_assign'];
$states=['available','forbidden','unsupported'];$out=[];
foreach (json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $role=\prepare_backend\source_export_role::from($role_names[$case['role']]);
 if ($case['mode']==='semantics') {
  $value=$role->semantics();$kind=$role->implemented_kind();
  $out[]=$value===['destination_before'=>$case['before'],'destination_after'=>$case['after'],'source_access'=>$case['access'],'source_after'=>$case['source_after'],'aliasing'=>$case['aliasing'],'payload_escape'=>'call_scoped','failure'=>'terminate','unwind'=>'none','resources'=>'selected_field_contracts'] && ($kind===null?$case['kind']===0:$kind->value===$role_names[$case['kind']]);
  continue;
 }
 $operation=null;
 if ($case['kind']!==0) {
  $kind=\type_model\lifecycle_operation_kind::from($role_names[$case['kind']]);
  $operation=$case['imported']?new \type_model\runtime_lifecycle_operation('p','op','impl','ccc',$kind):new \type_model\source_lifecycle_operation(1,'impl',$kind,[]);
 }
 $accepted=true;
 try {$capability=new \prepare_backend\source_export_capability($role,\prepare_backend\source_export_availability::from($states[$case['state']]),'reason',$operation);}
 catch (\InvalidArgumentException|\TypeError $error) {$accepted=false;}
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
