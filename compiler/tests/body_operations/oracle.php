<?php
$root=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/representations.php',
 '04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php',
 '01_prepare_inputs/load_runtime/handlers/type_definitions.php','01_prepare_inputs/load_runtime/utilities/catalog_syntax.php',
 '04_analyze/type_model/data/store.php','04_analyze/resolve_types/utilities/type_cache.php','04_analyze/check_bodies/utilities/operations.php'] as $file)require $root.$file;
$catalog=\load_runtime\Catalog_Syntax::parse(file_get_contents($argv[2]));
$types=new \type_model\Type_Store(new \type_model\type_context('config','providers','target'));
foreach($catalog->definitions() as $definition){\resolve_types\Type_Cache::materialize($types,$definition);}
$cases=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
foreach($cases as $row){
    $left=$types->find_type($row['left'],'');$right=$types->find_type($row['right'],'');$boolean=$row['boolean']?$types->find_type('bool',''):0;
    $contract=\check_bodies\Operation_Resolver::binary($types,$row['operation'],$left,$right,$boolean);
    if(($contract?->implementation->entry??'')!==$row['entry']){throw new \LogicException('Retained selector differs');}
    if($contract!==null){
        $result=$row['operation']==='less_than'?$boolean:$left;
        if($contract->result_type!==$result||$contract->operand_types!==[$left,$right]){throw new \LogicException('Retained identity differs');}
    }
}
echo count($cases)," retained operation cases passed\n";
