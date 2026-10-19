<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';
try {
	$config = new native_cpp_config($argv[4], $argv[3], $argv[2]);
	$result = (new Native_CPP_Build($config))->build($argv[1]);
	fwrite(STDERR, $result->diagnostics);
	echo json_encode(['executable' => $result->executable, 'compiled' => $result->compiled,
		'reused' => $result->reused, 'pch_built' => $result->pch_built, 'linked' => $result->linked], JSON_THROW_ON_ERROR), "\n";
} catch (\Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
