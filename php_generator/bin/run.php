#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../tools/s2s/bin/bootstrap.php';

use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\S2SException;

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
	echo implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL;
} catch (S2SException $e) {
	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(3);
} catch (Throwable $e) {
	fwrite(STDERR, 'internal error: ' . $e->getMessage() . PHP_EOL);
	exit(4);
}

exit(0);
