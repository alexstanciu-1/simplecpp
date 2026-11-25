<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/data/';
foreach (['representations.php','lifecycle.php','resources.php','definitions.php','storage.php'] as $file) { require $base.$file; }
use type_model as m;
$representation=new m\representation_record(m\representation_kind::opaque_inline,new m\opaque_representation(16,8));
$lifetime=new m\lifetime_contract(m\copy_kind::unavailable,m\cleanup_kind::none,construction:m\construction_kind::zero);
$descriptor=new m\named_type_definition('descriptor','',$representation,$lifetime,struct_field:true);
$counter=new m\named_type_definition('size','',new m\representation_record(m\representation_kind::integer,new m\integer_representation(64)),new m\lifetime_contract(m\copy_kind::value,m\cleanup_kind::none),signed:false);
$void=new m\named_type_definition('void','',new m\representation_record(m\representation_kind::void_type,null),null);
$family=new m\storage_family('provider','family',$descriptor,$counter,$void,[],[],'Buffer','collections');
$out=['effects'=>[],'definition_acceptance'=>[]];
foreach (m\storage_role::cases() as $role) {
 $function=new m\storage_function($family,$role,'custom_'.$role->value,'collections','provider','operation');
 $effect=$function->allocation_effect;$out['effects'][]=[$role->value,$effect->kind->value,$effect->owner,$effect->destination];
}
$storage=new m\element_storage($family,7,$counter);
for ($case=0;$case<5;$case++) {
 $shape=$representation;$life=$lifetime;$resource=m\resource_kind::allocation;
 if ($case===1) { $resource=null; }
 if ($case===2) { $shape=new m\representation_record(m\representation_kind::opaque_inline,new m\opaque_representation(16,8)); }
 if ($case===3) { $life=new m\lifetime_contract(m\copy_kind::unavailable,m\cleanup_kind::none,construction:m\construction_kind::zero); }
 $accepted=true;
 try { $definition=new m\named_type_definition('Buffer_size','',$shape,$life,struct_field:true,resource:$resource,element_storage:$case===4?null:$storage); }
 catch (\InvalidArgumentException $error) { $accepted=false; }
 $out['definition_acceptance'][]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
