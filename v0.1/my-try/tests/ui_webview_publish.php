<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once dirname(__DIR__) . '/host/runtime_build.php';
$input = Runtime_Catalog_Loader::load($argv[2], [$argv[3]]);
Host_Runtime_Build::resolve($input);
Compiler_Lifecycle::configure_runtime($input);
$publication = new Output_Publication($argv[1] . '/published');
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1] . '/source']);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp($publication);
} finally { $publication->close(); }
