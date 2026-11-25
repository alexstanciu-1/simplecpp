<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';

/**
 * Time successful close or failed-generation retirement at its gate, without production instrumentation.
 * @return mixed[]
 */
function measure_close(string $app, string $flavor, bool $failed): array /** list<array<string,mixed>> */
{
	mkdir($app);
	file_put_contents($app . '/module.json', '{}');
	file_put_contents($app . '/main.' . $flavor, 'value(); return 8;');
	$header = $flavor === 'cpp' ? 'void value()' : 'function value(): void';
	$compiler = new Project_Compiler();
	$session = null;
	$target = new entry_target(31);
	$rows = [];
	$stamp = time();
	for ($step = 0; $step < 51; $step++)
	{
		$phase = $step === 0 ? 'initial' : (($step % 2) === 1 ? 'edit' : 'noop');
		if ($phase !== 'noop') {
			file_put_contents($app . '/library.' . $flavor, $header . '{' . ($failed ? 'missing();' : '') . 'echo ' . $step . ';}');
			touch($app . '/library.' . $flavor, ++$stamp);
		}
		$session = $compiler->frontend($app, $session, $app . '/out');
		if ($failed) {
			try {
				Incremental_Preparation::prepare($session, $target);
				throw new \LogicException('Expected preparation failure');
			}
			catch (diagnostic_group) {
			}
		}
		else {
			Incremental_Preparation::prepare($session, $target);
			$files = $session->files();
			$key = $session->entry_key();
			$program = new prepared_program($key, $files, new prepared_entry($files[$key], $target), $session);
			$output = Cpp_Program::emit($program);
			Cpp_Bundle::write($app . '/out', $output, array_map(static fn(ast_file_node $file): string => $file->source()->path, array_values($files)));
			$session->preparation_work->complete_emission();
		}
		// No external reference to the prior generation survives into this measurement.
		$start = hrtime(true);
		if ($failed) {
			Generation_Cleanup::failed($session);
		}
		else {
			Generation_Cleanup::close($session);
		}
		$close_ms = (hrtime(true) - $start) / 1e6;
		$start = hrtime(true);
		$collected = gc_collect_cycles();
		$gc_ms = (hrtime(true) - $start) / 1e6;
		$rows[] = ['phase' => $phase, 'close_ms' => $close_ms, 'gc_ms' => $gc_ms, 'gc_collected' => $collected];
	}
	return $rows;
}

$failed = ($argv[1] ?? '') === 'failed';
$root = sys_get_temp_dir() . '/scpp-close-timing-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	$report = [];
	foreach (['cpp', 'php', 'js'] as $flavor) {
		$report[$flavor] = measure_close($root . '/' . $flavor, $flavor, $failed);
		gc_collect_cycles();
	}
	echo json_encode($report, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR) . "\n";
}
finally {
	$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
