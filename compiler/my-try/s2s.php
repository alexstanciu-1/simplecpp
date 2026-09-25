<?php

// Host-only entry: print the first v0.2 C++ artifact for a one-file source directory.
namespace scpp\compiler;

require_once __DIR__ . '/boot.php';
if ($argc !== 2) {
	fwrite(STDERR, "Usage: php compiler/my-try/s2s.php SOURCE_DIRECTORY\n");
	exit(1);
}
try {
	$compiler = new Compiler();
	$compiler->init([$argv[1]]);
	$compiler->exec_cpp();
	echo Model::$cpp_files[0]->text;
}
catch (\Throwable $error) {
	fwrite(STDERR, 'S2S generation failed: ' . $error->getMessage() . "\n");
	exit(1);
}
