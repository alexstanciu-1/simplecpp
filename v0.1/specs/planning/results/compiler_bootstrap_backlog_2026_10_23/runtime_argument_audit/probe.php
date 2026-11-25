<?php
namespace scpp\compiler;
require '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$cases = [
'derived_append' => 'class Base {} class Child extends Base {} $s=new Storage<Base>();$v=new Child();$s->append($v);',
'derived_append_view' => 'class Base {} class Child extends Base {} $s=new Storage<Base>();$v=new Child();$base Base=$v;$s->append($base);',
'derived_add' => 'class Base {} class Child extends Base {} $s=new Keyed_Storage<Base>();$v=new Child();$s->add("x",$v);',
'derived_add_view' => 'class Base {} class Child extends Base {} $s=new Keyed_Storage<Base>();$v=new Child();$base Base=$v;$s->add("x",$base);',
'nullable_append' => 'class Row {} function f(?Row $v):void {$s=new Storage<Row>();if($v!==null){$s->append($v);}}',
'nullable_append_view' => 'class Row {} function f(?Row $v):void {$s=new Storage<Row>();if($v!==null){$required Row=$v;$s->append($required);}}',
];
 Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load('/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/metadata/modules',['compiler']));
$rows=[];
foreach($cases as $name=>$source)foreach([false,true] as $mode){
 Compiler_Lifecycle::reset();
 $m=new module('/audit','/audit','/audit');$f=new file();$f->path='main.phs';$f->content=$source;Source_Registry::add($m,$f);Model::$modules->add($m->name,$m);
 try{(new Compiler(new cpp_lowering_options($mode)))->exec_cpp();$result='PASS';}catch(\Throwable $e){$result=$e->getMessage();}
 $rows[]=['case'=>$name,'concrete'=>$mode,'result'=>$result];
}
echo json_encode($rows,JSON_PRETTY_PRINT),"\n";
