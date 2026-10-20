<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Publish through the normal coordinator; null stays expression-only and non-storable.
$publication = new Output_Publication($argv[2]);
try {
	$compiler = new Compiler();
	$compiler->cpp_lowering->prelower_templates = true;
	$compiler->init([$argv[1]]);
	$compiler->exec_cpp($publication);
} finally { $publication->close(); }
