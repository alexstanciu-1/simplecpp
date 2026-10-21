<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$publication = new Output_Publication($argv[1] . '/published');
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1] . '/source']);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp($publication);
} finally { $publication->close(); }
