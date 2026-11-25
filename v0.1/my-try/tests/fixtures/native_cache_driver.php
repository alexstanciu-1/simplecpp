<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';
try {
	$config = new native_cpp_config($argv[4], $argv[3], $argv[2]);
	$config->pch = ($argv[5] ?? '') !== '--no-pch'; // This cache fixture explicitly exercises PCH by default.
	if ($argc > 5) { if ($argv[5] !== '--no-pch') { $config->linker = $argv[5]; } }
	if ($argc > 6) { $config->linker_threads = (int)$argv[6]; }
	if ($argc > 7) { $config->jobs = (int)$argv[7]; }
	$result = (new Native_CPP_Build($config))->build($argv[1]);
	fwrite(STDERR, $result->diagnostics);
	echo json_encode(['executable' => $result->executable, 'compiled' => $result->compiled,
		'reused' => $result->reused, 'pch_built' => $result->pch_built, 'linked' => $result->linked], JSON_THROW_ON_ERROR), "\n";
} catch (\Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
