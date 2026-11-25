<?php
namespace scpp\compiler;
$paths /** vector<string> */ = ['/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/samples/01_base'];
$compiler = new Compiler();
$compiler->init($paths);
$compiler->exec();
foreach (Model::$llvm_files as $output) { echo $output->file_name, "\n", $output->text; }
