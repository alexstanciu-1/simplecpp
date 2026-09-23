<?php
declare(strict_types=1);
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/resource_states.php';
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/data/ownership.php';
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case) {
    try { $row=new \analyze_lifetimes\resource_transition($case['required'],$case['result'],$case['mutates'],$case['accessed']); $out[]=[$row->required,$row->result,$row->mutates,$row->accessed]; }
    catch(\InvalidArgumentException $error) { $out[]=false; }
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
