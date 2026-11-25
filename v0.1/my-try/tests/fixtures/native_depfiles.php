<?php
require_once __DIR__ . '/../../boot.php';

$rule = 'output: one\\ two /absolute/hash\\#name dollar$$name ' . "\\\n" . " one\\ two\nignored:\n";
$paths = \scpp\compiler\Native_Depfiles::paths($rule, '/inputs');
if ($paths !== ['/inputs/one two', '/absolute/hash#name', '/inputs/dollar$name']) {
	throw new \RuntimeException('Dependency escaping, continuation or deduplication failed');
}
foreach (['no rule', 'output: broken\\', 'output: $expansion'] as $invalid) {
	$rejected = false;
	try { \scpp\compiler\Native_Depfiles::paths($invalid, '/inputs'); }
	catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \RuntimeException('Malformed dependency accepted'); }
}
echo "PASS\n";
