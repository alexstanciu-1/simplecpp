<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[1], [$argv[4] ?? 'build_proof']));
$publication = new Output_Publication($argv[3]);
try {
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$argv[2]]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp($publication);
}
finally { $publication->close(); }
