<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Interned (authored method, called type) pair. Both symbols survive declaration replacement. */
final class called_method_instance
{
	public readonly symbol_entry $method;
	public readonly symbol_entry $called;
	/** Private working body, created by the defining-unit preparation pass on first reachable demand. */
	public ?ast_function_declaration $implementation = null;
	/** Publication fact derived from reachable callers in other source units. */
	public bool $external = false;

	public function __construct(symbol_entry $method, symbol_entry $called)
	{
		$this->method = $method;
		$this->called = $called;
	}
}

/** Coordinator for private static bodies. Reachability starts at authored nonstatic bodies, so unused cycles retire together. */
final class Called_Methods
{
	/** @var called_method_instance[] Stable pair inventory for this session lineage. */
	private array $pairs = [];
	/** @var array<int,array<string,called_method_instance>> Outgoing calls only for bodies with static calls. */
	private array $edges = [];
	/** @var array<int,string> Source keys of nonstatic callers with at least one demand. */
	private array $roots = [];
	private bool $dirty = false;

	/** Intern the selection and record its caller before any body is materialized. */
	public function request(ast_function_body $caller, symbol_entry $method, symbol_entry $called): called_method_instance
	{
		$this->dirty = true;
		$key = spl_object_id($method) . ':' . spl_object_id($called);
		$instance = $this->pairs[$key] ??= new called_method_instance($method, $called);
		$id = spl_object_id($caller);
		$this->edges[$id][$key] = $instance;
		if ($caller->parent->instance === null) $this->roots[$id] = $caller->dependency_source_key();
		return $instance;
	}

	public function forget(ast_function_body $caller): void
	{
		$id = spl_object_id($caller);
		if (isset($this->edges[$id])) $this->dirty = true;
		unset($this->edges[$id], $this->roots[$id]);
	}

	/** Prepare reachable work once. Only a successful traversal retires unreachable implementations. */
	public function prepare(scope $symbols, Dependency_Watches $dependencies, ?preparation_work $work = null): int /** uint32 */
	{
		if (!$this->dirty && $work !== null && $work->emission_sources() === []) return 0;
		$todo = [];
		$external = [];
		$errors = [];
		foreach ($this->roots as $id => $source) $this->enqueue($id, $source, $todo, $external);
		$seen = [];
		$count = 0;
		while ($todo !== []) {
			$batch = $todo;
			$todo = [];
			foreach ($batch as $key => $pair) {
				if (isset($seen[$key])) continue;
				$seen[$key] = true;
				$origin = $pair->method->declaration();
				$method = $pair->implementation;
				if ($method === null || $method->parent !== $origin->parent || $method->return_type_def() !== $origin->return_type_def()) {
					if ($method !== null) $this->remove($method, $dependencies, $work);
					$method = $pair->implementation = $origin->instantiate($pair);
				}
				if (!$method->body()->is_prepared()) {
					$work?->change_source($method->dependency_source_key());
					try {
						(new Preparation())->function_body($method->body(), $symbols, null, $dependencies);
					} catch (diagnostic $error) {
						$errors[] = $error;
						continue;
					} catch (diagnostic_group $group) {
						array_push($errors, ...$group->diagnostics);
						continue;
					}
					$dependencies->watch($pair->method, $method->body());
					$dependencies->watch($pair->called, $method->body());
					$method->root()->publish_implementation($method);
					$count++;
				}
				$this->enqueue(spl_object_id($method->body()), $method->dependency_source_key(), $todo, $external);
			}
		}
		if ($errors !== []) throw new diagnostic_group(Declaration_Diagnostics::ordered($errors));
		foreach ($seen as $key => $_) $this->pairs[$key]->external = isset($external[$key]);
		foreach (array_diff_key($this->pairs, $seen) as $key => $pair) {
			if ($pair->implementation !== null) $this->remove($pair->implementation, $dependencies, $work);
			unset($this->pairs[$key]);
		}
		$this->dirty = false;
		return $count;
	}

	/**
	 * Queue outgoing pairs and collect cross-unit uses, including back edges to already prepared bodies.
	 * @param called_method_instance[] $todo
	 * @param bool[] $external
	 */
	private function enqueue(int $id, string $source, array &$todo, array &$external): void
	{
		foreach ($this->edges[$id] ?? [] as $key => $pair) {
			$todo[$key] = $pair;
			if ($pair->method->declaration()->dependency_source_key() !== $source) $external[$key] = true;
		}
	}

	/** After successful retirement, every remaining pair has a prepared body. */
	public function count(): int /** uint32 */
	{
		return count($this->pairs);
	}

	/** Invalidating a private body can be the only consequence of deleting a dependency file. */
	public function invalidate(): void
	{
		$this->dirty = true;
	}

	/** Retire the executable subtree and its subscriptions at the coordinator gate. */
	private function remove(ast_function_declaration $method, Dependency_Watches $dependencies, ?preparation_work $work): void
	{
		$dependencies->forget($method->body());
		$method->root()->retire_implementation($method);
		$work?->change_source($method->dependency_source_key());
	}
}
