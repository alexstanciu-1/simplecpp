<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Project_Compiler, Incremental_Preparation, entry_target, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

$root = sys_get_temp_dir() . '/scpp-grouped-observers-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	foreach (['cpp', 'php', 'js'] as $flavor) {
		$app = $root . '/' . $flavor;
		mkdir($app);
		file_put_contents($app . '/module.json', '{}');
		$fn = static fn(string $name, string $body): string => ($flavor === 'cpp'
			? "void $name()" : "function $name(): void") . '{' . $body . '}';
		$main = $app . '/main.' . $flavor;
		$library = $app . '/library.' . $flavor;
		$foreign = $app . '/foreign.' . $flavor;
		$local = $fn('local', 'value();') . $fn('local_peer', 'value();');
		file_put_contents($main, 'value(); value(); return 8;');
		file_put_contents($library, $fn('value', '') . $local);
		file_put_contents($foreign, $fn('other', 'value();'));
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($app);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$watches = $session->dependency_watches;
		$slot = $session->scope->entry('func:value');
		$library_key = $session->scope->resolve('func:local')->body()->dependency_source_key();
		$main_key = $session->entry_key();
		$foreign_key = $session->scope->resolve('func:other')->body()->dependency_source_key();
		verify($watches->observer_count($slot) === 4, 'Repeated occurrence was not deduplicated');
		verify($watches->observer_count($slot, $library_key) === 2, 'Same-unit bodies not grouped');
		verify($watches->observer_count($slot, $main_key) === 1, 'Entry source key mismatch');
		verify($watches->has_external_observers($slot, $library_key), 'External readers missing');
		$session = $compiler->frontend($app, $session);
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 0, 'reused' => 5], 'No-op invalidated bodies');
		verify($watches->observer_count($slot) === 4, 'No-op lost retained groups');
		// Movement retires the old source bucket and registers the replacement under the new key.
		$moved = $app . '/moved.' . $flavor;
		rename($foreign, $moved);
		$session = $compiler->frontend($app, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Move preparation scope');
		$moved_key = $session->scope->resolve('func:other')->body()->dependency_source_key();
		verify($watches->observer_count($slot, $foreign_key) === 0, 'Retired source bucket survived');
		verify($watches->observer_count($slot, $moved_key) === 1, 'Moved source lost subscription');
		unlink($moved);
		file_put_contents($main, 'return 8;');
		$session = $compiler->frontend($app, $session);
		Incremental_Preparation::prepare($session, $target);
		verify($watches->observer_count($slot) === 2 && !$watches->has_external_observers($slot, $library_key),
			'Last external reader removal left external use');
		file_put_contents($library, $fn('value', '') . 'function');
		try {
			$compiler->frontend($app, $session);
			throw new RuntimeException('Broken parse accepted');
		}
		catch (diagnostic) {
		}
		verify($session->is_current && $watches->observer_count($slot, $library_key) === 2, 'Parse failure mutated groups');
		file_put_contents($library, $local);
		file_put_contents($main, 'value(); return 8;');
		$session = $compiler->frontend($app, $session);
		for ($retry = 0; $retry < 2; $retry++) {
			try {
				Incremental_Preparation::prepare($session, $target);
				throw new RuntimeException('Missing function accepted');
			}
			catch (diagnostic_group $error) {
				verify(str_contains($error->getMessage(), 'Unknown function: value'), 'Unexpected diagnostic');
			}
			verify($watches->observer_count($slot) === 3, 'Failed retries duplicated or lost subscriptions');
		}
		file_put_contents($library, $fn('value', '') . $local);
		$session = $compiler->frontend($app, $session);
		verify($session->scope->entry('func:value') === $slot, 'Recovery replaced stable symbol');
		verify(Incremental_Preparation::prepare($session, $target) === ['prepared' => 4, 'reused' => 0], 'Recovery work selection');
		verify($watches->observer_count($slot, $library_key) === 2 && $watches->observer_count($slot, $main_key) === 1,
			'Recovery did not restore source grouping');
		verify($watches->has_external_observers($slot, $library_key), 'Recovered external use missing');
	}
}
finally {
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
echo "Grouped observers: source reuse, movement, removal, parse failure, tombstones and recovery passed for all flavors\n";
