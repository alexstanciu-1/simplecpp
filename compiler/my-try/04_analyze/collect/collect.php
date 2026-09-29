<?php

/* Parser-called registration and reconciliation; no independent collection or resolution pass. */
namespace scpp\compiler;

final class Symbol_Collector
{
	private collected_file $collection;
	private ?scope $global = null;
	private scope $file_scope;

	/** Retain symbol rows and occurrences until the parser selects reused or replaced bodies. */
	public function __construct(collected_file $collection, token_list $tokens, scope $file_scope, ?scope $global, int $revision)
	{
		$this->collection = $collection;
		$this->file_scope = $file_scope;
		$this->global = $global;
		$collection->set_tokens($tokens);
		$collection->revision = $revision;
	}

	/** Match the next unseen function identity in its owning scope. */
	public function previous_function(scope $scope, string $name): ?function_node
	{
		foreach ($scope->functions_named($name) as $entry) {
			if ($entry instanceof collected_function) {
				if ($this->unseen($entry)) {
					return object_cast($entry, collected_function::class)->syntax();
				}
			}
		}
		return null;
	}

	/** Match the next unseen struct identity in its owning scope. */
	public function previous_struct(scope $scope, string $name): ?struct_node
	{
		foreach ($scope->source_types_named($name) as $entry) {
			if ($entry instanceof collected_struct) {
				if ($this->unseen($entry)) {
					return object_cast($entry, collected_struct::class)->syntax();
				}
			}
		}
		return null;
	}

	/** Match the next unseen field identity in its owning scope. */
	public function previous_field(scope $scope, string $name): ?field_node
	{
		foreach ($scope->variables_named($name) as $entry) {
			if ($entry instanceof collected_field) {
				if ($this->unseen($entry)) {
					return object_cast($entry, collected_field::class)->syntax();
				}
			}
		}
		return null;
	}

	/** Match the next unseen parameter identity in its owning scope. */
	public function previous_parameter(scope $scope, string $name): ?parameter_node
	{
		foreach ($scope->variables_named($name) as $entry) {
			if ($entry instanceof collected_parameter) {
				if ($this->unseen($entry)) {
					return object_cast($entry, collected_parameter::class)->syntax();
				}
			}
		}
		return null;
	}

	/** Duplicate spellings consume retained identities in encounter order. */
	private function unseen(collected_name $entry): bool
	{
		return $entry->is_retained() && ((int)$entry->revision !== (int)$this->collection->revision);
	}

	/** Register typed syntax as soon as parsing identifies it, or refresh its retained identity. */
	public function collect_function(function_node $node, scope $scope, int $index): void
	{
		if ($this->reuse($node, $index)) {
			return;
		}
		$entry = new collected_function($this->collection, $node);
		$entry->retained_symbol = true;
		$this->append($entry, $scope, $node->name, $index);
		$scope->register($entry);
		$this->publish($entry, $scope);
	}

	/** Register typed syntax as soon as parsing identifies it, or refresh its retained identity. */
	public function collect_struct(struct_node $node, scope $scope, int $index): void
	{
		if ($this->reuse($node, $index)) {
			return;
		}
		$entry = new collected_struct($this->collection, $node);
		$entry->retained_symbol = true;
		$this->append($entry, $scope, $node->name, $index);
		$scope->register_type(Source_Types::definition($entry));
		$this->publish($entry, $scope);
	}

	/** Register typed syntax as soon as parsing identifies it, or refresh its retained identity. */
	public function collect_field(field_node $node, scope $scope, int $index): void
	{
		if ($this->reuse($node, $index)) {
			return;
		}
		$entry = new collected_field($this->collection, $node);
		$entry->retained_symbol = true;
		$this->append($entry, $scope, $node->name, $index);
		$scope->register($entry);
	}

