<?php
declare(strict_types=1);

use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\S2SException;

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
	copy($file, $targetDir . '/' . basename($file));

	try {
		$result = $transpiler->transpile($file, true);
		file_put_contents($targetDir . '/' . $base . '.hpp', implode("\n", $result->headerLines) . "\n");
		file_put_contents($targetDir . '/' . $base . '.cpp', implode("\n", $result->sourceLines) . "\n");
		$report[] = $base . ': ok';
	} catch (S2SException $e) {
		file_put_contents($targetDir . '/' . $base . '.errors.txt', $e->getMessage() . "\n");
		$report[] = $base . ': error';
	} catch (Throwable $e) {
		file_put_contents($targetDir . '/' . $base . '.errors.txt', 'internal error: ' . $e->getMessage() . "\n");
		$report[] = $base . ': internal-error';
	}
}

file_put_contents($outDir . '/report.txt', implode("\n", $report) . "\n");
echo implode("\n", $report) . "\n";
