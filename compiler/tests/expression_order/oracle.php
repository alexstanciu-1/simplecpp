<?php
// Isolate the retained traversal over its real original row classes. Source ownership
// is irrelevant to these queries; initialize only the fields they read via reflection.
$root=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/representations.php',
 '04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php',
 '01_prepare_inputs/load_runtime/handlers/type_definitions.php','01_prepare_inputs/load_runtime/utilities/catalog_syntax.php',
 '04_analyze/type_model/result_contracts.php','04_analyze/type_model/data/store.php','04_analyze/resolve_types/utilities/type_cache.php',
 '04_analyze/check_bodies/data/structures.php','04_analyze/check_bodies/data/result.php','04_analyze/check_bodies/utilities/evaluation.php'] as $file)require $root.$file;
$catalog=\load_runtime\Catalog_Syntax::parse(file_get_contents($argv[2]));
$types=new \type_model\Type_Store(new \type_model\type_context('config','providers','target'));
foreach($catalog->definitions() as $definition){\resolve_types\Type_Cache::materialize($types,$definition);}
$integer=$types->find_type('int32','');$byte=$types->find_type('uint8','');$void=$types->find_type('void','');
$signatures=[];
for($target=500;$target<505;$target++){
    $parameters=$target<503?[$integer]:[];if($target===501){$parameters[]=$integer;}
    $result=in_array($target,[502,504],true)?$void:$integer;
    $rid=$types->intern_signature($result,$parameters);
    $signatures[$target]=new \check_bodies\signature_dependency($target,$rid,$types->representation_by_id($rid));
}
$dependencies=[];foreach([$integer,$byte,$void] as $id){$dependencies[$id]=$types->type_by_id($id);}
$cases=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
foreach($cases as $case){
    $values=[];
    foreach($case['values'] as [$kind,$a,$b]){
        $tag=\check_bodies\value_kind::integer_literal;$type=$integer;$payload='1';
        if($kind==='convert'){$tag=\check_bodies\value_kind::conversion;$payload=new \check_bodies\conversion_value($a,\check_bodies\conversion_kind::integer_widen);}
        elseif($kind==='operation'){$tag=\check_bodies\value_kind::operation;$payload=new \check_bodies\operation_value($a,$b,new \type_model\operation_contract('addition',[$integer,$integer],$integer,new \type_model\implementation_binding(\type_model\implementation_kind::native_operation,'compiler.integer','add_wrap')));}
        elseif($kind==='call'||$kind==='wrong_call'){$tag=\check_bodies\value_kind::call_result;$payload=$a;if($kind==='wrong_call'){$type=$byte;}}
        elseif($kind==='place'){$tag=\check_bodies\value_kind::local_read;$payload=new \check_bodies\place(1,[
            new \check_bodies\place_projection(\check_bodies\projection_kind::field,0,$integer),
            new \check_bodies\place_projection(\check_bodies\projection_kind::index,$a,$integer),
            new \check_bodies\place_projection(\check_bodies\projection_kind::element,$b,$integer)]);}
        $values[]=new \check_bodies\typed_value(1,$type,$tag,$payload);
    }
    $calls=[];foreach($case['calls'] as [$target,$result,$start,$count]){$calls[]=new \check_bodies\typed_call(1,$target,$result,$start,$count);}
    $arguments=[];foreach($case['args'] as $id){$arguments[]=new \check_bodies\typed_argument($id,$integer);}
    $body=(new ReflectionClass(\check_bodies\Checked_Body::class))->newInstanceWithoutConstructor();
    foreach(['values'=>$values,'calls'=>$calls,'arguments'=>$arguments,'signature_dependencies'=>$signatures,'type_dependencies'=>$dependencies] as $field=>$value){
        (new ReflectionProperty($body,$field))->setValue($body,$value);
    }
    $actual=[];$failed=false;
    try{foreach(\check_bodies\Expression_Order::steps($body,$case['root'],$case['start'],$case['limit']) as $step){$actual[]=[$step->value_id,$step->call_id];}}
    catch(\LogicException $error){$failed=true;}
    if($failed!==$case['error']||$actual!==$case['expected']){throw new \LogicException('Retained evaluation differs: '.$case['name']);}
}
echo count($cases)," retained evaluation traces passed\n";
