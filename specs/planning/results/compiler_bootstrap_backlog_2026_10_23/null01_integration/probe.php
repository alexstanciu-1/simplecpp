<?php
require $argv[1] . '/boot.php';
use scpp\compiler\{Compiler_Lifecycle,Model,Compiler,cpp_lowering_options,module,file,Source_Registry};
$cases = [
 'optional_return_null' => 'class Node {} function read(): ?Node { return null; } read();',
 'optional_argument_null' => 'class Node {} function read(?Node $n): void {} read(null);',
 'required_assignment_null' => 'class Node {} $n Node = null;',
 'typed_empty_optional_return' => 'class Node {} function read(): ?Node { $n Node = null; return $n; } read();',
 'nullable_required_reference' => 'class Node {} function read(Node &$n): void {} $n ?Node = null; read($n);',
];
$out=[];
foreach ([false,true] as $mode) foreach($cases as $name=>$source) {
 Compiler_Lifecycle::reset(); $module=new module('/probe','/probe','/probe');
 $file=new file(); $file->path='main.phs'; $file->content=$source;
 Source_Registry::add($module,$file); Model::$modules->add($module->name,$module);
 try { (new Compiler(new cpp_lowering_options($mode)))->exec_cpp(); $result='accepted'; }
 catch (RuntimeException $e) {$result=$e->getMessage();}
 $out[($mode?'concrete':'ordinary').'/'.$name]=$result;
}
echo json_encode($out,JSON_PRETTY_PRINT)."\n";
