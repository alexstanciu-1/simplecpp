<?php

/* Parser-called registration and reconciliation; no independent collection or resolution pass. */
namespace scpp\compiler;

final class Symbol_Collector
{
	private ?scope $global = null;

	/** Retain symbol rows and occurrences until the parser selects reused or replaced bodies. */
	public function __construct(private collected_file $collection, token_list $tokens,
		private scope $source_scope, ?scope $global, int $revision)
	{
		$this->global = $global;
		$collection->set_tokens($tokens);
		$collection->revision = $revision;
	}

	/** The parser selects each source occurrence's declaration publication environment. */
	public function select_source_scope(scope $local_scope, ?scope $published): void
	{
		$this->source_scope = $local_scope;
		$this->global = $published;
	}

	/** Allocate an anonymous source identity without publishing a declaration name. */
	public function collect_anonymous_callable(closure_node $syntax): void
	{
		$identity = new anonymous_callable_identity($this->collection, $syntax);
		$identities /** Storage<anonymous_callable_identity> */ = $this->collection->anonymous_callables;
		$identity->local_index = $identities->append($identity);
		$identity->revision = (int)$this->collection->revision;
		$syntax->attach_anonymous_identity($identity);
	}

	/** Match the next unseen function identity in its owning scope. */
	public function previous_function(scope $scope, string $name): ?function_node
	{
		foreach ($scope->functions_named($name) as $entry) {
			if ($this->unseen($entry)) {
				return object_cast($entry->callable_syntax(), function_node::class);
			}
		}
		return null;
	}

	/** Members reconcile only inside the declaring type, independently of properties. */
	public function previous_method(scope $scope, string $name): ?method_node
	{
		foreach ($scope->methods_named($name) as $entry) {
			if ($this->unseen($entry)) {
				return object_cast($entry->callable_syntax(), method_node::class);
			}
		}
		return null;
	}

	/** Reuse the same declaration kind; a kind edit replaces its nominal identity. */
	public function previous_member_owner(scope $scope, string $name, type_declaration_kind $kind): ?type_declaration_node
	{
		foreach ($this->retained_types($scope, $name) as $entry) {
			if (!($entry instanceof collected_member_owner)) {
				continue;
			}
			if ($this->unseen($entry)) {
				$syntax = object_cast($entry, collected_member_owner::class)->syntax();
				if ($syntax->declaration_kind === $kind) {
					return $syntax;
				}
			}
		}
		return null;
	}

	/** Enum reconciliation never reuses another source type's syntax, even under the same spelling. */
	public function previous_enum(scope $scope, string $name): ?enum_node
	{
		foreach ($this->retained_types($scope, $name) as $entry) {
			if ($entry instanceof collected_enum) {
				if ($this->unseen($entry)) {
					return object_cast($entry, collected_enum::class)->enum_syntax();
				}
			}
		}
		return null;
	}

	/** Snapshot shared associations under publication's lock, then reconcile source-local syntax. */
	private function retained_types(scope $scope, string $name): array /** vector<collected_type_definition> */
	{
		if ($this->global === null) { return $scope->source_types_named($name); }
		$snapshot = new source_type_snapshot();
		task_synchronize(function () use ($scope, $name, $snapshot): void {
			$snapshot->declarations = $scope->source_types_named($name);
		});
		return $snapshot->declarations;
	}

	public function previous_constant(scope $scope, string $name): ?constant_declaration_node
	{
		foreach ($scope->constants_named($name) as $target) {
			if ($target instanceof collected_constant) {
				$entry = object_cast($target, collected_constant::class);
				if ($this->unseen($entry)) { return $entry->syntax(); }
			}
		}
		return null;
	}

	public function collect_constant(constant_declaration_node $node, scope $scope, int $index, ?collected_member_owner $record = null): void
	{
		if ($this->reuse($node, $index)) { return; }
		$entry = new collected_constant($this->collection, $node);
		if ($record !== null) {
			$owner /** collected_member_owner */ = $record;
			$entry->member_owner = weak($owner);
		}
		$entry->retained_symbol = true;
		$this->append($entry, $scope, $node->name, $index);
		$scope->register($entry);
		if ($record === null) { $this->publish($entry, $scope); }
	}

	/** Match the next unseen field identity in its owning scope. */
	public function previous_field(scope $scope, string $name): ?property_node
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

	/** Member registration shares retained identities but never namespace publication. */
	public function collect_method(method_node $node, collected_member_owner $record, int $index, ?scope $registration_scope = null): void
	{
		if ($this->reuse($node, $index)) { return; }
		$entry = new collected_method($this->collection, $node, $record);
		$entry->retained_symbol = true;
		$member_scope = $registration_scope ?? $record->syntax()->member_scope();
		$this->append($entry, $member_scope, $node->name, $index);
		$member_scope->register($entry);
	}

	/** Register typed syntax as soon as parsing identifies it, or refresh its retained identity. */
	public function collect_member_owner(type_declaration_node $node, scope $scope, int $index): void
	{
		if (($node->declaration_kind !== type_declaration_kind::union_type)
			&& ($node->declaration_kind !== type_declaration_kind::struct_type)
			&& ($node->declaration_kind !== type_declaration_kind::class_type)
			&& ($node->declaration_kind !== type_declaration_kind::interface_type)
			&& ($node->declaration_kind !== type_declaration_kind::trait_type)) {
			throw new \RuntimeException('Unsupported member-owning declaration kind');
		}
		if ($this->reuse($node, $index)) {
			return;
		}
		$entry /** collected_member_owner */;
		if ($node->declaration_kind === type_declaration_kind::interface_type) {
			$entry = new collected_interface($this->collection, $node);
		}
		elseif ($node->declaration_kind === type_declaration_kind::trait_type) {
			$entry = new collected_trait($this->collection, $node);
		}
		else {
			$entry = new collected_record($this->collection, $node);
		}
		$this->register_source_type($entry, $scope, $node->name, $index);
	}

