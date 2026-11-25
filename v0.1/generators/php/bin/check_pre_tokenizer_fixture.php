<?php
declare(strict_types=1);

use Scpp\S2S\PreTokenizer\PreTokenizer;

require_once __DIR__ . '/bootstrap.php';

$input = $argv[1] ?? null;
$expected = $argv[2] ?? null;
$mode = $argv[3] ?? 'check';

if ($input === null || $expected === null) {
	fwrite(STDERR, "Usage: php bin/check_pre_tokenizer_fixture.php <input.phs> <expected.json> [check|update]\n");
	exit(1);
}

$source = file_get_contents($input);
if ($source === false) {
	fwrite(STDERR, "Failed to read input file: {$input}\n");
	exit(1);
}

$preTokenizer = new PreTokenizer();
$result = $preTokenizer->rewrite($source);

$actual = [
	'source' => $result->source,
	'annotations' => $result->annotations,
];

$actualJson = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if ($mode === 'update') {
	file_put_contents($expected, $actualJson);
	echo "updated {$expected}\n";
	exit(0);
}

	if (!is_file($expected)) {
	fwrite(STDERR, "Expected fixture not found: {$expected}\n");
	exit(1);
}

$expectedJson = file_get_contents($expected);
if ($expectedJson === false) {
	fwrite(STDERR, "Failed to read expected fixture: {$expected}\n");
	exit(1);
}

if ($expectedJson !== $actualJson) {
	fwrite(STDERR, "Fixture mismatch for {$input}\n");
	fwrite(STDERR, "Expected: {$expected}\n");
	exit(2);
}

echo "ok {$input}\n";
