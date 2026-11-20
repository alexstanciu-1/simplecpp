<?php

declare(strict_types=1);

use scpp\compiler\{entry_target, flavor, Preparation, Program_Preparation, source_file, Source_Frontend, Symbol_Publication};

require_once __DIR__ . '/../boot.php';

// Time only re-preparation of an already collected body, including dependency watches.
foreach (array_slice($argv, 1) ?: [0, 1, 8] as $count) {
	$types = '';
	$arms = '';
	for ($i = 0; $i < 8; ++$i) {
		$types .= "class Failure$i : Exception {}; ";
		if ($i < $count) $arms .= " catch (Failure$i e) {}";
	}
	$source = new source_file('main', $types . "void probe() { try {} $arms finally {} } return 0;", flavor::cpp);
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
	printf("%d arms: %.2f us/body (median of 21 batches)\n", $count, $samples[10]);
}
