<?php

/* Reject recursive components containing reference returns, without retaining another call graph. */
namespace scpp\compiler;

final class Reference_Cycles
{
	private Storage $vertices /** Storage<reference_cycle_vertex> */;
	private array $stack /** vector<int> */ = [];
	private int $stack_size = 0;
	private int $next_index = 0;
	private Storage $cyclic /** Storage<collected_function> */;

	public function __construct()
	{
		$this->vertices = new Storage /** Storage<reference_cycle_vertex> */();
		$this->cyclic = new Storage /** Storage<collected_function> */();
	}

	/** Use current collection indexes and settled targets, including unchanged bodies on incremental runs. */
	public function find(Storage $sources /** Storage<collected_file> */): Storage /** Storage<collected_function> */
	{
		$by_definition = new \SplObjectStorage /** hash<int, shared<collected_function>> */();
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
				if ($entry instanceof collected_function) {
					$definition = object_cast($entry, collected_function::class);
					$index = q_count($vertices);
					$by_definition[$definition] = $index;
					$by_scope[$definition->syntax()->body->local_scope()] = $index;
					$vertices->append(new reference_cycle_vertex($definition));
					$has_reference_return = $has_reference_return || ($definition->signature()->reference_return !== null);
				}
			}
		}
		if (!$has_reference_return) {
			return $this->cyclic;
		}
		foreach ($sources as $source)
		{
			if ($source->deleted) {
				continue;
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->function_references as $position)
			{
				$entry = $entries[$position];
				if ($entry->change_status === change_state::deleted) {
					continue;
				}
				$call = object_cast($entry, collected_function_reference::class);
				$lexical_scope /** nullable<scope> */ = weakref_get($call->scope);
				while ($lexical_scope !== null)
				{
					$current_scope /** scope */ = $lexical_scope;
					if (isset($by_scope[$current_scope])) {
						$target = weakref_get($call->syntax()->require_call_preparation()->target);
						if ($target instanceof collected_function) {
							$callee = object_cast($target, collected_function::class);
							$vertex = $vertices[$by_scope[$current_scope]];
							$vertex->calls[] = $by_definition[$callee];
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

	/** Tarjan components cover cycles through value-returning helpers as well as direct forwarding. */
	private function visit(int $index): void
	{
		$vertices /** Storage<reference_cycle_vertex> */ = $this->vertices;
		$vertex = $vertices[$index];
		$vertex->visit_index = $this->next_index;
		$vertex->low_link = $this->next_index;
		$this->next_index++;
		$this->stack[$this->stack_size] = $index;
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
		$cyclic /** Storage<collected_function> */ = $this->cyclic;
		foreach ($component as $member) {
			$definition = $vertices[$member]->definition;
			if ($definition->signature()->reference_return !== null) {
				$cyclic->append($definition);
			}
		}
	}
}