	/** Enum declarations share the source-type registration and retained-identity protocol. */
	public function collect_enum(enum_node $node, scope $scope, int $index): void
	{
		if ($this->reuse($node, $index)) {
			return;
		}
		$entry = new collected_enum($this->collection, $node);
		$this->register_source_type($entry, $scope, $node->name, $index);
	}

	/** All nominal source declarations share publication and association ownership. */
	private function register_source_type(collected_type_definition $entry, scope $scope, string $name, int $index): void
	{
		$entry->retained_symbol = true;
		$this->append($entry, $scope, $name, $index);
		if ($this->global === null) {
			self::define_source_type($entry, $scope);
		}
		else {
			// Parallel parse workers share registry identities and source associations,
			// not just the global scope index. Publish that transaction under the same lock.
			task_synchronize(function () use ($entry, $scope): void {
				self::define_source_type($entry, $scope);
			});
		}
		$this->publish($entry, $scope);
	}

	private static function define_source_type(collected_type_definition $entry, scope $scope): void
	{
		$definition = Source_Types::definition($entry);
		$scope->register_type_as($entry->name, object_cast($definition, type_definition_i::class));
	}

	/** Register typed syntax as soon as parsing identifies it, or refresh its retained identity. */
	public function collect_field(property_node $node, scope $scope, int $index): void
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

	/** Record a constant occurrence without resolving it against the host PHP runtime. */
	public function collect_constant_reference(constant_reference_node $node, scope $scope, int $index): void
	{
		$entry = new collected_constant_reference($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_variable_write(variable_reference_node $node, scope $scope, int $index): void
	{
		$entry = new collected_variable_write($this->collection, $node);
		$this->append($entry, $scope, $node->name, $index);
	}

	/** Record this occurrence without resolving names or allocating preparation facts. */
	public function collect_call_reference(call_node $node, scope $scope, int $index): void
	{
		$entry = new collected_call_reference($this->collection, $node);
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
		$entry->scope = weak($scope);
		$entry->token_index = $index;
		$entry->revision = $this->collection->revision;
		$entries /** Storage<collected_name> */ = $this->collection->entries;
		$entry->local_index = $entries->append($entry);
		$node->attach_occurrence($entry);
		$entry->index_collection($this);
	}

	/** Only source-body definitions enter shared indexes, under the existing task lock. */
	private function publish(collected_definition $entry, scope $declaration_scope): void
	{
		if ($declaration_scope !== $this->source_scope) {
			return;
		}
		if ($this->global === null) {
			return;
		}
		$global /** scope */ = $this->global;
		$entry->exported = true;
		task_synchronize(function () use ($entry, $declaration_scope, $global): void {
			Scope_Publication::register($declaration_scope, $global, $entry);
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

	/** Maintain the constant-reference work list during insertion and retained refresh. */
	public function index_constant_reference(collected_constant_reference $entry): void
	{
		$this->collection->constant_references[] = $entry->local_index;
	}

	/** Maintain the existing occurrence list during insertion and retained-body refresh. */
	public function index_call_reference(collected_call_reference $entry): void
	{
		$this->collection->call_references[] = $entry->local_index;
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
		$this->refresh_anonymous_callables();
		self::sweep($this->collection, false);
		return $this->collection;
	}

	/** Retained syntax keeps its identity; replaced and speculative occurrences retire. */
	private function refresh_anonymous_callables(): void
	{
		$identities /** Storage<anonymous_callable_identity> */ = $this->collection->anonymous_callables;
		foreach ($identities as $identity) {
			if ($identity->deleted) { continue; }
			$identity->revision = $this->retained_revision($identity->syntax()->start_token(), $identity->revision);
			$identity->deleted = $identity->revision !== (int)$this->collection->revision;
		}
	}

	/** One token-range reconciliation rule for named uses and anonymous occurrences. */
	private function retained_revision(int $token_index, int $revision): int
	{
		$ranges /** Storage<retained_token_range> */ = $this->collection->token_snapshot()->retained_ranges;
		foreach ($ranges as $range) {
			if (($token_index >= $range->first) && ($token_index < $range->end)) {
				return (int)$this->collection->revision;
			}
			$current_end = $range->current_first + ($range->end - $range->first);
			if (($token_index >= $range->current_first) && ($token_index < $current_end)) { return 0; }
		}
		return $revision;
	}

	/** Retire obsolete occurrences after reuse decisions, rebuilding current work lists once. */
	private function refresh_occurrences(): void
	{
		$collection = $this->collection;
		$collection->defined_elements = /** vector<int> */ [];
		$collection->variable_references = /** vector<int> */ [];
		$collection->constant_references = /** vector<int> */ [];
		$collection->call_references = /** vector<int> */ [];
		$collection->type_references = /** vector<int> */ [];
		$collection->field_references = /** vector<int> */ [];
		$collection->pending_bindings = /** vector<int> */ [];
		$entries /** Storage<collected_name> */ = $collection->entries;
		$retired /** vector<int> */ = [];
		foreach ($entries as $entry)
		{
			// Reused regions retain old identities; temporary parses of their new interval retire.
			$selected_revision = $this->retained_revision((int)$entry->token_index, (int)$entry->revision);
			if ($selected_revision === 0) { $entry->revision = 0; }
			elseif ($selected_revision === (int)$collection->revision) { $entry->revision = $collection->revision; }
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
