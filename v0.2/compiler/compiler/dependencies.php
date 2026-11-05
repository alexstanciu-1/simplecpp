<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Invalidation target, currently a body; grouped bodies or source units may implement the same contract later. */
interface dependency_watcher {
	/** Stable source identity for the lifetime of registered subscriptions; forget before changing ownership. */
	public function dependency_source_key(): string;

	/** Invalidate owned caches and enqueue work; do not prepare, throw or mutate subscriptions during dispatch. */
	public function invalidate_dependencies(): void;
}

/**
 * Session-owned dependency relations and batched invalidation, independent of function contracts and AST traversal.
 * Example: watch($symbol, $body); changed($symbol); dispatch() invalidates that body once per batch.
 */
final class Dependency_Watches
{
	/** PROPERTIES */
	/**
	 * Native non-owning symbols/watchers, grouped by stable source key. Owning buckets never escape this coordinator.
	 * @var \SplObjectStorage<symbol_entry,\ArrayObject<string,\SplObjectStorage<dependency_watcher,null>>>
	 */
	private \SplObjectStorage $observers /** SplObjectStorage<symbol_entry,ArrayObject<string,SplObjectStorage<dependency_watcher,null>>> */;
	/**
	 * Native non-owning reverse index for watcher retirement and subscription replacement.
	 * @var \SplObjectStorage<dependency_watcher,\SplObjectStorage<symbol_entry,null>>
	 */
	private \SplObjectStorage $targets /** SplObjectStorage<dependency_watcher,SplObjectStorage<symbol_entry,null>> */;
	/**
	 * Native non-owning pending notifications; retired watchers must remain alive until dispatch completes.
	 * @var \SplObjectStorage<dependency_watcher,null>
	 */
	private \SplObjectStorage $pending /** SplObjectStorage<dependency_watcher,null> */;

	/** CONSTRUCTOR */
	public function __construct()
	{
		$this->observers = new \SplObjectStorage();
		$this->targets = new \SplObjectStorage();
		$this->pending = new \SplObjectStorage();
	}

	/** METHODS */
	/** Register one identity pair; repeated reads of the same symbol do not allocate additional relations. */
	public function watch(symbol_entry $symbol, dependency_watcher $watcher): void
	{
		$source_key = $watcher->dependency_source_key();
		if ($source_key === '') {
			throw new \LogicException('A dependency watcher requires a source key');
		}
		if (!$this->observers->offsetExists($symbol)) {
			$this->observers[$symbol] = new \ArrayObject();
		}
		$groups = $this->observers[$symbol];
		if (!isset($groups[$source_key])) {
			$groups[$source_key] = new \SplObjectStorage();
		}
		if (!$this->targets->offsetExists($watcher)) {
			$this->targets[$watcher] = new \SplObjectStorage();
		}
		$groups[$source_key][$watcher] = null;
		$this->targets[$watcher][$symbol] = null;
	}

	/** Remove all subscriptions and any queued notification before rebuilding or abandoning a watcher. */
	public function forget(dependency_watcher $watcher): void
	{
		$this->pending->offsetUnset($watcher);
		if (!$this->targets->offsetExists($watcher)) {
			return;
		}
		$source_key = $watcher->dependency_source_key();
		foreach ($this->targets[$watcher] as $symbol) {
			/** @var symbol_entry $symbol */
			$groups = $this->observers[$symbol];
			$groups[$source_key]->offsetUnset($watcher);
			if (count($groups[$source_key]) === 0) {
				unset($groups[$source_key]);
			}
			if (count($groups) === 0) {
				$this->observers->offsetUnset($symbol);
			}
		}
		$this->targets->offsetUnset($watcher);
	}

	/** Queue observers; publication decides which semantic changes matter and explicitly ends the batch. */
	public function changed(symbol_entry $symbol): void
	{
		if (!$this->observers->offsetExists($symbol)) {
			return;
		}
		foreach ($this->observers[$symbol] as $watchers) {
			foreach ($watchers as $watcher) {
				/** @var dependency_watcher $watcher */
				$this->pending[$watcher] = null;
			}
		}
	}

	/** Deliver once per watcher, even when several observed symbols changed; callbacks only invalidate caches. */
	public function dispatch(): void
	{
		$pending = $this->pending;
		$this->pending = new \SplObjectStorage();
		foreach ($pending as $watcher) {
			/** @var dependency_watcher $watcher */
			$watcher->invalidate_dependencies();
		}
	}

	/** Retire a known replaced/removed observer without scanning the live watcher inventory. */
	public function retire(dependency_watcher $watcher): void
	{
		$this->forget($watcher);
		$this->pending[$watcher] = null;
	}

	/**
	 * Retire absent observers, queuing one final invalidation before publication returns.
	 * @param dependency_watcher[] $watchers Complete live inventory supplied by the publication owner.
	 */
	public function retain(array $watchers /** list<dependency_watcher> */): void
	{
		$live = new \SplObjectStorage();
		foreach ($watchers as $watcher) {
			$live[$watcher] = null;
		}
		/** @var dependency_watcher[] $removed */
		$removed = [];
		foreach ($this->targets as $watcher) {
			/** @var dependency_watcher $watcher */
			if (!$live->offsetExists($watcher)) {
				$removed[] = $watcher;
			}
		}
		foreach ($removed as $watcher) {
			$this->retire($watcher);
		}
	}

	/** Query counts without exposing mutable buckets; null counts observers across all sources. */
	public function observer_count(symbol_entry $symbol, ?string $source_key = null): int /** uint32 */
	{
		if (!$this->observers->offsetExists($symbol)) {
			return 0;
		}
		$groups = $this->observers[$symbol];
		if ($source_key !== null) {
			return isset($groups[$source_key]) ? count($groups[$source_key]) : 0;
		}
		$count = 0;
		foreach ($groups as $watchers) {
			$count += count($watchers);
		}
		return $count;
	}

	/** Use after successful preparation; absent watches before preparation do not prove absence of external use. */
	public function has_external_observers(symbol_entry $symbol, string $declaring_source_key): bool
	{
		if (!$this->observers->offsetExists($symbol)) {
			return false;
		}
		foreach ($this->observers[$symbol] as $source_key => $_) {
			if ((string) $source_key !== $declaring_source_key) {
				return true;
			}
		}
		return false;
	}
}
