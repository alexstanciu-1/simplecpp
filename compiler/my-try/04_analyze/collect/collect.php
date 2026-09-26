<?php

/*
 * Role: collect canonical occurrences and register file-local declarations.
 * Call map: Parser -> Symbol_Collector::record -> Symbol_Collector::finish.
 */
namespace scpp\compiler;

final class Symbol_Collector
{
	private collected_file $collection;
	private bool $finished = false;

	public function __construct(token_list $source)
	{
		$this->collection = new collected_file($source);
	}

	/** Append one occurrence during parsing; never look up, merge or reject a name. */
	public function record(ast_node $node, int $token_index, collected_name_kind $kind, scope $scope, string $name): int
	{
		if ($this->finished) {
			throw new \LogicException('Collection is already finished');
		}
		$entry = new collected_name($this->collection);
		$entry->name = $name;
		$entry->kind = $kind;
		$entry->scope = $scope;
		$entry->node = $node;
		$entry->token_index = $token_index;
		$entries /** Storage<collected_name> */ = $this->collection->entries;
		$entry->local_index = $entries->append($entry);
		if (($kind === collected_name_kind::field_declaration) || ($kind === collected_name_kind::struct_declaration) || ($kind === collected_name_kind::variable_declaration) || ($kind === collected_name_kind::function_declaration)) {
			$this->collection->defined_elements[] = $entry->local_index;
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

	/** Publish declaration references only after the whole file has parsed successfully. */
	public function finish(ast_node $root): collected_file
	{
		if ($this->finished) {
			throw new \LogicException('Collection is already finished');
		}
		$this->collection->root = $root;
		$entries /** Storage<collected_name> */ = $this->collection->entries;
		foreach ($this->collection->defined_elements as $index)
		{
			$entry = $entries[$index];
			if ($entry->kind === collected_name_kind::field_declaration) {
				continue;
			}
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			if ($entry->kind === collected_name_kind::struct_declaration) {
				$entry_scope->register_type(Source_Types::definition($entry));
			}
			else {
				$entry_scope->register($entry);
			}
		}
		$this->finished = true;
		return $this->collection;
	}
}
