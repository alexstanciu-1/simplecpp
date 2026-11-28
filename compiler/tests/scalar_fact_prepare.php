<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../boot.php';

/**
 * Time only preparation and inspect the initializer, write, and local read before the return conversion.
 * @return array{compilation_session,float,array<string,int>}
 */
function measure(Project_Compiler $compiler, string $app, ?compilation_session $previous, bool $known): array
{
	$session = $compiler->frontend($app, $previous);
	$target = new entry_target(31);
	$start = hrtime(true);
	$counts = Incremental_Preparation::prepare($session, $target);
	$elapsed = (hrtime(true) - $start) / 1e6;
	$statements = $session->scope->resolve('func:probe0')->body()->statements;
	[$local, $write, $return] = array_slice($statements, -3);
	foreach ([$local, $write, $return->value->operand] as $site) {
		if ($site->constant_known() !== $known || $site->constant() !== ($known ? false : null)) {
			throw new \RuntimeException('Stale int-to-bool fact on ' . $site::class);
		}
	}
	return [$session, $elapsed, $counts];
}

[, $app, $flavor, $edit] = $argv;
$path = "$app/provider0." . flavor::from($flavor)->source_extension();
$initial = file_get_contents($path);
$changed = file_get_contents($edit);
$rows = [];
try {
	for ($sample = 0; $sample < 5; ++$sample) {
		file_put_contents($path, $initial);
		$compiler = new Project_Compiler();
		[$session, $cold, $all] = measure($compiler, $app, null, true);
		file_put_contents($path, $changed);
		[, $incremental, $dirty] = measure($compiler, $app, $session, false);
		if ($all['prepared'] !== 9 || $dirty !== ['prepared' => 1, 'reused' => 8]) {
			throw new \RuntimeException(json_encode([$all, $dirty]));
		}
		measure(new Project_Compiler(), $app, null, false);
		$rows[] = [$cold, $incremental];
	}
}
finally {
	file_put_contents($path, $initial);
}
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
