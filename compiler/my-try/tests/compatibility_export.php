<?php
// Host-only adapter for the pinned v0.1 generator; not compiler implementation.
require_once $argv[1] . '/bin/bootstrap.php';
$generator = new \Scpp\S2S\Transpiler(phpProfile: $argv[3]);
$result = $generator->transpile($argv[2]);
if ($result->errors !== []) {
	throw new \RuntimeException(implode("\n", $result->errors));
}
// Same single-file assembly as the baseline tests/tools/run_tests.php harness.
$body = $result->sourceLines;
if ($body !== [] && preg_match('/^#include\s+"[^"]+\.hpp"$/', $body[0]) === 1) {
	array_shift($body);
}
echo implode("\n", array_merge($result->headerLines, [''], $body)), "\n";
