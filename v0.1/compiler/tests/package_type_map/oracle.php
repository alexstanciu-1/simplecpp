<?php
declare(strict_types=1);
require dirname(__DIR__,3).'/tools/php_portability/runtime/bootstrap.php';
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach (['compile/step.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php','04_analyze/type_model/data/resources.php','04_analyze/type_model/data/generic.php','04_analyze/type_model/generic_contracts.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/resources.php','01_prepare_inputs/load_runtime/handlers/package_types.php','01_prepare_inputs/load_runtime/handlers/lifecycle.php','04_analyze/type_model/data/type_references.php','01_prepare_inputs/load_runtime/data/family_preparation.php','compile/data/native_project.php','05_generate_code/prepare_backend/data/configuration.php','05_generate_code/prepare_backend/data/layout.php','04_analyze/resolve_types/data/export_identity.php','05_generate_code/prepare_backend/data/source_exports.php','01_prepare_inputs/load_runtime/project_import.php'] as $file) { require $base.$file; }
class Retained_Types { use \load_runtime\Package_Syntax; use \load_runtime\Resource_Import; use \load_runtime\Lifecycle_Import; use \load_runtime\Package_Types { types as public import; } }
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
$language=new \type_model\named_type_definition('Owned','provider_ns',new \type_model\representation_record(\type_model\representation_kind::opaque_inline,new \type_model\opaque_representation(8,8)),$life);
$native=new \load_runtime\runtime_type('foreign',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::opaque_inline,8,8),null,null,$language);
$owner=new \load_runtime\runtime_type_import('accepted','foreign',$native,'t','d');
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $types=[]; foreach($case['types'] as $id=>$name) { $types[$id]=$name==='@parameter'?new \type_model\parameter_type_reference('owner',0):new \type_model\named_type_reference($name,''); }
 $sources=array_fill_keys($case['sources'],$source_export);$imports=array_fill_keys($case['imports'],$owner);
 $accepted=true;
 try { $result=Retained_Types::import($case['rows'],$catalog,$case['operations'],'provider',new \load_runtime\package_bindings($types,[],$imports,$sources)); }
 catch (\RuntimeException $error) { $accepted=false; }
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
