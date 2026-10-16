<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';
try {
	$config = new native_cpp_config($argv[4], $argv[3], $argv[2]);
	if ($argc > 5) { $config->linker = $argv[5]; }
	if ($argc > 6) { $config->linker_threads = (int)$argv[6]; }
	$result = (new Native_CPP_Build($config))->build($argv[1]);
	echo json_encode(['executable' => $result->executable, 'compiled' => $result->compiled,
		'reused' => $result->reused, 'pch_built' => $result->pch_built, 'linked' => $result->linked], JSON_THROW_ON_ERROR), "\n";
} catch (\Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
