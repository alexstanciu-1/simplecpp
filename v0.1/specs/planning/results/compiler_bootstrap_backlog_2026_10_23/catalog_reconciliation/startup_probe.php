<?php
namespace scpp\compiler;
require '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$c = new Compiler(new cpp_lowering_options(false));
$c->init(['/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/tests/samples/01_base']);
$r=Model::$type_catalog->registry();
echo json_encode(['definitions'=>$r->definition_count(),'types'=>$r->type_count(),'applications'=>$r->application_count(),'bindings'=>Model::$cpp_type_bindings->definition_count()]), "\n";
for ($i=1; $i<=$r->definition_count(); $i++) { $d=$r->definition($i); echo $d->name(), "\n"; }
