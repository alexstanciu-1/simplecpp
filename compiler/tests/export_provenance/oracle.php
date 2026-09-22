<?php
declare(strict_types=1);
require dirname(__DIR__,3).'/tools/php_portability/runtime/bootstrap.php';
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
require $base.'compile/data/native_project.php';
require $base.'05_generate_code/prepare_backend/data/configuration.php';
$out=[];
foreach (json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $fixture) {
 $accepted=true;
 try {
  if ($fixture['mode']==='project') {
   $source=$fixture['source'].($fixture['byte']<0?'':chr($fixture['byte']));
   $key=$fixture['key'].($fixture['key_byte']<0?'':chr($fixture['key_byte']));
   $project=new \compile\native_project($key,$source,$fixture['output']);
  } else { $config=new \prepare_backend\backend_configuration(...$fixture['values']); }
 } catch (\InvalidArgumentException $error) {$accepted=false;}
 $out[]=$accepted;
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
