<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/callables.php','04_analyze/type_model/data/storage.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/storage.php'] as $file) { require $base.$file; }
class Retained_Storage { use \load_runtime\Package_Syntax; use \load_runtime\Storage_Import { storage_families as public import; } }
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $fixture) {
 $mode=$fixture['descriptor_mode'];
 $constructor=$mode===1?null:new \type_model\runtime_lifecycle_operation('provider','ctor','ctor','ccc',\type_model\lifecycle_operation_kind::default_construct);
 $destructor=$mode===3?new \type_model\runtime_lifecycle_operation('provider','dtor','dtor','ccc',\type_model\lifecycle_operation_kind::destroy):null;
 $life=new \type_model\lifetime_contract($mode===2?\type_model\copy_kind::value:\type_model\copy_kind::unavailable,$mode===3?\type_model\cleanup_kind::destroy:\type_model\cleanup_kind::none,destructor:$destructor,construction:$mode===1?\type_model\construction_kind::unavailable:\type_model\construction_kind::construct,default_constructor:$constructor);
 $descriptor=new \type_model\named_type_definition('Storage',$mode===4?'ns':'',new \type_model\representation_record(\type_model\representation_kind::opaque_inline,new \type_model\opaque_representation(16,8)),$life);
 $counter=new \type_model\named_type_definition('Counter','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(64)),new \type_model\lifetime_contract(\type_model\copy_kind::unavailable,\type_model\cleanup_kind::none),signed:true);
 $void=new \type_model\named_type_definition('Void','',new \type_model\representation_record(\type_model\representation_kind::void_type,null),null);
 $types=['descriptor'=>new \load_runtime\runtime_type('descriptor',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::opaque_inline,16,8),null,null,$descriptor),
 'counter'=>new \load_runtime\runtime_type('counter',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::integer,8,8),64,$fixture['counter_mode']!==1,$fixture['counter_mode']===2?null:$counter),
 'other_counter'=>new \load_runtime\runtime_type('other_counter',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::integer,8,8),64,true,$counter),
 'address'=>new \load_runtime\runtime_type('address',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::address,8,8),null,null,null)];
 if($fixture['void_mode']===0)$types['void']=new \load_runtime\runtime_type('void',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::void_type,0,1),null,null,$void);
 $accepted=true;
 try { $families=Retained_Storage::import($fixture['rows'],$types,$fixture['operations'],'provider'); }
 catch(\RuntimeException $error) { $accepted=false; }
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
