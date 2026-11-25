<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Project_Compiler, Incremental_Preparation, entry_target, diagnostic, diagnostic_group,
	Function_Preparation};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require a source error, without consuming retry work. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

$root = sys_get_temp_dir() . '/scpp-preparation-work-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	foreach (['php', 'cpp', 'js'] as $flavor) {
		$app = $root . '/' . $flavor;
		mkdir($app);
		file_put_contents($app . '/module.json', '{}');
		$main = $app . '/main.' . $flavor;
		$library = $app . '/library.' . $flavor;
		$fn = static fn(string $name, string $body): string => ($flavor === 'cpp'
			? "int $name()" : "function $name(): int") . '{' . $body . '}';
		$constant = static fn(string $value): string => $flavor === 'js' ? "const LIMIT: int=$value;" : "const int LIMIT=$value;";
		file_put_contents($main, 'echo value(); echo LIMIT; echo LIMIT; return 8;');
		file_put_contents($library, $constant('8') . $fn('value', 'return 8;'));
		$compiler = new Project_Compiler();
		$target = new entry_target(31);
		$session = $compiler->frontend($app);
		$work = $session->preparation_work;
		$keys = [];
		foreach ($session->entry->sources as $unit) {
			$keys[$unit->path] = $session->source_key($unit);
		}
		$main_key = $keys['main.' . $flavor];
		$library_key = $keys['library.' . $flavor];
		verify(($work->body_count() === 2) && ($work->pending_count() === 2), 'Initial body inventory');
		verify(isset($work->pending()[$main_key], $work->pending()[$library_key]), 'Buckets did not use session source keys');
		// No preparation between frontend generations must not lose newly parsed work.
		$session = $compiler->frontend($app, $session);
		verify(($session->preparation_work === $work) && ($work->pending_count() === 2), 'No-op lost pending work');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 2, 'reused' => 0], 'Initial preparation');
		verify(($work->pending_count() === 0) && ($work->pending() === []), 'Successful bodies/buckets stayed queued');
		$caller = $session->entry->entry->ast->main()->body();
		$callee = $session->scope->resolve('func:value')->body();
		$slot = $session->scope->entry('const:LIMIT');
		verify($session->dependency_watches->observer_count($slot) === 1, 'Repeated reads duplicated subscriptions');
		$session->dependency_watches->changed($slot);
		$session->dependency_watches->changed($session->scope->entry('func:value'));
		$session->dependency_watches->dispatch();
		$caller->invalidate_dependencies();
		verify(($work->pending_count() === 1) && ($work->pending()[$main_key] ?? null)?->offsetExists($caller), 'Invalidation failed to deduplicate');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 1, 'reused' => 1], 'Queued consumer selection');
		$session = $compiler->frontend($app, $session);
		verify($work->pending_count() === 0, 'No-op queued prepared bodies');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 0, 'reused' => 2], 'No-op preparation');
		file_put_contents($library, $constant('80') . $fn('value', 'return 9;'));
		$session = $compiler->frontend($app, $session);
		verify($work->pending_count() === 2, 'Constant change missed consumer/new body');
		verify(!($work->pending()[$library_key] ?? null)?->offsetExists($callee), 'Retired body remained pending');
		$callee->invalidate_dependencies();
		verify($work->pending_count() === 2, 'Detached body rejoined queue');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 2, 'reused' => 0], 'Constant change preparation');
		verify($caller->statements[1]->values[0]->constant() === 80, 'Constant fact not refreshed');
		file_put_contents($library, $constant('80') . $fn('value', 'return 123;'));
		$session = $compiler->frontend($app, $session);
		verify(($work->pending_count() === 1) && !($work->pending()[$main_key] ?? null)?->offsetExists($caller), 'Body-only change queued consumer');
		Incremental_Preparation::prepare($session, $target);
		// A parse failure must leave the successful generation and its queue intact.
		$before = $session;
		file_put_contents($library, $fn('value', 'return'));
		rejects(fn() => $compiler->frontend($app, $session), 'Expected');
		verify($before->is_current && ($work->pending_count() === 0), 'Failed parse changed live work');
		// Failed bodies remain pending in the same generation and across a no-op frontend.
		file_put_contents($library, $constant('80') . $fn('value', 'return missing();'));
		$session = $compiler->frontend($app, $session);
		$retry = $session->scope->resolve('func:value')->body();
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unknown function');
		verify(($work->pending_count() === 1) && ($work->pending()[$library_key] ?? null)?->offsetExists($retry), 'Failed body lost retry');
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unknown function');
		$session = $compiler->frontend($app, $session);
		verify($work->pending_count() === 1, 'No-op dropped failed work');
		$added = $app . '/added.' . $flavor;
		file_put_contents($added, $fn('missing', 'return 7;'));
		$session = $compiler->frontend($app, $session);
		verify(($work->body_count() === 3) && ($work->pending_count() === 2), 'Missing-name recovery work');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 2, 'reused' => 1], 'Missing-name recovery');
		// Global declaration failure leaves new work queued, including bodies without any watches yet.
		file_put_contents($added, $constant('99') . $fn('missing', 'return 77;'));
		$session = $compiler->frontend($app, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Duplicate constant');
		verify($work->pending_count() >= 1, 'Validation failure consumed work');
		file_put_contents($added, $fn('missing', 'return 777;'));
		$session = $compiler->frontend($app, $session);
		Incremental_Preparation::prepare($session, $target);
		verify(($work->pending_count() === 0) && ($work->body_count() === 3), 'Duplicate recovery/retirement');
		// Add, replace, and remove an unprepared body with no dependency subscriptions.
		$unused = $app . '/unused.' . $flavor;
		file_put_contents($unused, $fn('unused', 'return 1;'));
		$session = $compiler->frontend($app, $session);
		$abandoned = $session->scope->resolve('func:unused')->body();
		$unused_key = $session->source_key($session->entry->sources['unused.' . $flavor]);
		file_put_contents($unused, $fn('unused', 'return 100;'));
		$session = $compiler->frontend($app, $session);
		verify(($work->body_count() === 4) && ($work->pending_count() === 1), 'Unprepared replacement count');
		verify(!($work->pending()[$unused_key] ?? null)?->offsetExists($abandoned), 'Unprepared replacement retained old body');
		unlink($unused);
		$session = $compiler->frontend($app, $session);
		verify(($work->body_count() === 3) && ($work->pending_count() === 0), 'Unprepared removal retained work');
		$abandoned->invalidate_dependencies();
		verify($work->pending_count() === 0, 'Removed body requeued itself');
		Function_Preparation::reset_body($caller);
		verify($work->pending_count() === 1, 'Explicit reset bypassed scheduling');
		Incremental_Preparation::prepare($session, $target);
		verify(Incremental_Preparation::prepare($session, new entry_target(15)) === ['prepared' => 1, 'reused' => 2], 'Entry target omitted');
		verify($work->pending_count() === 0, 'Target preparation stayed queued');
		// Multiple bodies in one unit: complete peers without losing failed work or mutating a snapshot.
		$grouped = $app . '/grouped.' . $flavor;
		file_put_contents($grouped, $fn('good', 'return 1;') . $fn('bad', 'return unknown();'));
		$session = $compiler->frontend($app, $session);
		$group_key = $session->source_key($session->entry->sources['grouped.' . $flavor]);
		$snapshot = $work->pending();
		verify((count($snapshot) === 1) && (count($snapshot[$group_key]) === 2), 'Bodies did not share one source bucket');
		rejects(fn() => Incremental_Preparation::prepare($session, new entry_target(15)), 'Unknown function');
		verify((count($work->pending()) === 1) && ($work->pending_count() === 1), 'Partial completion lost failed body');
		verify(count($snapshot[$group_key]) === 2, 'Completion mutated snapshot bucket');
		verify($session->scope->resolve('func:good')->body()->is_prepared(), 'Successful peer was not prepared');
		// Moving a failed source retires its old bucket; unchanged unit-record replacement keeps other keys stable.
		$moved = $app . '/moved.' . $flavor;
		rename($grouped, $moved);
		$session = $compiler->frontend($app, $session);
		$moved_key = $session->source_key($session->entry->sources['moved.' . $flavor]);
		verify(!isset($work->pending()[$group_key]) && isset($work->pending()[$moved_key]), 'Move retained obsolete source bucket');
		verify(($work->body_count() === 5) && ($work->pending_count() === 2), 'Move duplicated work membership');
		file_put_contents($moved, $fn('good', 'return 1;') . $fn('bad', 'return 100;'));
		$session = $compiler->frontend($app, $session);
		verify(Incremental_Preparation::prepare($session, new entry_target(15)) === ['prepared' => 2, 'reused' => 3], 'Grouped recovery');
		verify($work->pending() === [], 'Recovered bucket was not removed');
		unlink($moved);
		$session = $compiler->frontend($app, $session);
		verify(($work->body_count() === 3) && ($work->pending() === []), 'Removed grouped source retained work');

	}
}
finally {
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($iterator as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
echo "Preparation queue deduplication, selection, retry, retirement and recovery passed for all flavors\n";
