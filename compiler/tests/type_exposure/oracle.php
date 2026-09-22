<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach (['compile/step.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php','04_analyze/type_model/data/resources.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/resources.php','01_prepare_inputs/load_runtime/handlers/package_types.php','01_prepare_inputs/load_runtime/handlers/lifecycle.php','04_analyze/type_model/data/type_references.php','01_prepare_inputs/load_runtime/data/family_preparation.php'] as $file) { require $base.$file; }
class Retained_Types { use \load_runtime\Package_Syntax; use \load_runtime\Resource_Import; use \load_runtime\Lifecycle_Import; use \load_runtime\Package_Types { types as public import; } }
$integer=new \type_model\named_type_definition('Int','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(64)),new \type_model\lifetime_contract(\type_model\copy_kind::value,\type_model\cleanup_kind::none),true);
$unsigned_value=new \type_model\named_type_definition('UInt','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(64)),new \type_model\lifetime_contract(\type_model\copy_kind::value,\type_model\cleanup_kind::none),false);
$void_type=new \type_model\named_type_definition('Void','',new \type_model\representation_record(\type_model\representation_kind::void_type,null),null);
$catalog=new \type_model\Type_Catalog('p','key','language_values',[$integer,$unsigned_value,$void_type],$integer,$integer);
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $accepted=true;
 try { $types=Retained_Types::import([$case['row']],$catalog,[],'provider',new \load_runtime\package_bindings(types:['T'=>new \type_model\named_type_reference($case['name'],$case['namespace'])])); }
 catch (\RuntimeException $error) { $accepted=false; }
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
