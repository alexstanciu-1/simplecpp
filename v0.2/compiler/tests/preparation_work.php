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
		verify(($work->body_count() === 2) && ($work->pending_count() === 2), 'Initial body inventory');
		// No preparation between frontend generations must not lose newly parsed work.
		$session = $compiler->frontend($app, $session);
		verify(($session->preparation_work === $work) && ($work->pending_count() === 2), 'No-op lost pending work');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 2, 'reused' => 0], 'Initial preparation');
		verify($work->pending_count() === 0, 'Successful bodies stayed queued');
		$caller = $session->entry->entry->ast->main()->body();
		$callee = $session->scope->resolve('func:value')->body();
		$slot = $session->scope->entry('const:LIMIT');
		verify($session->dependency_watches->observer_count($slot) === 1, 'Repeated reads duplicated subscriptions');
		$session->dependency_watches->changed($slot);
		$session->dependency_watches->changed($session->scope->entry('func:value'));
		$session->dependency_watches->dispatch();
		$caller->invalidate_dependencies();
		verify(($work->pending_count() === 1) && $work->pending()->offsetExists($caller), 'Invalidation failed to deduplicate');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 1, 'reused' => 1], 'Queued consumer selection');
		$session = $compiler->frontend($app, $session);
		verify($work->pending_count() === 0, 'No-op queued prepared bodies');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 0, 'reused' => 2], 'No-op preparation');
		file_put_contents($library, $constant('80') . $fn('value', 'return 9;'));
		$session = $compiler->frontend($app, $session);
		verify($work->pending_count() === 2, 'Constant change missed consumer/new body');
		verify(!$work->pending()->offsetExists($callee), 'Retired body remained pending');
		$callee->invalidate_dependencies();
		verify($work->pending_count() === 2, 'Detached body rejoined queue');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 2, 'reused' => 0], 'Constant change preparation');
		verify($caller->statements[1]->value->constant() === 80, 'Constant fact not refreshed');
		file_put_contents($library, $constant('80') . $fn('value', 'return 123;'));
		$session = $compiler->frontend($app, $session);
		verify(($work->pending_count() === 1) && !$work->pending()->offsetExists($caller), 'Body-only change queued consumer');
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
		verify(($work->pending_count() === 1) && $work->pending()->offsetExists($retry), 'Failed body lost retry');
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
		file_put_contents($unused, $fn('unused', 'return 100;'));
		$session = $compiler->frontend($app, $session);
		verify(($work->body_count() === 4) && ($work->pending_count() === 1), 'Unprepared replacement count');
		verify(!$work->pending()->offsetExists($abandoned), 'Unprepared replacement retained old body');
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
