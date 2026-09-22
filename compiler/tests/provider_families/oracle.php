<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/';
foreach (['data/type_references.php','data/semantic_calls.php','data/generic.php','data/lifecycle.php','data/families.php','family_contracts.php'] as $file) { require $base.$file; }
function reference(array $row): \type_model\type_reference {
    return match ($row[0]) {
        'named'=>new \type_model\named_type_reference($row[1],$row[2]),
        'provider'=>new \type_model\provider_type_reference($row[1],$row[2]),
        'parameter'=>new \type_model\parameter_type_reference($row[1],$row[2]),
        'family'=>new \type_model\family_type_reference($row[1],array_map('reference',$row[2])),
    };
}
$rows=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);$out=[];
foreach ($rows as $row) {
    $parameters=array_map(fn(string $name)=>new \type_model\family_parameter($name),$row['parameters']);$operations=[];
    foreach ($row['operations'] as $op) {
        $args=array_map(fn(array $p)=>new \type_model\semantic_parameter(reference($p[0]),\type_model\argument_passing::from($p[1])),$op['parameters']);
        $result=new \type_model\semantic_result(reference($op['result'][0]),\type_model\result_production::from($op['result'][1]));
        $requirements=array_map(fn(array $r)=>new \type_model\capability_requirement($r[0],\type_model\lifecycle_operation_kind::from($r[1])),$op['requirements']);
        $effects=array_map(fn(array $e)=>new \type_model\element_effect(\type_model\element_effect_kind::from($e[0]),$e[1],$e[2]),$op['effects']);
        $operations[$op['id']]=new \type_model\family_operation($op['id'],new \type_model\semantic_signature($args,$result),$requirements,$effects,$op['receiver'],$op['exposure']===null?null:reference($op['exposure']));
    }
    $family=new \type_model\family_definition($row['provider'],$row['id'],$parameters,$operations,$row['lifecycle'],$row['language']===null?null:reference($row['language']));
    $accepted=true;
    try { \type_model\Family_Contracts::validate($family); } catch (\RuntimeException $error) { $accepted=false; }
    $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
