<?php
declare(strict_types=1);

use Scpp\S2S\Transpiler;

require_once __DIR__ . '/bootstrap.php';

$input = $argv[1] ?? null;
$outDir = $argv[2] ?? null;
if ($input === null || $outDir === null) {
	fwrite(STDERR, "Usage: php bin/transpile_fixture.php <file.php> <out_dir>\n");
	exit(1);
}

$transpiler = new Transpiler();
$result = $transpiler->transpile($input);
$baseName = pathinfo($input, PATHINFO_FILENAME);
@mkdir($outDir, 0777, true);
file_put_contents($outDir . '/' . $baseName . '.hpp', implode("\n", $result->headerLines) . "\n");
file_put_contents($outDir . '/' . $baseName . '.cpp', implode("\n", $result->sourceLines) . "\n");
if ($result->errors !== []) {
	file_put_contents($outDir . '/' . $baseName . '.errors.txt', implode("\n", $result->errors) . "\n");
}

echo "Generated {$baseName}.hpp and {$baseName}.cpp\n";
if ($result->errors !== []) {
	echo "Notes/errors:\n- " . implode("\n- ", $result->errors) . "\n";
}
