<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host-only size injection avoids allocating a multi-gigabyte test string. Production
// Tokenizer still runs unchanged; the shim delegates all ordinary byte operations.
function string_byte_len(string $value): int
{
	if ($value === 'capacity_probe') { return 4294967296; }
	return \string_byte_len($value);
}

$source = new file();
$source->path = 'capacity.phs';
$source->content = 'capacity_probe';
$tokenizer = new Tokenizer($source);
$rejected = false;
try { $tokenizer->tokenize(); }
catch (\RuntimeException $error) {
	if ($error->getMessage() !== 'Source token buffer exceeds uint32 capacity') { throw $error; }
	$rejected = true;
}
if (!$rejected || $source->tokens !== null) {
	throw new \LogicException('Oversized input escaped admission or published tokens');
}
$source->content = '$valid';
$result = $tokenizer->tokenize();
$tokens = $result->tokens;
if (count($tokens) !== 1 || $tokens[0]->offset !== 0 || $tokens[0]->length !== 6) {
	throw new \LogicException('Tokenizer failed recovery after oversized input');
}
echo "Tokenizer rejects oversized input before spans and recovers\n";
