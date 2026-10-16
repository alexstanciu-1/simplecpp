<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host argument parsing only; command policy and graph generation are portable workers.
try
{
	if ($argc < 3) {
		throw new \RuntimeException('Usage: php build_ninja.php PUBLISHED_DIRECTORY BUILD_DIRECTORY [--reuse] [--no-pch] [-O0|-O1|-O2|-O3] [--linker=PATH] [--linker-threads=N] [--jobs=N] [--compiler=PATH] [--runtime=PATH] [--ninja=PATH]');
	}
	$config = new native_ninja_config('/usr/bin/clang++', dirname(__DIR__, 3) . '/runtime/include', $argv[2]);
	for ($index = 3; $index < $argc; $index++)
	{
		$option = $argv[$index];
		if ($option === '--reuse') {
			$config->reuse = true;
		}
		elseif ($option === '--no-pch') {
			$config->pch = false;
		}
		elseif ($option === '-O0') {
			$config->optimization = 0;
		}
		elseif ($option === '-O1') { $config->optimization = 1; }
		elseif ($option === '-O3') { $config->optimization = 3; }
		elseif ($option === '-O2') {
			$config->optimization = 2;
		}
		elseif (preg_match('/^--jobs=([1-9][0-9]*)$/D', $option, $match) === 1) {
			$config->jobs = (int)$match[1];
		}
		elseif (str_starts_with($option, '--compiler=')) {
			$config->compiler = substr($option, 11);
		}
		elseif (str_starts_with($option, '--runtime=')) {
			$config->runtime_include = substr($option, 10);
		}
		elseif (str_starts_with($option, '--ninja=')) {
			$config->ninja = substr($option, 8);
		}
		elseif (str_starts_with($option, '--linker=')) {
			$config->linker = substr($option, 9);
		}
		elseif (preg_match('/^--linker-threads=(0|[1-9][0-9]*)$/D', $option, $match) === 1) {
			$config->linker_threads = (int)$match[1];
		}
		else {
			throw new \RuntimeException('Unknown Ninja build option: ' . $option);
		}
	}
	$result = (new Native_Ninja_Build($config))->build($argv[1]);
	echo json_encode(['executable' => $result->executable, 'output' => $result->output], JSON_THROW_ON_ERROR), "\n";
}
catch (\Throwable $error) {
	fwrite(STDERR, $error->getMessage() . "\n"); exit(1);
}
