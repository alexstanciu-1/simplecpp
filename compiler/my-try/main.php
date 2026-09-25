<?php

/*
 * Role: browser/CLI host entry and full compiler debug report.
 * Call map: boot.php -> Compiler::init -> Compiler::exec -> native execution.
 * This host entry is not part of the convertible compiler core.
 */
namespace scpp\compiler;

\define('dbg', true);
require_once __DIR__ . '/boot.php';

if (PHP_SAPI !== 'cli') {
	header('Content-Type: text/plain; charset=UTF-8');
}

// Imported stage reports escape their output for an HTML preformatted block.
// Buffer once so CLI/plain-text HTTP clients receive the original text safely.
ob_start();
try
{
	$paths /** vector<string> */ = [__DIR__ . '/samples/01_base'];
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
