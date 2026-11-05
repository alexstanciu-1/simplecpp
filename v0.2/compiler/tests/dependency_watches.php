<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Dependency_Watches, dependency_watcher, symbol_entry};

/** Counts notifications without any AST dependency, proving that watcher granularity belongs to the consumer. */
final class Test_Watcher implements dependency_watcher
{
	public int $notifications /** uint32 */ = 0;
	private readonly string $source_key;

	public function __construct(string $source_key = 'app/a')
	{
		$this->source_key = $source_key;
	}

	public function dependency_source_key(): string
	{
		return $this->source_key;
	}

	public function invalidate_dependencies(): void
	{
		$this->notifications++;
	}
}

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

$watches = new Dependency_Watches();
$a = new symbol_entry();
$b = new symbol_entry();
$watcher = new Test_Watcher();
$other = new Test_Watcher('app/b');
$watches->watch($a, $watcher);
$watches->watch($a, $watcher);
$watches->watch($b, $watcher);
$watches->watch($a, $other);
verify($watches->observer_count($a) === 2, 'Repeated registration created another relation');
$watches->changed($a);
$watches->changed($b);
$watches->changed($a);
verify($watcher->notifications === 0, 'Notification escaped the publication batch');
$watches->dispatch();
verify(($watcher->notifications === 1) && ($other->notifications === 1), 'Batch did not deduplicate watchers');
$watches->dispatch();
verify($watcher->notifications === 1, 'Empty dispatch repeated a notification');
$watches->changed($b);
$watches->dispatch();
verify($watcher->notifications === 2, 'Next batch lost a notification');

$watches->changed($a);
$watches->forget($other);
$watches->dispatch();
verify(($other->notifications === 1) && ($watches->observer_count($a) === 1), 'Forget retained pending work or edges');
$watches->retain([]);
verify(($watches->observer_count($a) === 0) && ($watches->observer_count($b) === 0), 'Retirement retained edges');
$watches->dispatch();
verify($watcher->notifications === 4, 'Retirement must invalidate the removed cache once');
$watches->changed($a);
$watches->dispatch();
verify($watcher->notifications === 4, 'Retired watcher received later changes');

// Symbols may be tombstones; re-registering a rebuilt watcher must establish a fresh relation.
$watches->watch($a, $watcher);
$watches->retain([$watcher]);
$watches->changed($a);
$watches->dispatch();
verify($watcher->notifications === 5, 'Live watcher or re-registration lost');
$independent = new Dependency_Watches();
$independent->changed($a);
$independent->dispatch();
verify($watcher->notifications === 5, 'Separate coordinator leaked notifications');
$watches->forget($watcher);
$watches->forget($watcher);
$watches->forget(new Test_Watcher());
verify($watches->observer_count($a) === 0, 'Repeated forget was not harmless');
echo "Generic watcher registration, batching, retirement, cancellation and isolation passed\n";

// Group queries reveal membership, never the storage representation or mutable buckets.
$local = new Test_Watcher('app/a');
$local_peer = new Test_Watcher('app/a');
$foreign = new Test_Watcher('app/b');
$watches->watch($a, $local);
$watches->watch($a, $local_peer);
$watches->watch($a, $foreign);
$watches->watch($b, $foreign);
verify($watches->observer_count($a, 'app/a') === 2, 'Same-source watchers did not group');
verify($watches->observer_count($a, 'app/b') === 1, 'Foreign source count');
verify($watches->observer_count($a, 'missing') === 0, 'Missing bucket query');
verify($watches->has_external_observers($a, 'app/a'), 'External use missing');
$watches->forget($foreign);
verify(!$watches->has_external_observers($a, 'app/a'), 'Empty foreign bucket retained');
verify(!$watches->has_external_observers($b, 'app/a'), 'Empty symbol retained');
$watches->forget($local);
verify($watches->observer_count($a, 'app/a') === 1, 'Peer removed with its source bucket');
$watches->changed($a);
$watches->dispatch();
verify($local_peer->notifications === 1 && $local->notifications === 0, 'Grouped dispatch');
$watches->forget($local_peer);
verify($watches->observer_count($a) === 0, 'Final bucket retained');
echo "Grouped source membership, external-use queries and bucket removal passed\n";
