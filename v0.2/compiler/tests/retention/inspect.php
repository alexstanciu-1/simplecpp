<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Test-only read-only graph inspection; weak observations never own the compiler objects they measure. */
final class Retention_Inspection
{
	/** PROPERTIES */
	/** @var \WeakMap<object,string> Weak keys identify previously observed lifetime families. */
	private \WeakMap $observed;

	/** CONSTRUCTOR */
	public function __construct()
	{
		$this->observed = new \WeakMap();
	}


	/** METHODS */
	/**
	 * Traverse strong instance references, including storage keys/data; exclude static roots and weak references.
	 * @return int[]
	 */
	private function graph(object $root): array /** array<string,uint64> */
	{
		$seen = new \SplObjectStorage();
		$stack = [$root];
		$counts = [];
		while ($stack !== [])
		{
			$value = array_pop($stack);
			if (is_array($value)) {
				foreach ($value as $child) {
					$stack[] = $child;
				}
				continue;
			}
			if (!is_object($value) || $seen->contains($value)) {
				continue;
			}
			$seen->attach($value);
			$class = get_class($value);
			$counts[$class] = ($counts[$class] ?? 0) + 1;
			if (($value instanceof compilation_session) || ($value instanceof ast_file_node)
				|| ($value instanceof ast_function_body) || ($value instanceof token_list) || ($value instanceof source_file)) {
				if (!isset($this->observed[$value])) {
					$this->observed[$value] = $class;
				}
			}
			if ($value instanceof \SplObjectStorage) {
				foreach ($value as $key) {
					$stack[] = $key;
					$stack[] = $value[$key];
				}
			}
			elseif ($value instanceof \ArrayObject) {
				$stack[] = $value->getArrayCopy();
			}
			elseif (!($value instanceof \UnitEnum) && str_starts_with($class, __NAMESPACE__ . '\\')) {
				foreach ((new \ReflectionObject($value))->getProperties() as $property) {
					if (!$property->isStatic() && $property->isInitialized($value)) {
						$stack[] = $property->getValue($value);
					}
				}
			}
		}
		ksort($counts);
		return $counts;
	}

	/**
	 * Count retained roots, live watcher relations and pending work at a completed sequential gate.
	 * @return mixed[]
	 */
	public function snapshot(Incremental_Project $project): array /** array<string,mixed> */
	{
		$classes = $this->graph($project);
		$session = $project->session() ?? throw new \LogicException('Run the project before inspecting');
		$live_watchers = new \SplObjectStorage();
		$live_declarations = new \SplObjectStorage();
		foreach ($session->files() as $file) {
			foreach ($file->published_declarations() as $declaration) {
				$live_declarations->attach($declaration);
			}
			foreach ($file->declaration_nodes as $node) {
				if ($node instanceof ast_function_declaration) {
					$live_watchers->attach($node);
					$live_watchers->attach($node->body());
				} elseif ($node instanceof ast_struct_declaration) {
					$live_watchers->attach($node->definition);
				}
			}
		}
		$depth = 0;
		$removed = 0;
		for ($prior = $session; $prior !== null; $prior = $prior->previous) {
			$depth++;
			$removed += count($prior->removed_sources);
		}
		$entries = $session->scope->entries();
		$tombstones = 0;
		$ambiguous = 0;
		foreach ($entries as $entry) {
			if ($entry->status === change_status::removed) {
				$tombstones++;
				if ($entry->declaration() !== null) {
					throw new \LogicException('Tombstone retains a declaration');
				}
			}
		}
		$chained = new \SplObjectStorage();
		foreach ($session->scope->heads() as $key => $head) {
			$ambiguous += $head->duplicate_element() !== null ? 1 : 0;
			for ($node = $head; $node !== null; $node = $node->duplicate_element()) {
				if ($chained->contains($node) || !$live_declarations->contains($node) || ($node->symbol_key() !== $key)) {
					throw new \LogicException('Duplicate chain cycles or references retired/wrong-key syntax');
				}
				$chained->attach($node);
			}
		}
		if (count($chained) !== count($live_declarations)) {
			throw new \LogicException('Live declaration missing from publication');
		}
		foreach ($session->preparation_work->pending() as $bucket) {
			foreach ($bucket as $body) {
				if (!($body instanceof ast_function_body) || !$live_watchers->contains($body)) {
					throw new \LogicException('Retired body remains in preparation queue');
				}
			}
		}
		/** @var \SplObjectStorage $targets */
		$targets = self::field($session->dependency_watches, 'targets');
		$relations = 0;
		$stale = 0;
		foreach ($targets as $watcher) {
			if (count($targets[$watcher]) === 0) {
				throw new \LogicException('Empty watcher reverse bucket');
			}
			$relations += count($targets[$watcher]);
			$stale += $live_watchers->contains($watcher) ? 0 : 1;
		}
		/** @var \SplObjectStorage $observers */
		$observers = self::field($session->dependency_watches, 'observers');
		$forward = 0;
		$groups = 0;
		foreach ($observers as $symbol) {
			if (count($observers[$symbol]) === 0) {
				throw new \LogicException('Empty observer symbol bucket');
			}
			foreach ($observers[$symbol] as $bucket) {
				if (count($bucket) === 0) {
					throw new \LogicException('Empty observer source bucket');
				}
				$groups++;
				$forward += count($bucket);
				foreach ($bucket as $watcher) {
					if (!$targets->contains($watcher) || !$targets[$watcher]->contains($symbol)) {
					throw new \LogicException('Forward/reverse watch mismatch');
					}
				}
			}
		}
		if ($forward !== $relations) {
			throw new \LogicException('Watch relation counts differ');
		}
		return ['session_depth' => $depth, 'live_files' => count($session->files()),
			'retained_ast_roots' => $classes[ast_file_node::class] ?? 0,
			'retained_token_lists' => $classes[token_list::class] ?? 0,
			'retired_ast_roots' => ($classes[ast_file_node::class] ?? 0) - count($session->files()),
			'removed_source_records_in_history' => $removed,
			'symbol_entries' => count($entries), 'tombstones' => $tombstones, 'ambiguous_symbols' => $ambiguous,
			'watchers' => count($targets), 'watch_relations' => $relations, 'watch_source_groups' => $groups,
			'stale_watchers' => $stale, 'pending_notifications' => count(self::field($session->dependency_watches, 'pending')),
			'pending_bodies' => $session->preparation_work->pending_count(),
			'pending_emission_sources' => count($session->preparation_work->emission_sources()),
			'class_counts' => $classes];
	}

	/** After releasing the entire project and collecting PHP cycles, these weak observations should be empty. */
	public function survivors(): int /** uint64 */
	{
		return count($this->observed);
	}

	/** STATIC METHODS */
	/** Read private bookkeeping only in this test adapter; never add inspection APIs to production owners. */
	public static function field(object $owner, string $name): mixed
	{
		return (new \ReflectionProperty($owner, $name))->getValue($owner);
	}

}
