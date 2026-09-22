<?php
declare(strict_types=1);
require __DIR__ . '/../../reference/pre-rewrite/src/04_analyze/resolve_symbols/data/declarations.php';
use resolve_symbols\name_role as R;
use resolve_symbols\reference_kind as K;
$roles = [1=>R::type,2=>R::value,3=>R::type_family];
$kinds = [1=>K::source_type,4=>K::template_type,5=>K::template_parameter,6=>K::project_constant,7=>K::local_constant];
$cases = [[1,1,1,1],[1,3,4,2],[1,1,5,0],[1,2,5,0],[1,2,6,1],[1,2,7,1],
          [0,1,1,1],[1,1,1,0],[1,2,1,1],[1,1,4,1],[1,3,5,0],[1,1,5,-1],[1,1,6,1],[1,1,7,1],[1,2,6,0]];
$out=[];
foreach ($cases as [$node,$role,$kind,$target]) {
    try { new \resolve_symbols\name_binding($node,$roles[$role],$kinds[$kind],$target);$out[]=true; }
    catch (\LogicException $e) { $out[]=false; }
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
