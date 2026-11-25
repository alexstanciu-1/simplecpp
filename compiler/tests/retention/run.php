<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';
require_once __DIR__ . '/inspect.php';

function retention_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \RuntimeException($message);
	}
}

/**
 * Exercise real incremental emission without detaching history; retain only scalar reports and weak probes.
 * @return mixed[]
 */
function retention_run(string $app, string $flavor, int $cycles /** uint32 */): array /** array<string,mixed> */
{
	mkdir($app);
	file_put_contents($app . '/module.json', '{}');
	$function = static fn(string $name, string $body): string => ($flavor === 'cpp'
		? "void $name()" : "function $name(): void") . '{' . $body . '}';
	file_put_contents($app . '/main.' . $flavor, 'value(); return 8;');
	file_put_contents($app . '/stable.' . $flavor, $function('stable', ''));
	file_put_contents($app . '/library.' . $flavor, $function('value', 'stable(); echo 0;'));
	$project = new Incremental_Project($app, $app . '/out', new entry_target(31));
	$probe = new Retention_Inspection();
	$rows = [];
	$stamp = time();
	$stable_root = null;
	$stable_tokens = null;
	$stable_entry = null;
	for ($step = 0; $step <= $cycles * 2; $step++)
	{
		$phase = $step === 0 ? 'initial' : (($step % 2) === 1 ? 'edit' : 'noop');
		if ($phase === 'edit') {
			file_put_contents($app . '/library.' . $flavor, $function('value', 'stable(); echo ' . $step . ';'));
			touch($app . '/library.' . $flavor, ++$stamp);
		}
		$started = hrtime(true);
		$result = $project->run();
		$compiler_ms = (hrtime(true) - $started) / 1e6;
		$session = $project->session();
		$stable = $session->scope->resolve('func:stable')->root();
		$entry = $session->scope->entries()['func:value'];
		if ($step === 0) {
			$stable_root = \WeakReference::create($stable);
			$stable_tokens = \WeakReference::create($stable->tokens);
			$stable_entry = \WeakReference::create($entry);
		}
		retention_check(($stable_root->get() === $stable) && ($stable_tokens->get() === $stable->tokens), 'Unchanged syntax lost identity');
		retention_check($stable_entry->get() === $entry, 'Stable symbol entry replaced');
		$expected = $step === 0 ? 3 : ($phase === 'edit' ? 1 : 0);
		retention_check($result['preparation']['prepared'] === $expected, 'Unexpected preparation scope');
		retention_check(count($result['emitted_sources']) === $expected, 'Unexpected emission scope');
		// Drop inspection aliases before measuring reachability and PHP cycle collection.
		unset($session, $stable, $entry);
		$started = hrtime(true);
		gc_collect_cycles();
		$gc_ms = (hrtime(true) - $started) / 1e6;
		$memory = memory_get_usage(false);
		$row = $probe->snapshot($project);
		retention_check(($row['session_depth'] === 1) && ($row['retired_ast_roots'] === 0), 'Closed history retained');
		// One session plus three each of file roots, bodies, token lists and source snapshots.
		retention_check($probe->survivors() === 13, 'Old lifetime family survives collection');
		retention_check(($row['watchers'] === 2) && ($row['watch_relations'] === 2), 'Watch growth or loss');
		retention_check(($row['stale_watchers'] === 0) && ($row['pending_notifications'] === 0), 'Retired or pending watcher survived');
		retention_check(($row['pending_bodies'] === 0) && ($row['pending_emission_sources'] === 0), 'Successful work remains queued');
		retention_check(($row['symbol_entries'] === 2) && ($row['tombstones'] === 0), 'Same-key edits grew symbol inventory');
		$rows[] = ['phase' => $phase, 'step' => $step, 'php_memory_bytes' => $memory,
			'compiler_ms' => $compiler_ms, 'gc_ms' => $gc_ms] + $row;
	}
	unset($project);
	gc_collect_cycles();
	retention_check($probe->survivors() === 0, 'Probe or external root retained compilation objects after project release');
	return ['flavor' => $flavor, 'cycles' => $cycles, 'rows' => $rows, 'survivors_after_project_release' => 0];
}

$cycles = isset($argv[1]) ? filter_var($argv[1], \FILTER_VALIDATE_INT) : 12;
if (($cycles === false) || ($cycles < 1) || ($cycles > 1000)) {
	throw new \InvalidArgumentException('Usage: php run.php [cycles:1..1000]');
}
$root = sys_get_temp_dir() . '/scpp-retention-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	$report = [];
	foreach (['cpp', 'php', 'js'] as $flavor) {
		$report[$flavor] = retention_run($root . '/' . $flavor, $flavor, $cycles);
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
