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

	/** Matching is owner-local; duplicate spellings consume existing identities in encounter order. */
	public function previous(scope $scope, string $name, collected_name_kind $kind): ?ast_node
	{
		$entries /** vector<collected_name> */ = [];
		if ($kind === collected_name_kind::function_declaration) {
			$entries = $scope->functions_named($name);
		}
		elseif ($kind === collected_name_kind::struct_declaration) {
			$entries = $scope->source_types_named($name);
		}
		else {
			$entries = $scope->variables_named($name);
		}
		foreach ($entries as $entry) {
			if (($entry->kind === $kind) && $entry->retained_symbol && ((int)$entry->revision !== (int)$this->collection->revision)) {
				return $entry->node;
			}
		}
		return null;
	}

	/** Reuse the canonical occurrence when the parser has matched a declaration node. */
	public function declaration(ast_node $node, int $index, collected_name_kind $kind, scope $scope, string $name, bool $existing): void
	{
		if ($existing) {
			$entry = $node->occurrence();
			if ($entry->change_status === change_state::deleted) {
				$entry->change_status = change_state::added;
			}
			$entry->token_index = $index;
		}
		else
		{
			$entry = $this->append($node, $index, $kind, $scope, $name);
			$entry->retained_symbol = true;
			$this->collection->defined_elements[] = $entry->local_index;
			if ($kind === collected_name_kind::struct_declaration) {
				$scope->register_type(Source_Types::definition($entry));
			}
			else {
				$scope->register($entry);
			}
			if (($scope === $this->file_scope) && ($this->global !== null))
			{
				$global /** scope */ = $this->global;
				if (($kind === collected_name_kind::struct_declaration) || ($kind === collected_name_kind::function_declaration)) {
					$entry->exported = true;
					task_synchronize(function () use ($entry, $scope, $global): void {
						Scope_Publication::register($scope, $global, $entry);
					});
				}
			}
		}
		$entry->revision = $this->collection->revision;
	}

	/** Record unresolved uses and replaceable body declarations without lookup or binding. */
	public function record(ast_node $node, int $token_index, collected_name_kind $kind, scope $scope, string $name): int
	{
		$entry = $this->append($node, $token_index, $kind, $scope, $name);
		if ($kind === collected_name_kind::variable_declaration) {
			$this->collection->defined_elements[] = $entry->local_index;
			$scope->register($entry);
		}
		elseif ($kind === collected_name_kind::field_reference) {
			$this->collection->field_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::variable_reference) {
			$this->collection->variable_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::type_reference) {
			$this->collection->type_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::function_reference) {
			$this->collection->function_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::binding) {
			$this->collection->pending_bindings[] = $entry->local_index;
		}
		return $entry->local_index;
	}

	/** Allocate only new identities; the storage index is never reused by a later occurrence. */
	private function append(ast_node $node, int $index, collected_name_kind $kind, scope $scope, string $name): collected_name
	{
		if ($node->optional_occurrence() !== null) {
			throw new \LogicException('Syntax already has a collected occurrence');
		}
		$entry = new collected_name($this->collection);
		$entry->name = $name;
		$entry->kind = $kind;
		$entry->scope = $scope;
		$entry->node = $node;
		$entry->token_index = $index;
		$entry->revision = $this->collection->revision;
		$entries /** Storage<collected_name> */ = $this->collection->entries;
		$entry->local_index = $entries->append($entry);
		$node->attach_occurrence($entry);
		return $entry;
	}

	/** A completed file can retire unseen local symbols; exported rows wait until the join. */
	public function finish(file_node $root): collected_file
	{
		$this->collection->root = $root;
		$this->refresh_occurrences();
		self::sweep($this->collection, false);
		return $this->collection;
	}

	/** Retained bodies stamp their old occurrences instead of allocating replacement identities. */
	public function retain(collected_name $entry, int $delta): void
	{
		$entry->revision = $this->collection->revision;
		$entry->token_index = $entry->token_index + $delta;
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
		foreach ($entries as $entry)
		{
			$index = $entry->local_index;
			if (!$entry->retained_symbol && ((int)$entry->revision !== (int)$collection->revision)) {
				$retired[] = $index;
				continue;
			}
			$kind = $entry->kind;
			if ($entry->retained_symbol || ($kind === collected_name_kind::variable_declaration)) {
				$collection->defined_elements[] = $index;
			}
			elseif ($kind === collected_name_kind::variable_reference) {
				$collection->variable_references[] = $index;
			}
			elseif ($kind === collected_name_kind::function_reference) {
				$collection->function_references[] = $index;
			}
			elseif ($kind === collected_name_kind::type_reference) {
				$collection->type_references[] = $index;
			}
			elseif ($kind === collected_name_kind::field_reference) {
				$collection->field_references[] = $index;
			}
			elseif ($kind === collected_name_kind::binding) {
				$collection->pending_bindings[] = $index;
			}
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
			if ($entry->retained_symbol && ($entry->exported === $exported) && ((int)$entry->revision !== (int)$collection->revision)) {
				$entry->change_status = change_state::deleted;
			}
		}
	}
}
