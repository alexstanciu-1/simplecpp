<?php

/*
 * Role: browser/CLI host entry for C++ S2S output or the existing compiler debug report.
 * Call map: boot.php -> Compiler::init -> exec_cpp (S2S) or exec (debug report).
 * This host entry is not part of the convertible compiler core.
 */
namespace scpp\compiler;

\define('dbg', true);
require_once __DIR__ . '/boot.php';

if (PHP_SAPI !== 'cli') {
	header('Content-Type: text/plain; charset=UTF-8');
}

// CLI: --s2s SOURCE_DIRECTORY. HTTP: ?mode=s2s&source=SOURCE_DIRECTORY.
// No parameter retains the existing sample/debug report.
$s2s = false;
$source_directory = '';
$usage_error = false;
if (PHP_SAPI === 'cli')
{
	if ($argc !== 1)
	{
		if (($argc !== 3) || ($argv[1] !== '--s2s')) {
			$usage_error = true;
		}
		else {
			$s2s = true;
			$source_directory = $argv[2];
			$usage_error = $source_directory === '';
		}
	}
}
else
{
	$mode = $_GET['mode'] ?? 'report';
	$source_directory = $_GET['source'] ?? '';
	if ((!is_string($mode)) || (!is_string($source_directory))) {
		$usage_error = true;
	}
	elseif ($mode === 's2s') {
		$s2s = true;
		$usage_error = $source_directory === '';
	}
	elseif ($mode !== 'report') {
		$usage_error = true;
	}
}
if ($usage_error)
{
	if (PHP_SAPI === 'cli') {
		fwrite(STDERR, "Usage: php compiler/my-try/main.php [--s2s SOURCE_DIRECTORY]\n");
	}
	else {
		http_response_code(400);
		echo "Usage: main.php?mode=s2s&source=SOURCE_DIRECTORY (or mode=report)\n";
	}
	exit(1);
}

if ($s2s)
{
	try {
		$compiler = new Compiler();
		$compiler->init([$source_directory]);
		$compiler->exec_cpp();
		echo Model::$cpp_files[0]->text;
	}
	catch (\Throwable $error)
	{
		$message = 'S2S generation failed: ' . $error->getMessage() . "\n";
		if (PHP_SAPI === 'cli') {
			fwrite(STDERR, $message);
		}
		else {
			http_response_code(500);
			echo $message;
		}
		exit(1);
	}
	exit(0);
}

// Imported stage reports escape their output for an HTML preformatted block.
// Buffer once so CLI/plain-text HTTP clients receive the original text safely.
ob_start();
try
{
	$paths /** vector<string> */ = [__DIR__ . '/tests/samples/01_base'];
	$compiler = new Compiler();
	$compiler->init($paths);
	$compiler->exec();

	echo "Simple C++ — PHP compiler\n\n";
	foreach (Model::$modules as $module) {
echo "Module: {$module->path}\n";
		foreach ($module->files as $file) {
echo "\nSource: {$file->path}\n";
			echo htmlspecialchars($file->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), "\n";
		}
	}
	$host = new Host_Report();
	$host->show();
	$host->run_native();
	$report = ob_get_clean();
	echo html_entity_decode($report, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
catch (\Throwable $error)
{
	ob_end_clean();
	if (PHP_SAPI !== 'cli') {
		http_response_code(500);
	}
	echo "Compilation failed: ", $error->getMessage(), "\n";
	exit(1);
}
