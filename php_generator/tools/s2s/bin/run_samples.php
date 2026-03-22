<?php
declare(strict_types=1);

use Scpp\S2S\Transpiler;

require_once __DIR__ . '/bootstrap.php';

$samplesDir = $argv[1] ?? (__DIR__ . '/../../samples');
$outDir = $argv[2] ?? (__DIR__ . '/../../build/samples_out');
@mkdir($outDir, 0777, true);

$transpiler = new Transpiler();
$files = glob(rtrim($samplesDir, '/') . '/*.php') ?: [];
sort($files);

$report = [];
foreach ($files as $file) {
	$base = pathinfo($file, PATHINFO_FILENAME);
	$targetDir = $outDir . '/' . $base;
	@mkdir($targetDir, 0777, true);

	$result = $transpiler->transpile($file);
	file_put_contents($targetDir . '/' . $base . '.hpp', implode("\n", $result->headerLines) . "\n");
	file_put_contents($targetDir . '/' . $base . '.cpp', implode("\n", $result->sourceLines) . "\n");
	copy($file, $targetDir . '/' . basename($file));
	if ($result->errors !== []) {
		file_put_contents($targetDir . '/' . $base . '.errors.txt', implode("\n", $result->errors) . "\n");
	}

	$report[] = $base . ': ' . ($result->errors === [] ? 'ok' : ('notes=' . count($result->errors)));
}

file_put_contents($outDir . '/report.txt', implode("\n", $report) . "\n");
echo implode("\n", $report) . "\n";
