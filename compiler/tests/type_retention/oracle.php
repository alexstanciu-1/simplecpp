<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/representations.php','04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/resources.php','04_analyze/type_model/data/records.php','04_analyze/type_model/data/catalog.php','04_analyze/type_model/data/type_references.php','01_prepare_inputs/load_runtime/data/runtime.php','01_prepare_inputs/load_runtime/data/family_preparation.php','01_prepare_inputs/load_runtime/data/package.php','01_prepare_inputs/load_runtime/handlers/package_types.php'] as $file) {require $base.$file;}
class Retained {use \load_runtime\Package_Types {retain_bound_types as public retain;}}
function row(int $mode,string $id): \load_runtime\runtime_type {
 $bits=$mode===1?32:64;$size=$mode===2?16:8;$align=$mode===3?4:8;$signed=$mode!==4;
 $life=new \type_model\lifetime_contract(\type_model\copy_kind::value,\type_model\cleanup_kind::none);
 $def=new \type_model\named_type_definition($id==='catalog'?'T':$id,'',new \type_model\representation_record(\type_model\representation_kind::integer,new \type_model\integer_representation($bits)),$life,$signed,comparison:$mode===6?\type_model\integer_comparison::ordered:null);
 if($mode===7) {$id='other';}
 if($mode===8||$mode===9) {return new \load_runtime\runtime_type($id,new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::record,8,8),null,null,null,new \type_model\record_declaration('T','',[new \type_model\field_declaration('field',$def,$mode===9)],true,\type_model\record_layout_policy::target));}
 if($mode===10) {return new \load_runtime\runtime_type($id,new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::void_type,0,1),null,null,new \type_model\named_type_definition('Void','',new \type_model\representation_record(\type_model\representation_kind::void_type,null),null));}
 return new \load_runtime\runtime_type($id,new \load_runtime\runtime_storage(\load_runtime\runtime_storage_kind::integer,$size,$align),$bits,$signed,$mode===5?null:$def);
}
$integer=row(0,'catalog')->language_type;$catalog=new \type_model\Type_Catalog('p','key','language_values',[$integer],$integer,$integer);$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 if($case['kind']==='source') {continue;}
 if($case['kind']==='compare') {$out[]=(row($case['left'],'T')==row($case['right'],'T'))===$case['equal'];continue;}
 $mode=$case['binding'];$old=row(0,'T');$current=row($case['mode'],'T');$old_first=row(0,'U');$new_first=row(0,'U');
 $old_types=['U'=>$old_first];if($mode!==5) {$old_types['T']=$old;}
 $old_names=['U'=>new \type_model\named_type_reference('U','')];if($mode!==3) {$old_names['T']=new \type_model\named_type_reference('T','');}
 $names=['U'=>new \type_model\named_type_reference('U','')];if($mode!==2) {$names['T']=new \type_model\named_type_reference($mode===1?'Changed':'T','');}
 $bindings=new \load_runtime\package_bindings(types:$names);
 $package=new \load_runtime\Runtime_Package('p','/p','t','d','clang',[],$old_types,[],[],[],'manifest',$catalog,$catalog,bindings:$mode===4?null:new \load_runtime\package_bindings(types:$old_names));
 $types=['U'=>$new_first,'T'=>$current];$accepted=true;$matches=true;
 try {$result=Retained::retain($types,$bindings,$package);$matches=$result['T']===($mode===0?$old:$current)&&$result['U']===($mode===4?$new_first:$old_first);}
 catch(\RuntimeException $error) {$accepted=false;}
 $out[]=$accepted===$case['accept']&&$matches&&$types['U']===$new_first&&$types['T']===$current&&$package->type_for('U')===$old_first;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
