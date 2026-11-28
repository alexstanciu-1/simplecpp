<?php

declare(strict_types=1);

use scpp\compiler\{diagnostic_group, entry_target, flavor, Incremental_Preparation, Parser, Preparation, Project_Compiler, source_file, Tokenizer};

require_once ($argv[1] ?? __DIR__ . '/../../../../compiler') . '/boot.php';
$target = new entry_target(31);
function report(string $case, array $samples): void
{
	sort($samples);
	printf("%s %.4f ms\n", $case, $samples[intdiv(count($samples), 2)] / 1e6);
}

foreach ([0, 32, 128] as $size) {
	$source = 'function apply(callback<int,int> $op):int {return $op(1);}';
	$body = '';
	if ($size === 0) {
		for ($i = 0; $i < 400; ++$i) $body .= '$local' . $i . '=123456789;';
		for ($i = 0; $i < 128; ++$i) $body .= '$out' . $i . '=apply(fn(int $n):int=>$n+1);';
		$source .= $body . 'return 8;';
	} else {
		for ($i = 0; $i < $size; ++$i) {
			$source .= 'function apply' . $i . '(callback<int,int> $op):int {return $op(1);}';
			$body .= 'apply' . $i . '(fn(int $n):int=>$n+' . $i . ');';
		}
		$source .= 'function &root(int &$x):int {' . $body . 'return $x;}$n=0;root($n);return 8;';
	}
	$tokens = (new Tokenizer(new source_file('bench.php', $source, flavor::php)))->tokenize();
	$samples = [];
	for ($i = 0; $i < 39; ++$i) {
		$file = (new Parser($tokens))->parse();
		$start = hrtime(true);
		$program = (new Preparation())->prepare($file, $target);
		if ($i >= 8) $samples[] = hrtime(true) - $start;
		unset($file, $program);
		gc_collect_cycles();
	}
	report($size ? "shared $size" : 'by-value', $samples);
}

$dir = sys_get_temp_dir() . '/scpp-ref-edit-' . bin2hex(random_bytes(8));
mkdir($dir);
try {
	file_put_contents("$dir/module.json", '{}');
	foreach (['direct', 'pointer', ...array_slice($argv, 2)] as $kind) {
		$helper = 'function &selectSlot(int &$x):int {return $x;}';
		$edited = str_replace('&selectSlot', 'selectSlot', $helper);
		$entry = match ($kind) {
			'direct' => '$n=1;$a =& selectSlot($n);return 8;',
			'pointer' => 'function run(callback<int,int&> $op):void {$n=1;$op($n);}run(selectSlot(...));return 8;',
			'erased' => 'function run(function<int&(int&)> $op):void {$n=1;$a =& $op($n);}run(selectSlot(...));return 8;',
			default => throw new InvalidArgumentException($kind),
		};
		if ($kind === 'pointer') [$helper, $edited] = [$edited, $helper];
		file_put_contents("$dir/main.phs", $entry);
		file_put_contents("$dir/lib.phs", $helper);
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($dir);
		Incremental_Preparation::prepare($session, $target);
		$samples = [[], []];
		$pending = [];
		for ($i = 0; $i < 69; ++$i) {
			foreach ([$edited, $helper] as $index => $edit) {
				file_put_contents("$dir/lib.phs", $edit);
				$session = $compiler->frontend($dir, $session);
				$dirty = $session->preparation_work->pending_count();
				$start = hrtime(true);
				try {
					$stats = Incremental_Preparation::prepare($session, $target);
					if (!$index || $stats['prepared'] !== 2) throw new RuntimeException('Unexpected recovery');
				} catch (diagnostic_group $e) {
					if ($index || !str_contains($e->getMessage(), $kind === 'direct' ? 'reference return' : 'signature mismatch')) throw $e;
				}
				$elapsed = hrtime(true) - $start;
				$pending[$index] = $dirty . '/' . $session->preparation_work->pending_count();
				if ($i >= 8) $samples[$index][] = $elapsed;
			}
		}
		report("$kind edit (pending before/after $pending[0])", $samples[0]);
		report("$kind recovery (2 bodies)", $samples[1]);
	}
} finally {
	foreach (glob("$dir/*") as $file) unlink($file);
	rmdir($dir);
}
