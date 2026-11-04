<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Project_Compiler, Incremental_Preparation, entry_target, change_status, diagnostic_group};
function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}
$root = sys_get_temp_dir() . '/scpp-stable-symbols-' . bin2hex(random_bytes(8));
mkdir($root);
try
{
	foreach (['php', 'cpp', 'js'] as $flavor)
	{
		$app = $root . '/' . $flavor;
		mkdir($app);
		file_put_contents($app . '/module.json', '{}');
		$head = static fn(string $name, string $type = 'void'): string => $flavor === 'cpp' ? "$type $name()" : "function $name(): $type";
		$main = $app . '/main.' . $flavor;
		$first = $app . '/first.' . $flavor;
		$second = $app . '/second.' . $flavor;
		file_put_contents($main, 'first(); return 8;');
		file_put_contents($first, $head('first') . '{ second(); }');
		file_put_contents($second, $head('second') . '{}');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($app);
		$target = new entry_target(31);
		/** Return: test report. */
		$prepare = static fn(\scpp\compiler\compilation_session $session): array /** array<string,mixed> */ => Incremental_Preparation::prepare($session,
			$target);
		verify($prepare($session) === ['prepared' => 3, 'reused' => 0], 'Initial cache construction');
		$symbols = $session->scope;
		$slot = $symbols->entry('func:second');
		$first_body = $symbols->resolve('func:first')->body();
		/** @var \scpp\compiler\ast_call_statement $call */
		$call = $first_body->statements[0];
		$old_second = $call->function_def();
		verify($session->dependency_watches->observer_count($slot) === 1, 'Missing signature subscription');
		$old_session = $session;
		$session = $compiler->frontend($app, $session);
		try {
			$prepare($old_session);
			throw new RuntimeException('Old session prepared against live index');
		}
		catch (LogicException) {
		}
		try {
			$compiler->frontend($app, $old_session);
			throw new RuntimeException('Old session used as baseline');
		}
		catch (LogicException) {
		}
		verify($prepare($session) === ['prepared' => 0, 'reused' => 3], 'Unchanged caches rebuilt');
		file_put_contents($second, $head('second') . '{ echo "changed"; }');
		$session = $compiler->frontend($app, $session);
		verify(($session->scope === $symbols) && ($symbols->entry('func:second') === $slot),
			'Stable identity replaced');
		verify(($slot->status === change_status::changed) && $first_body->is_prepared(),
			'Body edit invalidated caller');
		verify($prepare($session) === ['prepared' => 1, 'reused' => 2], 'Body-only preparation scope');
		verify(($call->function_def() !== $old_second) && ($call->function_def() === $slot->declaration()),
			'Cached call did not follow new destination');
		rename($second, $app . '/moved.' . $flavor);
		$second = $app . '/moved.' . $flavor;
		$session = $compiler->frontend($app, $session);
		verify(($symbols->entry('func:second') === $slot) && $first_body->is_prepared(),
			'Move invalidated unchanged contract');
		verify($prepare($session) === ['prepared' => 1, 'reused' => 2], 'Moved body preparation');
		unlink($second);
		$session = $compiler->frontend($app, $session);
		verify(($slot->status === change_status::removed) && ($slot->declaration() === null) && (!$first_body->is_prepared()),
			'Removal failed to invalidate');
		try {
			$call->function_def();
			throw new RuntimeException('Dirty call exposed cache');
		}
		catch (LogicException) {
		}
		try {
			$prepare($session);
			throw new RuntimeException('Missing callee accepted');
		}
		catch (diagnostic_group $error) {
			verify(str_contains($error->getMessage(), 'Unknown function: second'),
				'Missing callee diagnostic');
		}
		verify($session->dependency_watches->observer_count($slot) === 1, 'Removed symbol lost observer');
		file_put_contents($second, $head('second') . '{ return; }');
		$session = $compiler->frontend($app, $session);
		verify(($symbols->entry('func:second') === $slot) && ($slot->status === change_status::added),
			'Reactivation lost identity');
		verify($prepare($session) === ['prepared' => 2, 'reused' => 1], 'Reactivation preparation scope');
		if ($flavor !== 'cpp')
		{
			file_put_contents($second, $head('second', 'int') . '{ return; }');
			$session = $compiler->frontend($app, $session);
			verify(!$first_body->is_prepared(), 'Signature field change not detected');
			try {
				$prepare($session);
				throw new RuntimeException('Unsupported signature accepted');
			}
			catch (scpp\compiler\diagnostic|scpp\compiler\diagnostic_group $error) {
				verify(str_contains($error->getMessage(), 'requires a value return'), 'Signature diagnostic');
			}
			file_put_contents($second, $head('second') . '{}');
			$session = $compiler->frontend($app, $session);
			verify($prepare($session) === ['prepared' => 2, 'reused' => 1], 'Signature repair');
		}
		/** @var \scpp\compiler\ast_function_declaration $current_declaration */
		$current_declaration = $slot->declaration();
		file_put_contents($second, $head('second') . '{');
		try {
			$compiler->frontend($app, $session);
			throw new LogicException('Invalid frontend published');
		}
		catch (scpp\compiler\diagnostic) {
		}
		verify(($slot->declaration() === $current_declaration) && $first_body->is_prepared(),
			'Failed frontend mutated publication/cache');
		file_put_contents($second, $head('other') . '{}');
		$session = $compiler->frontend($app, $session);
		$other = $symbols->entry('func:other');
		verify(($slot->status === change_status::removed) && ($other->status === change_status::added),
			'Rename is not remove/add');
		file_put_contents($first, $head('first') . '{ other(); }');
		$session = $compiler->frontend($app, $session);
		verify(($session->dependency_watches->observer_count($slot) === 0) && (!$first_body->is_prepared()),
			'Replaced body retained subscription/cache readiness');
		verify($prepare($session) === ['prepared' => 2, 'reused' => 1], 'Rename/observer replacement');
		verify($session->dependency_watches->observer_count($other) === 1,
			'Replacement body failed to subscribe');
		$duplicate = $app . '/duplicate.' . $flavor;
		file_put_contents($duplicate, $head('other') . '{}');
		$session = $compiler->frontend($app, $session);
		verify(!$symbols->resolve('func:first')->body()->is_prepared(),
			'Ambiguity failed to invalidate observer');
		try {
			$prepare($session);
			throw new RuntimeException('Duplicates accepted');
		}
		catch (diagnostic_group) {
		}
		unlink($duplicate);
		$session = $compiler->frontend($app, $session);
		verify($prepare($session) === ['prepared' => 1, 'reused' => 2], 'Ambiguity recovery');
		verify(Incremental_Preparation::prepare($session, new entry_target(15)) === ['prepared' => 1, 'reused' => 2],
			'Entry target cache context ignored');
		// A never-declared name also gets a stable identity and subscription during failed preparation.
		file_put_contents($first, $head('first') . '{ absent(); }');
		$session = $compiler->frontend($app, $session);
		try {
			$prepare($session);
			throw new RuntimeException('Unknown name accepted');
		}
		catch (diagnostic_group) {
		}
		$absent = $symbols->entry('func:absent');
		verify($session->dependency_watches->observer_count($absent) === 1, 'Unresolved lookup not watched');
		file_put_contents($second, $head('absent') . '{}');
		$session = $compiler->frontend($app, $session);
		verify($symbols->entry('func:absent') === $absent, 'First declaration replaced unresolved identity');
		verify($prepare($session) === ['prepared' => 2, 'reused' => 1], 'Unknown-name repair');
		unlink($first);
		$session = $compiler->frontend($app, $session);
		verify($session->dependency_watches->observer_count($absent) === 0,
			'Removed observer retained subscription');
	}
}
finally
{
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($iterator as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
echo "Stable symbols and role-based body caches passed for all flavors\n";
