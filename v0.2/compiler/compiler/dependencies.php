<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Invalidation target, currently a body; grouped bodies or source units may implement the same contract later. */
interface dependency_watcher {
	/** Mark owned caches dirty only; do not prepare, throw or mutate subscriptions during dispatch. */
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
	 * Native non-owning symbol keys and watcher references; scopes and cache owners retain their lifetimes.
	 * @var \SplObjectStorage<symbol_entry,\SplObjectStorage<dependency_watcher,null>>
	 */
	private \SplObjectStorage $observers /** SplObjectStorage<symbol_entry,SplObjectStorage<dependency_watcher,null>> */;
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
		if (!$this->observers->offsetExists($symbol)) {
			$this->observers[$symbol] = new \SplObjectStorage();
		}
		if (!$this->targets->offsetExists($watcher)) {
			$this->targets[$watcher] = new \SplObjectStorage();
		}
		$this->observers[$symbol][$watcher] = null;
		$this->targets[$watcher][$symbol] = null;
	}

	/** Remove all subscriptions and any queued notification before rebuilding or abandoning a watcher. */
	public function forget(dependency_watcher $watcher): void
	{
		$this->pending->offsetUnset($watcher);
		if (!$this->targets->offsetExists($watcher)) {
			return;
		}
		foreach ($this->targets[$watcher] as $symbol) {
			/** @var symbol_entry $symbol */
			$this->observers[$symbol]->offsetUnset($watcher);
			if (count($this->observers[$symbol]) === 0) {
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
		foreach ($this->observers[$symbol] as $watcher) {
			/** @var dependency_watcher $watcher */
			$this->pending[$watcher] = null;
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
			$this->forget($watcher);
			$this->pending[$watcher] = null;
		}
	}

	public function observer_count(symbol_entry $symbol): int /** uint32 */
	{
		return $this->observers->offsetExists($symbol) ? count($this->observers[$symbol]) : 0;
	}
}
