<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../compiler/boot.php';

use scpp\compiler\{Parser, Preparation, Tokenizer, entry_target, flavor, source_file};

$arrow = match ($argv[1] ?? '') {
	'explicit' => 'fn(int $n):int=>$n+1',
	'context' => 'fn($n)=>$n+1',
	default => throw new InvalidArgumentException('Choose explicit or context'),
};
$text = 'function apply(callback<int,int> $f):int {return $f(1);}';
for ($i = 0; $i < 400; ++$i) $text .= '$local' . $i . "=123456789;\n";
for ($i = 0; $i < 128; ++$i) $text .= '$out' . $i . '=apply(' . $arrow . ");\n";
$text .= 'return 8;';
$tokens = (new Tokenizer(new source_file('bench.php', $text, flavor::php)))->tokenize();
$samples = [];
for ($i = 0; $i < 109; ++$i) {
	$file = (new Parser($tokens))->parse();
	$start = hrtime(true);
	$program = (new Preparation())->prepare($file, new entry_target(31));
	$elapsed = (hrtime(true) - $start) / 1_000_000;
	if ($i >= 8) $samples[] = $elapsed;
	unset($file, $program);
	gc_collect_cycles();
}
sort($samples);
printf("%d bytes, median %.3f ms, p25 %.3f, p75 %.3f\n", strlen($text), $samples[50], $samples[25], $samples[75]);
