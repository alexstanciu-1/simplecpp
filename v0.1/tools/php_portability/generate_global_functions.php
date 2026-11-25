<?php
$root=dirname(__DIR__, 2);
require $root.'/tools/php_portability/runtime/bootstrap.php';
$map=require $root.'/tools/php_portability/function_map.php';
$wrapper="<?php\ndeclare(strict_types=1);\n\n// Generated global facade. Owned by function_map.php; no per-file imports.\n";
foreach($map as $name=>$rule) {
 $alias=$name;
 $impl=$rule['php']??$name;
 $reflection=new ReflectionFunction($impl);
 $params=[];$args=[];
 $max=max((array)$rule['arity']);
 foreach(array_slice($reflection->getParameters(),0,$max) as $p) {
  $render=static function(ReflectionType $type) use (&$render):string {
   if($type instanceof ReflectionUnionType)return implode('|',array_map($render,$type->getTypes()));
   $name=$type->getName();$name=$type->isBuiltin()?$name:'\\'.$name;
   return ($type->allowsNull()&&!in_array($name,['mixed','null'],true)?'?':'').$name;
  };
  $param=($p->hasType()?$render($p->getType()).' ':'').($p->isPassedByReference()?'&':'').'$'.$p->getName();
  if($p->isDefaultValueAvailable()) {
   $default=var_export($p->getDefaultValue(),true);
   if($p->isDefaultValueConstant() && str_ends_with($p->getDefaultValueConstantName(), 'PHP_INT_MAX')) $default='\\PHP_INT_MAX';
   $param.=' = '.$default;
  }
  $params[]=$param;$args[]='$'.$p->getName();
 }
 $ret=$reflection->hasReturnType()?$render($reflection->getReturnType()):'';
 $wrapper.='function '.$alias.'('.implode(', ',$params).')'.($ret!==''?': '.$ret:'').' { '.($ret==='void'?'':'return ').'\\'.$impl.'('.implode(', ',$args).'); }' . "\n";
}
$path=$root.'/tools/php_portability/runtime/global_functions.php';
if (($argv[1] ?? '') === '--check') {
    if (file_get_contents($path) !== $wrapper) { fwrite(STDERR, "Global facade is stale\n"); exit(1); }
} else { file_put_contents($path, $wrapper); }
