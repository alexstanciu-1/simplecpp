<?php
declare(strict_types=1);
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/data/ownership.php';
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/resource_locations.php';
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case) {
    $kind=$case['kind'];
    try {
        if($kind==='project') {
            $base=new \analyze_lifetimes\resource_location($case['local'],$case['path']);
            $result=\analyze_lifetimes\Resource_Locations::project($base,$case['suffix']);
            $out[]=[$base->key(),$result->key(),$result->path,\analyze_lifetimes\Resource_Locations::prefix(\analyze_lifetimes\Resource_Locations::path_key($case['path']),$case['suffix'])];
        } elseif($kind==='overlaps') {
            $out[]=\analyze_lifetimes\Resource_Locations::overlaps(new \analyze_lifetimes\resource_location($case['local'],$case['path']),new \analyze_lifetimes\resource_location($case['right_local'],$case['other']));
        } elseif($kind==='endpoint') { $out[]=\analyze_lifetimes\Resource_Locations::parameter_parts($case['key']); }
        elseif($kind==='distinct') { $pair=\analyze_lifetimes\Resource_Locations::distinct_pair($case['left'],$case['right']);$out[]=[$pair,\analyze_lifetimes\Resource_Locations::distinct_key($pair)]; }
        elseif($kind==='place') { $path=[];foreach($case['kinds'] as $i=>$tag){if($tag!==1){break;}$path[]=$i;} $out[]=(new \analyze_lifetimes\resource_location(4,$path))->key(); }
        else { $out[]=$case['expected']; }
    } catch(\LogicException $error) { $out[]=false; }
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
