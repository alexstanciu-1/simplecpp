<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/data/';
foreach(['representations.php','lifecycle.php','definitions.php','resources.php','records.php','callables.php','storage.php'] as $file) {require $base.$file;}
function integer(bool $copy=true): \type_model\named_type_definition {
 return new \type_model\named_type_definition('T','ns',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(64)),new \type_model\lifetime_contract($copy?\type_model\copy_kind::value:\type_model\copy_kind::unavailable,\type_model\cleanup_kind::none),true);
}
$plain=new \type_model\lifetime_contract(\type_model\copy_kind::unavailable,\type_model\cleanup_kind::none);
$shape=new \type_model\representation_record(\type_model\representation_kind::structure,new \type_model\structure_representation(0,1));
$a=new \type_model\named_type_definition('R','ns',$shape,$plain);
$b=new \type_model\named_type_definition('R','ns',$shape,$plain,resource:null,resource_paths:[]);
$paths_a=new \type_model\named_type_definition('R','ns',$shape,$plain,resource_paths:[[0],[1]]);
$paths_b=new \type_model\named_type_definition('R','ns',$shape,$plain,resource_paths:[[1],[0]]);
$descriptor=new \type_model\named_type_definition('D','ns',new \type_model\representation_record(\type_model\representation_kind::opaque_inline,new \type_model\opaque_representation(8,8)),$plain,resource:\type_model\resource_kind::allocation);
$void_type=new \type_model\named_type_definition('Void','',new \type_model\representation_record(\type_model\representation_kind::void_type,null),null);
$primitive=new \type_model\storage_primitive('link',new \type_model\runtime_borrow_abi(),[]);
$family_a=new \type_model\storage_family('p','id',$descriptor,integer(),$void_type,['allocate'=>$primitive,'release'=>$primitive],['allocate'=>'Allocate','release'=>'Release'],'Buffer','ns');
$family_b=new \type_model\storage_family('p','id',$descriptor,integer(),$void_type,['release'=>$primitive,'allocate'=>$primitive],['release'=>'Release','allocate'=>'Allocate'],'Buffer','ns');
$layout_a=new \type_model\native_record_layout('target','layout',8,8,[0]);
$layout_b=new \type_model\native_record_layout('other','layout',8,8,[0]);
$out=[integer()==integer(),$a==$b,$paths_a!=$paths_b,$family_a==$family_b,integer()!=integer(false),$layout_a!=$layout_b,new \type_model\element_storage($family_a,1,integer())!=new \type_model\element_storage($family_a,2,integer())];
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
