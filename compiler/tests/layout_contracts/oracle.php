<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach (['compile/step.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/resources.php','01_prepare_inputs/load_runtime/handlers/package_types.php','05_generate_code/prepare_backend/data/configuration.php','05_generate_code/prepare_backend/data/layout.php'] as $file) { require $base.$file; }
class Retained_Types { use \load_runtime\Package_Syntax; use \load_runtime\Resource_Import; use \load_runtime\Package_Types { types as public import; } }
$integer=new \type_model\named_type_definition('Int','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(64)),new \type_model\lifetime_contract(\type_model\copy_kind::value,\type_model\cleanup_kind::none),true);
$configuration=new \prepare_backend\backend_configuration('b','t','d','','','a','r');
$lineage=new \type_model\type_lineage();
$member=new \type_model\type_member(1,'field',true);
$dependency=new \prepare_backend\layout_dependency(1,$integer,[$member],[]);
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $accepted=true;
 try {$layout=new \prepare_backend\storage_layout($integer,$configuration,array_fill(0,$case['fields'],$member),'i64',$case['size'],$case['alignment'],$case['offsets'],$lineage,$dependency);}
 catch(\LogicException $error) {$accepted=false;}
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
