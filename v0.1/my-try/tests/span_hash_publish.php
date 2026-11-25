<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['source']));
$publication = new Output_Publication($argv[1] . '/published');
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1] . '/source']);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp($publication);
} finally { $publication->close(); }
