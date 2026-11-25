<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';
require_once __DIR__ . '/inspect.php';

function retirement_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \RuntimeException($message);
	}
}

/**
 * Exercise publication retirement through the real executable coordinator, including failed generations.
 * @return mixed[]
 */
function retirement_case(string $app, string $flavor, string $kind): array /** list<array<string,mixed>> */
{
	mkdir($app);
	file_put_contents($app . '/module.json', '{}');
	$function = static fn(string $name, string $type, string $body): string => ($flavor === 'cpp'
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$provider = static fn(int $value): string => match ($kind) {
		'function' => $function('item', 'int', 'return ' . $value . ';'),
		'constant' => $flavor === 'js' ? "const ITEM: int = $value;" : "const int ITEM = $value;",
		'global' => match ($flavor) {
			'php' => 'global $item int = ' . $value . ';',
			'js' => "global let item: int = $value;",
			default => "global int item = $value;",
		},
	};
	$expression = match ($kind) {
		'function' => 'item()',
		'constant' => 'ITEM',
		'global' => $flavor === 'php' ? '$item' : 'item',
	};
	$key = match ($kind) {
		'function' => 'func:item',
		'constant' => 'const:ITEM',
		'global' => 'var:item',
	};
	$reader = $function('reader', 'void', 'echo ' . $expression . ';');
	$stamp = time();
	$write = static function (string $name, string $text) use ($app, $flavor, &$stamp): void {
		file_put_contents($app . '/' . $name . '.' . $flavor, $text);
		touch($app . '/' . $name . '.' . $flavor, ++$stamp);
	};
	$remove = static function (string $name) use ($app, $flavor): void {
		unlink($app . '/' . $name . '.' . $flavor);
	};
	$write('main', 'reader(); return 8;');
	$write('reader', $reader);
	$write('quiet', $function('quiet', 'void', ''));
	$write('a', $provider(8));
	$project = new Incremental_Project($app, $app . '/out', new entry_target(31));
	$probe = new Retention_Inspection();
	$slot = null;
	$quiet = null;
	$rows = [];
	/** Run one gate, asserting exact invalidation scope and no retired chain/observer/queue membership. */
	$run = static function (string $label, ?int $prepared, int $watchers, int $candidates,
		?string $failure = null) use ($project, $probe, $key, &$slot, &$quiet, &$rows): void
	{
		$message = null;
		$result = null;
		try {
			$result = $project->run();
		}
		catch (diagnostic_group $error) {
			$message = $error->getMessage();
			unset($error);
		}
		retirement_check($failure === null ? $message === null : (($message !== null) && str_contains($message, $failure)),
			$label . ': unexpected result: ' . ($message ?? 'success'));
		$session = $project->session();
		$slot ??= $session->scope->entries()[$key];
		retirement_check($session->scope->entries()[$key] === $slot, $label . ': stable entry changed');
		$quiet_body = $session->scope->resolve('func:quiet')->body();
		$quiet ??= \WeakReference::create($quiet_body);
		retirement_check(($quiet->get() === $quiet_body) && $quiet_body->is_prepared(), $label . ': quiet body lost');
		retirement_check($session->dependency_watches->observer_count($slot) === $watchers, $label . ': necessary watch lost/duplicated');
		$count = 0;
		for ($node = $session->scope->heads()[$key] ?? null; $node !== null; $node = $node->duplicate_element()) {
			retirement_check(++$count <= $candidates, $label . ': too many candidates or cycle');
		}
		retirement_check($count === $candidates, $label . ': wrong duplicate chain');
		if ($candidates === 0) {
			retirement_check(($slot->status === change_status::removed) && ($slot->declaration() === null),
				$label . ': removed symbol lost its tombstone');
		}
		if ($candidates > 1) {
			try {
				$slot->declaration();
				throw new \LogicException('Ambiguous declaration resolved');
			}
			catch (\RuntimeException $error) {
				retirement_check($error->getMessage() === 'Ambiguous symbol', 'Wrong ambiguity failure');
				unset($error);
			}
		}
		gc_collect_cycles();
		$snapshot = $probe->snapshot($project);
		retirement_check(($snapshot['stale_watchers'] === 0) && ($snapshot['pending_notifications'] === 0), $label . ': stale watcher');
		if ($failure === null) {
			retirement_check($result['preparation']['prepared'] === $prepared, $label . ': wrong prepared count');
			retirement_check(($snapshot['session_depth'] === 1) && ($snapshot['retired_ast_roots'] === 0), $label . ': retired syntax retained');
			retirement_check(($snapshot['pending_bodies'] === 0) && ($snapshot['pending_emission_sources'] === 0), $label . ': pending work');
			$families = [compilation_session::class, ast_file_node::class, ast_function_body::class, token_list::class, source_file::class];
			$live = array_sum(array_intersect_key($snapshot['class_counts'], array_flip($families)));
			retirement_check($probe->survivors() === $live, $label . ': old weak-observed object survived');
		}
		$rows[] = ['scenario' => $label, 'failure' => $failure, 'prepared' => $result['preparation']['prepared'] ?? null] + $snapshot;
	};
	$run('initial', $kind === 'function' ? 4 : 3, 1, 1);
	$write('a', $provider(9));
	$run('replace_provider', $kind === 'global' ? 0 : 1, 1, 1);
	$remove('a');
	$run('delete_provider', null, 1, 0, 'Unknown');
	$run('missing_retry', null, 1, 0, 'Unknown');
	$write('a', $provider(8));
	$run('readd_provider', $kind === 'function' ? 2 : 1, 1, 1);
	$write('b', $provider(10));
	$write('c', $provider(11));
	$run('three_duplicates', null, 1, 3, 'Duplicate');
	$write('b', $provider(12));
	$run('replace_middle_duplicate', null, 1, 3, 'Duplicate');
	$remove('b');
	$run('remove_middle_duplicate', null, 1, 2, 'Duplicate');
	$remove('a');
	$run('remove_head_recover', $kind === 'function' ? 2 : 1, 1, 1);
	$write('a', $provider(11));
	$remove('c');
	$run('move_provider', $kind === 'function' ? 1 : 0, 1, 1);
	$write('a', $provider(8) . $provider(9));
	$run('same_file_duplicates', null, 1, 2, 'Duplicate');
	$write('a', $provider(8));
	$run('remove_tail_recover', $kind === 'function' ? 2 : 1, 1, 1);
	$write('reader', $function('reader', 'void', 'echo 8;'));
	$run('replace_consumer', 1, 0, 1);
	$write('reader', $reader);
	$run('restore_consumer_watch', 1, 1, 1);
	$write('main', 'return 8;');
	$remove('reader');
	$run('delete_consumer', 1, 0, 1);
	$write('reader', $reader);
	$write('main', 'reader(); return 8;');
	$run('readd_consumer', 2, 1, 1);
	$remove('a');
	$remove('reader');
	$write('main', 'return 8;');
	$run('delete_provider_and_consumer', 1, 0, 0);
	$write('a', $provider(8));
	$write('reader', $reader);
	$write('main', 'reader(); return 8;');
	$run('readd_both', $kind === 'function' ? 3 : 2, 1, 1);
	$run('final_noop', 0, 1, 1);
	unset($run, $project, $slot);
	gc_collect_cycles();
	retirement_check($probe->survivors() === 0, 'Case retains compilation objects after release');
	return $rows;
}

$root = sys_get_temp_dir() . '/scpp-retirement-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	$report = [];
	foreach (['cpp', 'php', 'js'] as $flavor) {
		foreach (['function', 'global', 'constant'] as $kind) {
			$report[$flavor . '/' . $kind] = retirement_case($root . '/' . $flavor . '-' . $kind, $flavor, $kind);
		}
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
