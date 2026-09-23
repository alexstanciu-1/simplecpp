<?php
$root=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/representations.php',
 '04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php',
 '01_prepare_inputs/load_runtime/handlers/type_definitions.php','01_prepare_inputs/load_runtime/utilities/catalog_syntax.php',
 '04_analyze/type_model/data/store.php','04_analyze/resolve_types/utilities/type_cache.php',
 '04_analyze/resolve_types/data/result.php','04_analyze/check_bodies/data/structures.php','04_analyze/check_bodies/utilities/conversions.php'] as $file)require $root.$file;
$catalog=\load_runtime\Catalog_Syntax::parse(file_get_contents($argv[2]));
$types=new \type_model\Type_Store(new \type_model\type_context('config','providers','target'));
foreach($catalog->definitions() as $definition){\resolve_types\Type_Cache::materialize($types,$definition);}
$context=(new ReflectionClass(\resolve_types\Type_Resolution::class))->newInstanceWithoutConstructor();
(new ReflectionProperty($context,'types'))->setValue($context,$types);
$bindings=[];
foreach([['explicit_cast','int32','uint8',10000],['text','int32','uint8',10001],['implicit','uint32','uint8',10002],['condition','int32','uint8',10003]] as [$purpose,$source,$destination,$id]){
    $bindings[$purpose][$types->find_type($source,'')][$types->find_type($destination,'')]=$id;
}
(new ReflectionProperty($context,'conversion_calls'))->setValue($context,$bindings);
$purposes=\type_model\conversion_purpose::cases();
$forms=['identity'=>1,'primitive'=>2,'provider_call'=>3];
$cases=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
foreach($cases as $case){
    $request=new \check_bodies\conversion_request($types->find_type($case['source'],''),$types->find_type($case['destination'],''),$purposes[$case['purpose']]);
    $selection=\check_bodies\Conversion_Resolver::resolve($context,$request);
    $actual=$selection===null?[0,0,0]:[$forms[$selection->form->value],$selection->primitive===null?0:1,$selection->callable_id];
    if($actual!==[$case['form'],$case['primitive'],$case['target']]){throw new \LogicException('Retained conversion differs');}
}
echo count($cases)," retained conversion cases passed\n";
