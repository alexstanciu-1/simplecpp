<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(dirname(__DIR__, 4) . '/runtime/metadata/modules',
	['filesystem', 'json', 'process', 'datetime']));
$publication = new Output_Publication($argv[2]);
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1]]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp($publication);
}
finally { $publication->close(); }
