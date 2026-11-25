<?php
namespace scpp\compiler;
require '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$cases = [
'bare_foreach' => 'foreach (["mixed","dynamic"] as $name) {echo $name;}',
'typed_foreach' => '$names vector<string> =["mixed","dynamic"];foreach ($names as $name) {echo $name;}',
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
