<?php
declare(strict_types=1);
require dirname(__DIR__,3).'/tools/php_portability/runtime/bootstrap.php';
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach (['compile/step.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php','04_analyze/type_model/data/resources.php','04_analyze/type_model/data/generic.php','04_analyze/type_model/generic_contracts.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/resources.php','01_prepare_inputs/load_runtime/handlers/package_types.php','01_prepare_inputs/load_runtime/handlers/lifecycle.php','04_analyze/type_model/data/type_references.php','01_prepare_inputs/load_runtime/data/family_preparation.php','compile/data/native_project.php','05_generate_code/prepare_backend/data/configuration.php','05_generate_code/prepare_backend/data/layout.php','04_analyze/resolve_types/data/export_identity.php','05_generate_code/prepare_backend/data/source_exports.php','01_prepare_inputs/load_runtime/project_import.php'] as $file) { require $base.$file; }
$life=new \type_model\lifetime_contract(\type_model\copy_kind::value,\type_model\cleanup_kind::none,assignment:\type_model\assignment_kind::value);
$integer=new \type_model\named_type_definition('Int','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(64)),$life,true);
$catalog=new \type_model\Type_Catalog('p','key','language_values',[$integer],$integer,$integer);
$definition=new \type_model\named_type_definition('R','',new \type_model\representation_record(\type_model\representation_kind::structure,new \type_model\structure_representation(0,0)),$life);
$config=new \prepare_backend\backend_configuration('b','t','d','','','a','r');
$dependency=new \prepare_backend\layout_dependency(1,$definition,[],[]);
$layout=new \prepare_backend\storage_layout($definition,$config,[],'{}',8,8,[],new \type_model\type_lineage(),$dependency);
$identity=new \resolve_types\export_type_identity(['source','p',['r.phs','','R'],[]],true);
$task=new \prepare_backend\source_export_task(new \compile\native_project('p','/src','/out'),$identity,$layout,[1=>$identity],[]);
$source_export=new \prepare_backend\source_type_export($task,[]);
require $base.'01_prepare_inputs/load_runtime/data/project.php';
require $base.'01_prepare_inputs/load_runtime/data/package.php';
function make_life(int $mask,string $prefix): \type_model\lifetime_contract {
 $names=['destroy','copy_construct','move_construct','copy_assign','default_construct'];$ops=[];
 foreach($names as $bit=>$name) { $ops[$name]=($mask & (1<<$bit))?new \type_model\runtime_lifecycle_operation('p',$prefix.$name,$prefix.$name,'ccc',\type_model\lifecycle_operation_kind::from($name)):null; }
 return new \type_model\lifetime_contract($ops['copy_construct']?\type_model\copy_kind::construct:\type_model\copy_kind::unavailable,$ops['destroy']?\type_model\cleanup_kind::destroy:\type_model\cleanup_kind::none,$ops['destroy'],$ops['copy_construct'],$ops['default_construct']?\type_model\construction_kind::construct:\type_model\construction_kind::unavailable,$ops['default_construct'],$ops['copy_assign']?\type_model\assignment_kind::call:\type_model\assignment_kind::unavailable,$ops['copy_assign'],$ops['move_construct']?\type_model\expiring_construction::construct:\type_model\expiring_construction::unavailable,$ops['move_construct']);
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $first_life=make_life($case['mask'],'first');$second_life=make_life($case['mask'],'second');
 $first_definition=new \type_model\named_type_definition('I','',$integer->representation,$first_life,true);
 $second_definition=new \type_model\named_type_definition('J','',$integer->representation,$second_life,true);
 $storage=new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::integer,8,8);
 $first=new \load_runtime\runtime_type('first',$storage,64,true,$first_definition);
 $second=new \load_runtime\runtime_type('second',$storage,64,true,$second_definition);
 $hidden=new \load_runtime\runtime_type('hidden',$storage,64,true,null);
 $mode=$case['binding'];
 if($mode===3) {$second=new \load_runtime\runtime_type('second',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::record,8,8),null,null,$source_export->task->layout->definition);}
 $bindings=$mode===4?null:new \load_runtime\package_bindings(types:$mode===2?['second'=>new \type_model\named_type_reference('I','')]:[],imports:$mode===1?['second'=>new \load_runtime\runtime_type_import('p','second',$second,'t','d')]:[],sources:$mode===3?['second'=>$source_export]:[]);
 $package=new \load_runtime\Runtime_Package('p','/provider','t','d','clang',['-lm'],['second'=>$second,'hidden'=>$hidden,'first'=>$first],[],['ordinary'=>'runtime.bc','thin_lto'=>'runtime.thin.bc'],['/protected'],'manifest',$catalog,$catalog,bindings:$bindings);
 $expected=[];
 foreach((($mode===1)||($mode===3))?['first']:['second','first'] as $prefix) {
  foreach(['destroy','copy_construct','move_construct','copy_assign','default_construct'] as $bit=>$name) { if($case['mask'] & (1<<$bit)) {$expected[]=$prefix.$name;} }
 }
 $actual=array_map(static fn($op)=>$op->id,$package->lifecycle_operations());
 $out[]=$actual===$expected && $package->type_for('first')===$first && $package->storage_for('second')===$second->storage;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
