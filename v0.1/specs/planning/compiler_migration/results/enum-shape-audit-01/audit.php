<?php
declare(strict_types=1);
$rows=[];
foreach (['compiler/src','compiler/src-runtime-preparation'] as $root) {
 foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
  if ($file->getExtension()!=='php') { continue; }
  $source=file_get_contents($file->getPathname());$tokens=token_get_all($source);$namespace='';
  for ($i=0,$n=count($tokens);$i<$n;++$i) {
   $t=$tokens[$i];
   if (is_array($t) && $t[0]===T_NAMESPACE) {
    $namespace='';for (++$i;$i<$n && $tokens[$i]!==';';++$i) { if (is_array($tokens[$i]) && in_array($tokens[$i][0],[T_STRING,T_NAME_QUALIFIED],true)) {$namespace.=$tokens[$i][1];} }
   }
   if (!is_array($t) || $t[0]!==T_ENUM) {continue;}
   $row=['name'=>'','namespace'=>$namespace,'file'=>$file->getPathname(),'line'=>$t[2],'backing'=>'unit','cases'=>[],'methods'=>[],'sha256'=>hash('sha256',$source)];$backed=false;
   for (++$i;$tokens[$i]!=='{';++$i) {
    if ($tokens[$i]===':') {$backed=true;}
    if (is_array($tokens[$i]) && $tokens[$i][0]===T_STRING) {if($row['name']===''){$row['name']=$tokens[$i][1];}elseif($backed){$row['backing']=$tokens[$i][1];}}
   }
   $depth=1;
   for (++$i;$i<$n && $depth>0;++$i) {
    $v=$tokens[$i]; if ($v==='{') {++$depth;} elseif($v==='}') {--$depth;}
    if ($depth!==1 || !is_array($v)) {continue;}
    if ($v[0]===T_CASE) {
     $case=['name'=>'','literal'=>null];
     for (++$i;$tokens[$i]!==';';++$i) { $c=$tokens[$i];if (is_array($c) && $c[0]===T_STRING && $case['name']==='') {$case['name']=$c[1];}elseif(is_array($c) && in_array($c[0],[T_CONSTANT_ENCAPSED_STRING,T_LNUMBER],true)){$case['literal']=$c[1];} }
     $row['cases'][]=$case;
    } elseif ($v[0]===T_FUNCTION) {
     for (++$i;$i<$n;++$i) {if(is_array($tokens[$i]) && $tokens[$i][0]===T_STRING){$row['methods'][]=$tokens[$i][1];break;}}
    }
   }
   --$i;$rows[]=$row;
  }
 }
}
usort($rows,fn($a,$b)=>[$a['file'],$a['line']]<=>[$b['file'],$b['line']]);
echo json_encode(['scope'=>['compiler/src','compiler/src-runtime-preparation'],'method'=>'token_get_all declaration inventory; no consumer symbol resolution','enums'=>$rows],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
