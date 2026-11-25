<?php
declare(strict_types=1);
// Execute the retained worker's actual private closure algorithm. Only its fixed
// type snapshot is initialized; this does not bypass or prove body-task acceptance.
$root=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/representations.php',
 '04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php',
 '01_prepare_inputs/load_runtime/handlers/type_definitions.php','01_prepare_inputs/load_runtime/utilities/catalog_syntax.php',
 '04_analyze/type_model/data/store.php','04_analyze/resolve_types/utilities/type_cache.php',
 '04_analyze/resolve_types/data/result.php','04_analyze/check_bodies/data/structures.php',
 '04_analyze/check_bodies/handlers/statements.php','04_analyze/check_bodies/handlers/control_statements.php',
 '04_analyze/check_bodies/handlers/expressions.php','04_analyze/check_bodies/handlers/places.php',
 '04_analyze/check_bodies/handlers/writes.php','04_analyze/check_bodies/body.php'] as $file)require $root.$file;
$catalog=\load_runtime\Catalog_Syntax::parse(file_get_contents($argv[1]));
foreach ([0,3,4096,-1] as $depth) {
    $types=new \type_model\Type_Store(new \type_model\type_context('config','providers','target'));
    $base=$catalog->find_type('int32','');
    $element=\resolve_types\Type_Cache::materialize($types,$base);$top=$element;
    for($i=0;$i<$depth;$i++) {
        $id=$types->declare_type('Array_'.$i,'proof');
        $shape=new \type_model\representation_record(\type_model\representation_kind::fixed_array,new \type_model\array_representation($top,2));
        $types->bind_definition($id,new \type_model\named_type_definition('Array_'.$i,'proof',$shape,$base->lifetime,struct_field:true));
        $top=$id;
    }
    if($depth===-1) {
        $top=$types->declare_type('Cycle','proof');
        $shape=new \type_model\representation_record(\type_model\representation_kind::fixed_array,new \type_model\array_representation($top,2));
        $types->bind_definition($top,new \type_model\named_type_definition('Cycle','proof',$shape,$base->lifetime,struct_field:true));
    }
    $snapshot=(new ReflectionClass(\resolve_types\Type_Resolution::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty($snapshot,'types'))->setValue($snapshot,$types);
    $worker=(new ReflectionClass(\check_bodies\Body_Worker::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty($worker,'types'))->setValue($worker,$snapshot);
    $retain=new ReflectionMethod($worker,'retain_type');$retain->invoke($worker,$top);$retain->invoke($worker,$top);
    $rows=(new ReflectionProperty($worker,'type_dependencies'))->getValue($worker);
    if(count($rows)!==($depth===-1?1:$depth+1)){throw new LogicException('Retained closure differs');}
    foreach($rows as $id=>$row){if($row!==$types->type_by_id($id)){throw new LogicException('Lost retained identity');}}
}
echo "4 retained closure scenarios passed\n";
