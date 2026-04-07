#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../tools/s2s/bin/bootstrap.php';

use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\S2SException;

const SCPP_VERSION = '0.1.0-dev';

main($argv);

function main(array $argv): void
{
	$args = $argv;
	array_shift($args);

	if ($args === [] || in_array($args[0], ['-h', '--help'], true)) {
		print_help();
		return;
	}

	if ($args[0] === '--version') {
		print_version();
		return;
	}

	if ($args[0] === '--doctor') {
		print_doctor();
		return;
	}

	$inputFile = $args[0];
	if (!is_file($inputFile)) {
		fwrite(STDERR, "Input file not found: {$inputFile}\n");
		exit(1);
	}

	try {
		$transpiler = new Transpiler();
		$cppFile = $transpiler->transpile($inputFile);
		echo implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL;
	} catch (S2SException $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);
		exit(3);
	} catch (Throwable $e) {
		fwrite(STDERR, 'internal error: ' . $e->getMessage() . PHP_EOL);
		exit(4);
	}
}

function print_help(): void
{
	echo "Prism++ CLI\n";
	echo "Usage:\n";
	echo "  scpp <input.php>\n";
	echo "  scpp --help\n";
	echo "  scpp --version\n";
	echo "  scpp --doctor\n";
}

function print_version(): void
{
	echo 'scpp ' . SCPP_VERSION . PHP_EOL;
}

function print_doctor(): void
{
	$repoRoot = dirname(__DIR__, 2);
	$entry = __FILE__;
	$phpIni = php_ini_loaded_file();
	$astLoaded = extension_loaded('ast') ? 'yes' : 'no';

	echo "scpp doctor\n";
	echo 'version: ' . SCPP_VERSION . PHP_EOL;
	echo 'php_binary: ' . PHP_BINARY . PHP_EOL;
	echo 'php_version: ' . PHP_VERSION . PHP_EOL;
	echo 'php_ini: ' . ($phpIni === false ? '(none)' : $phpIni) . PHP_EOL;
	$configPath = dirname((string) ($GLOBALS['argv'][0] ?? __FILE__)) . DIRECTORY_SEPARATOR . 'scpp.json';
	echo 'php_ast_loaded: ' . $astLoaded . PHP_EOL;
	echo 'repo_root: ' . $repoRoot . PHP_EOL;
	echo 'entrypoint: ' . $entry . PHP_EOL;
	echo 'argv0: ' . ((string) ($GLOBALS['argv'][0] ?? __FILE__)) . PHP_EOL;
	echo 'config_path: ' . (is_file($configPath) ? $configPath : '(none)') . PHP_EOL;
}
