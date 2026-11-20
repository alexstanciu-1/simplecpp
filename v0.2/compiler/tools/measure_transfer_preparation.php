<?php

declare(strict_types=1);

use scpp\compiler\{entry_target, flavor, Preparation, Program_Preparation, source_file, Source_Frontend, Symbol_Publication};

require_once __DIR__ . '/../boot.php';

// Use --baseline on a compiler predating numbered transfers. Parsing stays outside the timed region.
$fixtures = [
	'no exit' => 'while (flag) { while (flag) {} }',
	'bare exit' => 'while (flag) { while (flag) { break; } }',
	'bare exits through finally' => 'while (flag) { while (flag) { try { if (flag) { break; } continue; } finally {} } }',
];
foreach ([4, 8] as $depth) {
	$fixtures["bare exit at depth $depth"] = str_repeat('while (flag) {', $depth) . 'break;' . str_repeat('}', $depth);
}
if (!in_array('--baseline', $argv, true)) {
	foreach ([2, 4, 8] as $depth) {
		$fixtures["break $depth"] = str_repeat('while (flag) {', $depth) . "break $depth;" . str_repeat('}', $depth);
	}
	$fixtures['two destinations through finally'] = 'while (flag) { while (flag) { try { if (flag) { break; } break 2; } finally {} } }';
}
foreach ($fixtures as $label => $statements) {
	$source = new source_file('main', "void probe(bool flag) { $statements } return 0;", flavor::cpp);
	$file = (new Source_Frontend())->collect($source);
	$symbols = Symbol_Publication::completed(['main' => $file]);
	$program = Program_Preparation::prepare('main', ['main' => $file], new entry_target(31), $symbols);
	$body = $file->functions()[0]->body();
	$samples = [];
	for ($batch = 0; $batch < 22; ++$batch) {
		$start = hrtime(true);
		for ($n = 0; $n < 250; ++$n) {
			(new Preparation())->function_body($body, $symbols, null, $program->dependencies);
		}
		if ($batch > 0) $samples[] = (hrtime(true) - $start) / 250000;
	}
	sort($samples);
	printf("%s: %.2f us/body (median of 21 batches)\n", $label, $samples[10]);
}
