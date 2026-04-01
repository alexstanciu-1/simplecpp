#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../tools/s2s/bin/bootstrap.php';

use Scpp\S2S\Transpiler;

// --- validate input ---
if ($argc < 2) {
	fwrite(STDERR, "Usage: run.php <input.php>\n");
	exit(1);
}

$inputFile = $argv[1];

if (!is_file($inputFile)) {
	fwrite(STDERR, "Input file not found: {$inputFile}\n");
	exit(1);
}

try {
	$transpiler = new Transpiler();

	$cppFile = $transpiler->transpile($inputFile);

	if ($cppFile->errors !== []) {
		foreach ($cppFile->errors as $error) {
			fwrite(STDERR, $error . PHP_EOL);
		}
		exit(4);
	}

	echo implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL;

} catch (Throwable $e) {
	fwrite(STDERR, "Generation error: " . $e->getMessage() . " | line ". $e->getLine() ." | ".$e->getFile()."\n");
	fwrite(STDERR, "Generation error: " . $e->getTraceAsString() . "\n");
	exit(3);
}

exit(0);
