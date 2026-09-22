<?php
declare(strict_types=1);
require __DIR__ . '/../../compiler/bootstrap.php';
require __DIR__ . '/oracles/native_project_original.php';
function project_outcome(string $class, string $key, string $source, string $output): array {
    try { $p = new $class($key, $source, $output); return [$p->project_key, $p->source_root, $p->output_root]; }
    catch (InvalidArgumentException $e) { return ['error', $e->getMessage()]; }
}
$paths = ['', '/', '//', '/./', '/a//b/./', '/..', '/a/../b', 'relative', '/é/😀', '/.../..x', "/a\0b", "/\xff", '/a\\b'];
$cases=0;
foreach (['key', '', "bad\0key", "\xff"] as $key) {
 foreach ($paths as $source) { foreach ($paths as $output) {
  $old=project_outcome(compile\baseline_native_project::class,$key,$source,$output);
  $new=project_outcome(compile\native_project::class,$key,$source,$output);
  if ($old!==$new) { throw new RuntimeException('Native project mismatch'); }
  ++$cases;
 } }
}
mt_srand(20260923);
$parts=['a','','.','..','...','é',"\xff","\0",'a\\b'];
for ($i=0;$i<2000;++$i) {
 $path='/';for($j=0,$n=mt_rand(0,12);$j<$n;++$j){$path.=$parts[mt_rand(0,count($parts)-1)].'/';}
 if(project_outcome(compile\baseline_native_project::class,'key',$path,'/')!==project_outcome(compile\native_project::class,'key',$path,'/')) {throw new RuntimeException('Random root mismatch');}
 ++$cases;
}
echo $cases," native project configurations match frozen prototype\n";
