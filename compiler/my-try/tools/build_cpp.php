<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host CLI only; all build policy is in the portable native-build worker.
try {
	if ($argc < 3 || $argc > 6) {
		throw new \RuntimeException('Usage: php build_cpp.php PUBLISHED_DIRECTORY CACHE_DIRECTORY [--no-pch] [--runtime-snapshot] [-O0|-O2]');
	}
	$config = new native_cpp_config('/usr/bin/clang++', dirname(__DIR__, 3) . '/runtime/include', $argv[2]);
	for ($index = 3; $index < $argc; $index++) {
		if ($argv[$index] === '--no-pch') { $config->pch = false; }
		elseif ($argv[$index] === '--runtime-snapshot') { $config->runtime_snapshot = true; }
		elseif ($argv[$index] === '-O0') { $config->optimization = 0; }
		elseif ($argv[$index] === '-O2') { $config->optimization = 2; }
		else { throw new \RuntimeException('Unknown native build option'); }
	}
	$result = (new Native_CPP_Build($config))->build($argv[1]);
	fwrite(STDERR, $result->diagnostics);
	echo json_encode(['executable' => $result->executable, 'compiled' => $result->compiled,
		'reused' => $result->reused, 'pch_built' => $result->pch_built, 'linked' => $result->linked], JSON_THROW_ON_ERROR), "\n";
} catch (\Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
