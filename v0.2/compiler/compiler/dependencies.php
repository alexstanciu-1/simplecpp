<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Invalidation target for prepared bodies and inline struct layouts. */
interface dependency_watcher {
	/** Stable source identity for the lifetime of registered subscriptions; forget before changing ownership. */
	public function dependency_source_key(): string;

	/** Invalidate owned caches and enqueue work; do not prepare, throw or mutate subscriptions during dispatch. */
	public function invalidate_dependencies(): void;
}

/** Optional emission requirements on an existing symbol/watch relation; native uint8 mask bits. */
enum dependency_use: int
{
	case enum_from_value = 1;
	case enum_name = 2;
	case enum_value = 4;
	case async_wait = 16;
	case async_task = 32;
	case reordered_base = 64;
	case enum_try_from_value = 8;

	public const array ordered_uses /** dependency_use[] */ = [self::enum_from_value, self::enum_name, self::enum_value, self::enum_try_from_value];
}

/**
 * Session-owned dependency relations and batched invalidation, independent of function contracts and AST traversal.
 * Example: watch($symbol, $body); changed($symbol); dispatch() invalidates that body once per batch.
 */
final class Dependency_Watches
{
	/** CONSTANTS */
	/** Subscription mask for source-name binding, separate from enum helper emission uses. */
	public const int PARAMETER_NAMES = 16;

	/** PROPERTIES */
	/**
	 * Native non-owning symbols/watchers, grouped by stable source key. Owning buckets never escape this coordinator.
	 * @var \SplObjectStorage<symbol_entry,\ArrayObject<string,\SplObjectStorage<dependency_watcher,int>>>
	 */
	private \SplObjectStorage $observers /** SplObjectStorage<symbol_entry,ArrayObject<string,SplObjectStorage<dependency_watcher,uint8>>> */;
	/**
	 * Native non-owning reverse index for watcher retirement and subscription replacement.
	 * @var \SplObjectStorage<dependency_watcher,\SplObjectStorage<symbol_entry,bool>>
	 */
	private \SplObjectStorage $targets /** SplObjectStorage<dependency_watcher,SplObjectStorage<symbol_entry,bool>> */;
	/**
	 * Native non-owning pending notifications; retired watchers must remain alive until dispatch completes.
	 * @var \SplObjectStorage<dependency_watcher,null>
	 */
	private \SplObjectStorage $pending /** SplObjectStorage<dependency_watcher,null> */;

	/** @var array<int,ast_function_body> Roots containing named or concrete closure reference returns. */
	private array $reference_callers = [];
	private bool $reference_cycles_checked = false;
	/** Edges involving a concrete closure, keyed by the actual calling body. */
	private ?\WeakMap $closure_edges /** WeakMap<ast_node,WeakMap<ast_node|symbol_entry,bool>>|null */ = null;

	/** Session-owned demand graph, allocated only by programs that call static methods. */
	private ?Called_Methods $called_method_cache = null;

	/** CONSTRUCTOR */
	public function __construct()
	{
		$this->observers = new \SplObjectStorage();
		$this->targets = new \SplObjectStorage();
		$this->pending = new \SplObjectStorage();
	}

	/** METHODS */
	/** Register the caller's demand without inspecting or preparing foreign syntax. */
	public function static_call(ast_function_body $caller, symbol_entry $method, symbol_entry $called): called_method_instance
	{
		$cache = $this->called_method_cache;
		if ($cache === null) {
			$cache = new Called_Methods();
			$this->called_method_cache = $cache;
		}
		return $cache->request($caller, $method, $called);
	}

	/** Observe the existing demand graph without creating it. */
	public function called_methods(): ?Called_Methods
	{
		return $this->called_method_cache;
	}

