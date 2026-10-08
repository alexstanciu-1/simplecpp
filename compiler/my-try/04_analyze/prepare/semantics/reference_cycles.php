<?php

/* Reject recursive components containing reference returns, without retaining another call graph. */
namespace scpp\compiler;

final class Reference_Cycles
{
	private Storage $vertices /** Storage<reference_cycle_vertex> */;
	private array $stack /** vector<int> */ = [];
	private int $stack_size = 0;
	private int $next_index = 0;
	private Storage $cyclic /** Storage<collected_source_callable> */;

	public function __construct()
	{
		$this->vertices = new Storage /** Storage<reference_cycle_vertex> */();
		$this->cyclic = new Storage /** Storage<collected_source_callable> */();
	}

	/** Use current collection indexes and settled targets, including unchanged bodies on incremental runs. */
	public function find(Storage $sources /** Storage<collected_file> */): Storage /** Storage<collected_source_callable> */
	{
		$sources = Callable_Inventory::source_collections($sources);
		$by_definition = new \SplObjectStorage /** hash<int, shared<collected_source_callable>> */();
		$by_scope = new \SplObjectStorage /** hash<int, shared<scope>> */();
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		$has_reference_return = false;
		foreach ($sources as $source)
		{
			if ($source->deleted) {
				continue;
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->defined_elements as $position)
			{
				$entry = $entries[$position];
				if ($entry->change_status === change_state::deleted) {
					continue;
				}
				if ($entry instanceof collected_source_callable) {
					$definition = object_cast($entry, collected_source_callable::class);
					if ($definition instanceof collected_method) {
						if (object_cast($definition, collected_method::class)->declaring_type() instanceof collected_trait) { continue; }
					}
					$index = q_count($vertices);
					$by_definition[$definition] = $index;
					if ($definition->callable_syntax()->has_parsed_body()) {
						$by_scope[$definition->callable_syntax()->require_body()->local_scope()] = $index;
					}
					$vertices->append(new reference_cycle_vertex($definition));
					$has_reference_return = $has_reference_return || ($definition->signature()->reference_return !== null);
				}
			}
		}
		if (!$has_reference_return) {
			return $this->cyclic;
		}
		$this->interface_targets($sources, $by_definition);
		foreach ($sources as $source)
		{
			if ($source->deleted) {
				continue;
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->call_references as $position)
			{
				$entry = $entries[$position];
				if ($entry->change_status === change_state::deleted) {
					continue;
				}
				$call = object_cast($entry, collected_call_reference::class);
				$lexical_scope /** nullable<scope> */ = weakref_get($call->scope);
				while ($lexical_scope !== null)
				{
					$current_scope /** scope */ = $lexical_scope;
					if (isset($by_scope[$current_scope])) {
						$facts = $call->syntax()->require_call_preparation();
						$target = weakref_get($facts->target);
						if ($target instanceof collected_source_callable) {
							$callee = object_cast($target, collected_source_callable::class);
							$vertex = $vertices[$by_scope[$current_scope]];
							$vertex->calls[] = $by_definition[$callee];
							if ($facts->receiver !== null) {
								if ($facts->receiver->dispatch_type === null) {
									$this->virtual_targets($vertex, $callee);
								}
							}
						}
						break;
					}
					$lexical_scope = $current_scope->parent_scope();
				}
			}
		}
		foreach ($vertices as $index => $vertex) {
			if ($vertex->visit_index === -1) {
				$this->visit($index);
			}
		}
		return $this->cyclic;
	}

	/** Interface contracts route to original implementations, including inherited forwarding bindings. */
	private function interface_targets(Storage $sources /** Storage<collected_file> */,
		\SplObjectStorage $by_definition /** hash<int, shared<collected_source_callable>> */): void
	{
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		foreach ($sources as $source) {
			if ($source->deleted) { continue; }
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->defined_elements as $position) {
				$entry = $entries[$position];
				if (($entry->change_status === change_state::deleted) || !($entry instanceof collected_record)) { continue; }
				$record = object_cast($entry, collected_record::class);
				$contracts = $record->contract_work->require_facts();
				foreach ($contracts->interface_requirements as $requirement) {
					$implementation = self::implementation($record, $requirement->name);
					if ($implementation !== null) {
						$vertex = $vertices[$by_definition[$requirement]];
						$vertex->calls[] = $by_definition[$implementation];
					}
				}
			}
		}
	}

	/** Read settled decisions; no scheduling, synthetic methods or retained dispatch graph. */
	private static function implementation(collected_record $record, string $name): ?collected_method
	{
		$contracts = $record->contract_work->require_facts();
		$methods /** Keyed_Storage<prepared_method_contract> */ = $contracts->methods;
		$forwarders /** Keyed_Storage<prepared_method_contract> */ = $contracts->forwarders;
		if (isset($methods[$name])) { return weakref_get($methods[$name]->method); }
		if (isset($forwarders[$name])) { return weakref_get($forwarders[$name]->method); }
		$base = weakref_get($record->relationship_work->require_facts()->base);
		if ($base === null) { return null; }
		return self::implementation($base, $name);
	}

	/** Virtual alternatives belong to the call site; explicit parent calls stay exact. */
	private function virtual_targets(reference_cycle_vertex $caller, collected_source_callable $target): void
	{
		if (!($target instanceof collected_method)) { return; }
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		foreach ($vertices as $index => $vertex) {
			if (!($vertex->definition instanceof collected_method)) { continue; }
			$method /** nullable<collected_method> */ = object_cast($vertex->definition, collected_method::class);
			while ($method !== null) {
				$contracts = $method->declaring_type()->contract_work->require_facts();
				$methods /** Keyed_Storage<prepared_method_contract> */ = $contracts->methods;
				$method = weakref_get($methods[$method->name]->overridden);
				if ($method === $target) { $caller->calls[] = $index; break; }
			}
		}
	}

	/** Tarjan components cover cycles through value-returning helpers as well as direct forwarding. */
	private function visit(int $index): void
	{
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		$vertex = $vertices[$index];
		$vertex->visit_index = $this->next_index;
		$vertex->low_link = $this->next_index;
		$this->next_index++;
		// Native vectors require append for new slots; popped slots may be reused.
		if ($this->stack_size === q_count($this->stack)) {
			$this->stack[] = $index;
		}
		else {
			$this->stack[$this->stack_size] = $index;
		}
		$this->stack_size++;
		$vertex->on_stack = true;
		$self_call = false;
		foreach ($vertex->calls as $target)
		{
			$self_call = $self_call || ($target === $index);
			$next = $vertices[$target];
			if ($next->visit_index === -1) {
				$this->visit($target);
				if ($next->low_link < $vertex->low_link) {
					$vertex->low_link = $next->low_link;
				}
			}
			elseif ($next->on_stack) {
				if ($next->visit_index < $vertex->low_link) {
					$vertex->low_link = $next->visit_index;
				}
			}
		}
		if ($vertex->low_link !== $vertex->visit_index) {
			return;
		}
		$component /** vector<int> */ = [];
		$member = -1;
		do {
			$this->stack_size -= 1;
			$member = $this->stack[$this->stack_size];
			$vertices[$member]->on_stack = false;
			$component[] = $member;
		}
		while ($member !== $index);
		if ((q_count($component) === 1) && !$self_call) {
			return;
		}
		$cyclic /** Storage<collected_source_callable> */ = $this->cyclic;
		foreach ($component as $member) {
			$definition = $vertices[$member]->definition;
			if (($definition->signature()->reference_return !== null) && $definition->callable_syntax()->has_parsed_body()) {
				$cyclic->append($definition);
			}
		}
	}
}
