<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Publish through the normal coordinator; null stays expression-only and non-storable.
$publication = new Output_Publication($argv[2]);
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1]]);
	$compiler->exec_cpp($publication);
	$standalone = new CPP_Generator();
	$artifacts = $standalone->generate_program(Model::$prepared_files, Model::$prepared_entry);
	if (CPP_Output::encode($artifacts) !== CPP_Output::encode(Model::$cpp_files)) {
		throw new \LogicException('Standalone generator and compiler defaults differ');
	}
} finally { $publication->close(); }
