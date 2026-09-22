<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/resolve_types/data/export_identity.php';
$out=[];
foreach (json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 if ($case['mode']!=='valid') {continue;}
 $text=$case['text'];$language=['language',$text,'ns','I'];$provider=['provider',$text,'id'];
 $arguments=[['type',$language],['constant',$provider,$case['value']]];
 $family=['family',$text,'Vec',$arguments];$source=['source',$text,[$text,'ns','S'],$arguments];$array=$source;
 for ($level=0;$level<$case['depth'];$level++) {$array=['array',$array,(string)$level];}
 $keys=[];
 foreach ([$language,$provider,$family,$source,$array] as $parts) {$key=new \resolve_types\export_type_identity($parts,$parts[0]==='source');$keys[]=$key->key;}
 $out[]=$keys;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
