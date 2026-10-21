<?php

/* Reject recursive components containing reference returns, without retaining another call graph. */
namespace scpp\compiler;

final class Reference_Cycles
{
	private Storage $vertices /** Storage<reference_cycle_vertex> */;
	private array $stack /** vector<int> */ = [];
	private int $stack_size = 0;
	private int $next_index = 0;
	private Storage $cyclic /** Storage<reference_cycle_vertex> */;

	public function __construct()
	{
		$this->vertices = new Storage /** Storage<reference_cycle_vertex> */();
		$this->cyclic = new Storage /** Storage<reference_cycle_vertex> */();
	}

	/** Use current collection indexes and settled targets, including unchanged bodies on incremental runs. */
	public function find(Storage $sources /** Storage<collected_file> */): Storage /** Storage<reference_cycle_vertex> */
	{
		$sources = Callable_Inventory::source_collections($sources);
		$by_definition = new \SplObjectStorage /** hash<int, shared<callable_target_i>> */();
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
					if (Callable_Inventory::deferred_body($definition)) { continue; }
					if ($definition instanceof collected_method) {
						if (object_cast($definition, collected_method::class)->declaring_type() instanceof collected_trait) { continue; }
						if (Member_Inventory::shadowed_lifecycle(object_cast($definition, collected_method::class))) { continue; }
					}
					$index = q_count($vertices);
					$by_definition[$definition] = $index;
					$body /** nullable<function_body_node> */ = null;
					if ($definition->callable_syntax()->has_parsed_body()) {
						$body = $definition->callable_syntax()->require_body();
						$by_scope[$body->local_scope()] = $index;
					}
					$vertices->append(new reference_cycle_vertex($definition, $body, $definition->name));
					$has_reference_return = $has_reference_return || ($definition->signature()->reference_return !== null);
				}
			}
			$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
			foreach ($anonymous as $identity) {
				if ($identity->deleted) { continue; }
				// Authored trait/deferred bodies may contain unprepared closure syntax.
				if ($identity->signature_work === null) { continue; }
				$anonymous_body = $identity->syntax()->require_body();
				if ($anonymous_body->work() === null) { continue; }
				$index = q_count($vertices);
				$by_definition[$identity] = $index;
				$by_scope[$anonymous_body->local_scope()] = $index;
				$vertices->append(new reference_cycle_vertex($identity, $anonymous_body, '<closure ' . $identity->local_index . '>'));
				$has_reference_return = $has_reference_return || ($identity->signature()->reference_return !== null);
			}
		}
		if (!$has_reference_return) {
			return $this->cyclic;
		}
		$this->requirement_targets($sources, $by_definition);
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
						if ($target instanceof callable_target_i) {
							$callee = object_cast($target, callable_target_i::class);
							$vertex = $vertices[$by_scope[$current_scope]];
							if ($callee instanceof callable_type_definition) {
								$this->erased_targets($vertex, object_cast($callee, callable_type_definition::class));
								break;
							}
							if (!isset($by_definition[$callee])) { break; }
							$vertex->calls[] = $by_definition[$callee];
							if (($callee instanceof collected_source_callable) && ($facts->receiver !== null)) {
								if ($facts->receiver->dispatch_type === null) {
									$this->virtual_targets($vertex, object_cast($callee, collected_source_callable::class));
								}
							}
						}
						break;
					}
					// Never attribute an unprepared anonymous body to an enclosing callable.
					if ($current_scope->is_function()) { break; }
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

	/** Conservative alternatives also cover value-returning erased intermediaries. */
	private function erased_targets(reference_cycle_vertex $caller, callable_type_definition $target): void
	{
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		foreach ($vertices as $index => $vertex) {
			if (!($vertex->target instanceof anonymous_callable_identity)) { continue; }
			if (Callable_Contracts::matches_erased($vertex->target->signature(), $target->signature())) {
				$caller->calls[] = $index;
			}
		}
	}

	/** Interface/trait contracts route to implementations, including inherited forwarding bindings. */
	private function requirement_targets(Storage $sources /** Storage<collected_file> */,
		\SplObjectStorage $by_definition /** hash<int, shared<callable_target_i>> */): void
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
				$bindings /** Storage<prepared_dispatch_binding> */ = $contracts->bindings;
				foreach ($bindings as $binding) {
					$requirement = weakref_get($binding->required);
					// Concrete parent bodies are exact targets for parent::, not requirement vertices.
					if ($requirement->callable_syntax()->has_parsed_body()) { continue; }
					$implementation = weakref_get($binding->implementation);
					if ($implementation !== $requirement) {
						$vertex = $vertices[$by_definition[$requirement]];
						$vertex->calls[] = $by_definition[$implementation];
					}
				}
			}
		}
	}

	/** Virtual alternatives belong to the call site; explicit parent calls stay exact. */
	private function virtual_targets(reference_cycle_vertex $caller, collected_source_callable $target): void
	{
		if (!($target instanceof collected_method)) { return; }
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		$reachable = new \SplObjectStorage /** hash<bool, shared<collected_source_callable>> */();
		$reachable[$target] = true;
		$changed = true;
		while ($changed) {
			$changed = false;
			foreach ($vertices as $index => $vertex) {
				if (!($vertex->target instanceof collected_method)) { continue; }
				$method = object_cast($vertex->target, collected_method::class);
				if (isset($reachable[$method])) { continue; }
				$bindings /** Storage<prepared_dispatch_binding> */ = $method->declaring_type()->contract_work->require_facts()->bindings;
				foreach ($bindings as $binding) {
					if (weakref_get($binding->implementation) !== $method) { continue; }
					$required = weakref_get($binding->required);
					if (isset($reachable[$required])) {
						$reachable[$method] = true;
						$caller->calls[] = $index;
						$changed = true;
						break;
					}
				}
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
		$cyclic /** Storage<reference_cycle_vertex> */ = $this->cyclic;
		foreach ($component as $member) {
			$member_vertex = $vertices[$member];
			if (($member_vertex->target->signature()->reference_return !== null) && ($member_vertex->body !== null)) {
				$cyclic->append($member_vertex);
			}
		}
	}
}
