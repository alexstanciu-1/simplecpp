<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fixtures = [
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_preview.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_preview.expected.json',
	],
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_return_sites.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_return_sites.expected.json',
	],
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_inline_comments.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_inline_comments.expected.json',
	],
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_namespaced_types.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_namespaced_types.expected.json',
	],
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_nested_generics.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_nested_generics.expected.json',
	],
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_prefix_ref_param.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_prefix_ref_param.expected.json',
	],
	[
		'input' => $root . '/samples/know_how/pre_tokenizer_const_params.phs',
		'expected' => $root . '/samples/know_how/pre_tokenizer_const_params.expected.json',
	],
];

$script = __DIR__ . '/check_pre_tokenizer_fixture.php';
$mode = $argv[1] ?? 'check';
$failures = 0;

foreach ($fixtures as $fixture) {
	$output = [];
	$cmd = escapeshellarg(PHP_BINARY) . ' ' .
		escapeshellarg($script) . ' ' .
		escapeshellarg($fixture['input']) . ' ' .
		escapeshellarg($fixture['expected']) . ' ' .
		escapeshellarg($mode) . ' 2>&1';

	exec($cmd, $output, $exitCode);
	echo implode("\n", $output);
	if ($output !== []) {
		echo "\n";
	}
	if ($exitCode !== 0) {
		$failures++;
	}
}

exit($failures === 0 ? 0 : 1);
