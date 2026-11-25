<?php
declare(strict_types=1);

use Scpp\S2S\PreTokenizer\PreTokenizer;

require_once __DIR__ . '/bootstrap.php';

$input = $argv[1] ?? null;
if ($input === null) {
	fwrite(STDERR, "Usage: php bin/preview_pre_tokenizer.php <file.php>\n");
	exit(1);
}

$source = file_get_contents($input);
if ($source === false) {
	fwrite(STDERR, "Failed to read input file.\n");
	exit(1);
}

$preTokenizer = new PreTokenizer();
$result = $preTokenizer->rewrite($source);

echo "=== Rewritten Source ===\n";
echo $result->source;
if ($result->source !== '' && !str_ends_with($result->source, "\n")) {
	echo "\n";
}

echo "\n=== Annotation Memory ===\n";
echo json_encode($result->annotations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