	/** Register typed syntax as soon as parsing identifies it, or refresh its retained identity. */
	public function collect_parameter(parameter_node $node, scope $scope, int $index): void
	{
		if ($this->reuse($node, $index)) {
			return;
		}
		$entry = new collected_parameter($this->collection, $node);
		$entry->retained_symbol = true;
		$this->append($entry, $scope, $node->name, $index);
		$scope->register($entry);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_variable(variable_declaration_node $node, scope $scope, int $index): void
	{
		$entry = new collected_variable($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
		$scope->register($entry);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_variable_reference(variable_reference_node $node, scope $scope, int $index): void
	{
		$entry = new collected_variable_reference($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_variable_write(variable_reference_node $node, scope $scope, int $index): void
	{
		$entry = new collected_variable_write($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_function_reference(call_node $node, scope $scope, int $index): void
	{
		$entry = new collected_function_reference($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_field_reference(field_access_node $node, scope $scope, int $index): void
	{
		$entry = new collected_field_reference($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_type_reference(named_type_node $node, scope $scope, int $index): void
	{
		$entry = new collected_type_reference($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Reconciliation refreshes provenance without allocating a replacement occurrence. */
	private function reuse(ast_node $node, int $index): bool
	{
		$previous = $node->optional_occurrence();
		if ($previous === null) {
			return false;
		}
		$entry /** collected_name */ = $previous;
		if ($entry->change_status === change_state::deleted) {
			$entry->change_status = change_state::added;
		}
		$entry->token_index = $index;
		$entry->revision = $this->collection->revision;
		return true;
	}

	/** Allocate only new identities; the storage index is never reused by a later occurrence. */
	private function append(collected_name $entry, scope $scope, string $name, int $index): void
	{
		$node = $entry->syntax();
		if ($node->optional_occurrence() !== null) {
			throw new \LogicException('Syntax already has a collected occurrence');
		}
		$entry->name = $name;
		$entry->scope = $scope;
		$entry->token_index = $index;
		$entry->revision = $this->collection->revision;
		$entries /** Storage<collected_name> */ = $this->collection->entries;
		$entry->local_index = $entries->append($entry);
		$node->attach_occurrence($entry);
		$entry->index_collection($this);
	}

	/** Only file-level functions and records enter shared indexes, under the existing task lock. */
	private function publish(collected_definition $entry, scope $scope): void
	{
		if ($scope !== $this->file_scope) {
			return;
		}
		if ($this->global === null) {
			return;
		}
		$global /** scope */ = $this->global;
		$entry->exported = true;
		task_synchronize(function () use ($entry, $scope, $global): void {
			Scope_Publication::register($scope, $global, $entry);
		});
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_declaration(collected_declaration $entry): void
	{
		$this->collection->defined_elements[] = $entry->local_index;
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_variable_reference(collected_variable_reference $entry): void
	{
		$this->collection->variable_references[] = $entry->local_index;
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_function_reference(collected_function_reference $entry): void
	{
		$this->collection->function_references[] = $entry->local_index;
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_type_reference(collected_type_reference $entry): void
	{
		$this->collection->type_references[] = $entry->local_index;
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_field_reference(collected_field_reference $entry): void
	{
		$this->collection->field_references[] = $entry->local_index;
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_variable_write(collected_variable_write $entry): void
	{
		$this->collection->pending_bindings[] = $entry->local_index;
	}

	/** A completed file can retire unseen local symbols; exported rows wait until the join. */
	public function finish(file_node $root): collected_file
	{
		$this->collection->root = $root;
		$this->refresh_occurrences();
		self::sweep($this->collection, false);
		return $this->collection;
	}

	/** Retire obsolete occurrences after reuse decisions, rebuilding current work lists once. */
	private function refresh_occurrences(): void
	{
		$collection = $this->collection;
		$collection->defined_elements = [];
		$collection->variable_references = [];
		$collection->function_references = [];
		$collection->type_references = [];
		$collection->field_references = [];
		$collection->pending_bindings = [];
		$entries /** Storage<collected_name> */ = $collection->entries;
		$retired /** vector<int> */ = [];
		$ranges /** Storage<retained_token_range> */ = $collection->token_snapshot()->retained_ranges;
		foreach ($entries as $entry)
		{
			// Reused regions retain old identities; temporary parses of their new interval retire.
			foreach ($ranges as $range)
			{
				if (($entry->token_index >= $range->first) && ($entry->token_index < $range->end)) {
					$entry->revision = $collection->revision;
					break;
				}
				$current_end = $range->current_first + ($range->end - $range->first);
				if (($entry->token_index >= $range->current_first) && ($entry->token_index < $current_end)) {
					$entry->revision = 0;
					break;
				}
			}
			$index = $entry->local_index;
			if (!$entry->is_retained() && ((int)$entry->revision !== (int)$collection->revision)) {
				$retired[] = $index;
				continue;
			}
			$entry->index_collection($this);
		}
		foreach ($retired as $index) {
			$entries->remove($index);
		}
	}

	/** Called locally for private symbols, and coordinator-side for globals after successful files. */
	// Preparation notifies dependents, then physically removes these rows and index memberships.
	public static function sweep(collected_file $collection, bool $exported): void
	{
		$entries /** Storage<collected_name> */ = $collection->entries;
		foreach ($collection->defined_elements as $index) {
			$entry = $entries[$index];
			if ($entry->is_retained() && ($entry->is_exported() === $exported) && ((int)$entry->revision !== (int)$collection->revision)) {
				$entry->change_status = change_state::deleted;
			}
		}
	}
}
