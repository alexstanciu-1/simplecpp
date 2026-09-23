<?php
declare(strict_types=1);
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/resource_states.php';
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case) {
    $method=$case['kind'];
    $out[]=$method==='deterministic' ? \analyze_lifetimes\Resource_States::deterministic($case['state']) : \analyze_lifetimes\Resource_States::$method($case['state'],$case['operand']);
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
