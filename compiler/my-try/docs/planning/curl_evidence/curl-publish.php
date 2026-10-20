<?php
namespace scpp\compiler;
require_once $argv[1].'/compiler/my-try/boot.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1].'/runtime/metadata/modules',['curl','filesystem','json','strings']));
$publication = new Output_Publication($argv[2].'/published');
try {
 $compiler = new Compiler();
 $compiler->init([$argv[2].'/source']);
 $compiler->select_entry('main','main.phs');
 $compiler->exec_cpp($publication);
} finally { $publication->close(); }