	/** Register one identity pair; repeated reads of the same symbol do not allocate additional relations. */
	public function watch(symbol_entry $symbol, dependency_watcher $watcher, int $uses /** uint8 */ = 0): void
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
		$group = $groups[$source_key];
		$group[$watcher] = ($group->offsetExists($watcher) ? $group[$watcher] : 0) | $uses;
		$targets = $this->targets[$watcher];
		if (!$targets->offsetExists($symbol)) $targets[$symbol] = false;
	}

	/** Register the ordinary symbol watch and distinguish an invocation from a name lookup. */
	public function watch_call(symbol_entry $symbol, ast_function_body $caller, int $uses /** uint8 */ = 0): void
	{
		$this->watch($symbol, $caller, $uses);
		$this->targets[$caller][$symbol] = true;
		$this->reference_cycles_checked = false;
		if ($caller->parent->returns_reference) $this->reference_callers[spl_object_id($caller)] = $caller;
	}

	/** Replace outgoing edges before re-preparing a retained closure. Its enclosing body owns the incremental root. */
	public function begin_lambda(ast_function_body $owner, ast_lambda_expression $lambda): void
	{
		if ($this->closure_edges !== null) unset($this->closure_edges[$lambda]);
		if ($lambda->returns_reference) {
			// Full publication retires watchers through the reverse index, even for a symbol-free body.
			if (!$this->targets->offsetExists($owner)) $this->targets[$owner] = new \SplObjectStorage();
			$this->reference_callers[spl_object_id($owner)] = $owner;
			$this->reference_cycles_checked = false;
		}
	}

	/** Only an invocation connects the caller to a concrete closure or its body to a named callee. */
	public function closure_call(ast_function_body|ast_lambda_expression $caller, ast_lambda_expression|symbol_entry $target): void
	{
		$this->closure_edges ??= new \WeakMap();
		$edges = $this->closure_edges[$caller] ??= new \WeakMap();
		$edges[$target] = true;
		$this->reference_cycles_checked = false;
	}

	/** Check direct calls reachable from reference-returning functions. Removing edges cannot create a cycle. */
	public function validate_reference_calls(): void
	{
		if ($this->reference_cycles_checked || $this->reference_callers === []) return;
		$cycles = new Reference_Call_Cycles($this->targets, $this->closure_edges);
		$cycles->validate($this->reference_callers);
		$this->reference_cycles_checked = true;
	}

	/** Replace a signature in its existing source bucket before delivering this publication's changes. */
	public function replace_signature(ast_function_declaration $old, ast_function_declaration $new): void
	{
		if ($this->targets->offsetExists($old)) {
			$key = $old->dependency_source_key();
			$targets = $this->targets[$old];
			foreach ($targets as $symbol) {
				$group = $this->observers[$symbol][$key];
				$group[$new] = $group[$old];
				$group->offsetUnset($old);
			}
			$this->targets[$new] = $targets;
			$this->targets->offsetUnset($old);
		}
		if ($this->pending->offsetExists($old)) {
			$this->pending[$new] = null;
			$this->pending->offsetUnset($old);
		}
	}

	/** Query a requirement without exposing buckets or inspecting foreign executable ASTs. */
	public function has_use(symbol_entry $symbol, dependency_use $use): bool
	{
		if (!$this->observers->offsetExists($symbol)) return false;
		foreach ($this->observers[$symbol] as $group) {
			foreach ($group as $watcher) {
				if (($group[$watcher] & $use->value) !== 0) return true;
			}
		}
		return false;
	}

	/** Remove all subscriptions and any queued notification before rebuilding or abandoning a watcher. */
	public function forget(dependency_watcher $watcher): void
	{
		unset($this->reference_callers[spl_object_id($watcher)]);
		if ($this->closure_edges !== null) unset($this->closure_edges[$watcher]);
		$cache = $this->called_method_cache;
		if ($watcher instanceof ast_function_body && $cache !== null) $cache->forget($watcher);
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
	public function changed(symbol_entry $symbol, int $only /** uint8 */ = 0): void
	{
		if (!$this->observers->offsetExists($symbol)) {
			return;
		}
		foreach ($this->observers[$symbol] as $watchers) {
			foreach ($watchers as $watcher) {
				/** @var dependency_watcher $watcher */
				if ($only !== 0 && ($watchers[$watcher] & $only) === 0) continue;
				$this->pending[$watcher] = null;
			}
		}
	}

	/** Deliver once per watcher, including notifications relayed by embedded layouts. */
	public function dispatch(): void
	{
		if (count($this->pending) === 0) return;
		$cache = $this->called_method_cache;
		if ($cache !== null) $cache->invalidate();
		$delivered = new \SplObjectStorage();
		while (count($this->pending) !== 0) {
			$pending = $this->pending;
			$this->pending = new \SplObjectStorage();
			foreach ($pending as $watcher) {
				/** @var dependency_watcher $watcher */
				if (!$delivered->offsetExists($watcher)) {
					$delivered[$watcher] = null;
					$watcher->invalidate_dependencies();
				}
			}
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

/** Tarjan's strongly connected components over the current generation's resolved free-function and final instance-method calls. */
final class Reference_Call_Cycles
{
	private array $order = [];
	private array $active = [];
	private array $stack = [];
	private array $errors = [];

	public function __construct(private readonly \SplObjectStorage $targets, private readonly ?\WeakMap $closure_edges) {}

	public function validate(array $roots): void
	{
		foreach ($roots as $body) {
			if (!isset($this->order[spl_object_id($body)])) $this->visit($body);
		}
		if ($this->errors !== []) throw new diagnostic_group(Declaration_Diagnostics::ordered($this->errors));
	}

	/** Resolve current-generation bodies lazily from watched symbols and concrete lexical invocations. */
	private function callees(ast_function_body|ast_lambda_expression $body): \Generator
	{
		$targets = $this->targets[$body] ?? null;
		if ($targets !== null) foreach ($targets as $symbol) {
			if ($targets[$symbol] && ($callee = $symbol->declaration()?->optional_body()) !== null) yield $callee;
		}
		foreach ($this->closure_edges[$body] ?? [] as $target => $_) {
			$callee = $target instanceof ast_lambda_expression ? $target : $target->declaration()?->optional_body();
			if ($callee !== null) yield $callee;
		}
	}

	private function visit(ast_function_body|ast_lambda_expression $body): int
	{
		$id = spl_object_id($body);
		$low = $this->order[$id] = count($this->order);
		$this->stack[] = $body;
		$this->active[$id] = true;
		$self_call = false;
		foreach ($this->callees($body) as $callee) {
			$next = spl_object_id($callee);
			if ($next === $id) $self_call = true;
			if (!isset($this->order[$next])) $low = min($low, $this->visit($callee));
			elseif (isset($this->active[$next])) $low = min($low, $this->order[$next]);
		}
		if ($low === $this->order[$id]) {
			$cyclic = $self_call || end($this->stack) !== $body;
			do {
				$member = array_pop($this->stack);
				unset($this->active[spl_object_id($member)]);
				$function = $member instanceof ast_lambda_expression ? $member : $member->parent;
				if ($cyclic && $function->returns_reference) {
					$this->errors[] = new diagnostic($function->source(), $function->byte_start(),
						'Recursive reference return is unsupported: ' . ($function instanceof ast_lambda_expression ? 'closure' : $function->name()));
				}
			} while ($member !== $body);
		}
		return $low;
	}
}
