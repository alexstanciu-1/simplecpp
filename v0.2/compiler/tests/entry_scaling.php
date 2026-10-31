<?php

declare(strict_types=1);
$startup = hrtime(true);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Cpp_Emitter, Cpp_Publication, Parser, Preparation, Tokenizer, entry_target, flavor, source_file};

function milliseconds(int $start /** int64; monotonic nanoseconds */): float /** float64; elapsed milliseconds */
{
	return (hrtime(true) - $start) / 1_000_000;
}

$startup_ms = milliseconds($startup);
$target = new entry_target(31);
$report = ['bootstrap_ms' => $startup_ms, 'runs' => []];
$publication = tempnam(sys_get_temp_dir(), 'scpp-entry-timing-');
if ($publication === false) {
	throw new RuntimeException('Cannot create timing output');
}
try
{
	foreach ([100, 1000, 5000] as $count)
	{
		$text = 'int v0=8;';
		for ($index = 1; $index < $count; $index++) {
			$text .= 'int v' . $index . '=v' . ($index - 1) . ';';
		}
		$text .= 'return v' . ($count - 1) . ';';
		$runs = [];
		for ($repeat = 0; $repeat < 5; $repeat++)
		{
			$start = hrtime(true);
			$source = new source_file('scaling', $text, flavor::cpp);
			$input_ms = milliseconds($start);
			$start = hrtime(true);
			$tokens = (new Tokenizer($source))->tokenize();
			$tokenize_ms = milliseconds($start);
			$start = hrtime(true);
			$collection = (new Parser($tokens))->parse();
			$parse_collect_ms = milliseconds($start);
			$start = hrtime(true);
			$program = (new Preparation())->prepare($collection, $target);
			$prepare_ms = milliseconds($start);
			$start = hrtime(true);
			$output = (new Cpp_Emitter())->emit($program);
			$render_ms = milliseconds($start);
			$start = hrtime(true);
			Cpp_Publication::write($publication, $output);
			$publish_ms = milliseconds($start);
			if (($program->statement_visits !== ($count + 1)) || ($program->expression_visits !== ($count + 1))
				|| ($program->name_lookups !== $count) || (count($collection->main()->body()->references) !== $count)) {
				throw new RuntimeException('Entry work is not one visit per statement/expression and one lookup per reference');
			}
			$runs[] = compact('input_ms', 'tokenize_ms', 'parse_collect_ms', 'prepare_ms', 'render_ms', 'publish_ms');
			unset($program, $collection, $tokens, $source, $output);
		}
		$median = [];
		foreach (array_keys($runs[0]) as $stage) {
			$values = array_column($runs, $stage);
			sort($values);
			$median[$stage] = round($values[2], 3);
		}
		$report['runs'][] = ['locals' => $count, 'statements' => $count + 1,
			'expressions' => $count + 1, 'name_lookups' => $count, 'median_ms' => $median];
	}
}
finally {
	unlink($publication);
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
