<?php
require dirname(__DIR__, 2) . '/reference/pre-rewrite/src/04_analyze/check_bodies/data/structures.php';
$kinds=[1=>\check_bodies\projection_kind::field,2=>\check_bodies\projection_kind::index,3=>\check_bodies\projection_kind::element];
$cases=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
foreach($cases as $case){
    $failed=false;
    try{
        $path=[];
        foreach($case['rows'] as $row){$path[]=new \check_bodies\place_projection($kinds[$row[0]],$row[1],$row[2],$row[3]);}
        $place=new \check_bodies\place($case['local'],$path);
        if($place->allocation_backed()!==$case['allocation']){throw new \LogicException('Allocation differs');}
        $indices=[];
        foreach($place->indices() as $projection){$indices[]=array_search($projection,$path,true);}
        if($indices!==$case['indices']){throw new \LogicException('Index order differs');}
    }catch(\InvalidArgumentException $error){$failed=true;}
    if($failed!==$case['error']){throw new \LogicException('Rejection differs');}
}
echo count($cases)," retained location cases passed\n";
