<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/resources.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/records.php','04_analyze/type_model/data/catalog.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/handlers/package_syntax.php','01_prepare_inputs/load_runtime/handlers/records.php'] as $file) { require $base.$file; }
class Retained_Records {
 use \load_runtime\Package_Syntax;
 use \load_runtime\Record_Import { records as public import; }
}
set_error_handler(static function(int $level,string $message): never { throw new \ErrorException($message,0,$level); });
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $fixture) {
 $mode=$fixture['scalar_mode'];
 $word=new \type_model\named_type_definition('word','',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation(32)),new \type_model\lifetime_contract(\type_model\copy_kind::value,\type_model\cleanup_kind::none),signed:true,struct_field:$mode!==1);
 $definitions=[$word];$records=[];
 if($fixture['collision']===1) { $definitions[]=new \type_model\named_type_definition('Pair','',new \type_model\representation_record(\type_model\representation_kind::void_type,null),null); }
 if($fixture['collision']===2) { $records[]=new \type_model\record_declaration('Pair','',[],true,\type_model\record_layout_policy::target); }
 $catalog=new \type_model\Type_Catalog('provider','content','language_values',$definitions,$word,$word,$records);
 $types=['word'=>new \load_runtime\runtime_type('word',new \load_runtime\runtime_storage($mode===3?\load_runtime\runtime_storage_kind::address:\load_runtime\runtime_storage_kind::integer,$mode===4?32:4,4),32,true,$mode===2?null:$word),
 'row'=>new \load_runtime\runtime_type('row',new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::record,$fixture['size'],$fixture['alignment']),null,null,null)];
 $accepted=true;
 try { $batch=Retained_Records::import($fixture['rows'],$types,$catalog,$fixture['target']); }
 catch(\Throwable $error) { $accepted=false; }
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
