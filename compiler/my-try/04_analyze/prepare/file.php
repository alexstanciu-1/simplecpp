<?php

/* Prepare the straight-line integer slice by attaching facts without changing source syntax or scopes. */
namespace scpp\compiler;

final class File_Preparation
{
	private collected_file $collection;
	/** Invocation-local declaration index; never published into parsed source scopes. */
	private scope $locals;
	private type_definition $integer;
	private array $occurrences /** hash<collected_name, int> */ = [];

	/** Every invocation owns fresh results and source-order declaration state. */
	public function __construct(collected_file $source, scope $language_scope)
	{
		$this->collection = $source;
		$this->locals = new scope();
		$this->integer = Language_Types::integer($language_scope);
		$entries /** Storage<collected_name> */ = $source->entries;
		foreach ($entries as $entry) {
			if ($entry->changes !== \scpp\compiler\SYNC_DELETED) {
				$this->occurrences[$entry->token_index] = $entry;
			}
		}
	}

	/** This slice handles one top-level body; other constructs remain explicit blockers. */
	public function prepare(): prepared_file
	{
		Preparation_Cleanup::tree($this->collection->root);
		$this->locals = new scope();
		try
		{
			$child = $this->collection->root->first_child();
			while ($child !== null)
			{
				$node = object_cast($child, ast_node::class);
				$child = $node->next();
				if ($node->kind === node_kind::variable_binding_statement) {
					$this->binding(object_cast($node, variable_binding_statement_node::class));
				}
				elseif ($node->kind === node_kind::return_statement) {
					$return_node = object_cast($node, return_statement_node::class);
					if ($return_node->expression() !== null) {
						$this->expression(object_cast($return_node->expression(), ast_node::class));
					}
				}
				else {
					throw new \RuntimeException('S2S preparation does not support this statement yet');
				}
			}
		}
		catch (\Throwable $error) {
			Preparation_Cleanup::tree($this->collection->root);
			throw $error;
		}
		$result = new prepared_file();
		$result->source = $this->collection;
		return $result;
	}

	/** Explicit types resolve through source scopes; inference uses the initializer's type. */
	private function binding(variable_binding_statement_node $node): void
	{
		if (($node->target() !== null) || ($node->initializer() === null)) {
			throw new \RuntimeException('S2S currently requires a local binding with an initializer');
		}
		$entry = $this->occurrences[(int) $node->name_index()];
		$value = $this->expression(object_cast($node->initializer(), ast_node::class));
		$binding = new prepared_binding();
		$binding->type = $value->type;
		$previous /** vector<collected_name> */ = $this->locals->variables_named($entry->name);
		if ($node->declared_type() !== null)
		{
			$type_node = object_cast($node->declared_type(), ast_node::class);
			if ($type_node->kind !== node_kind::identifier) {
				throw new \RuntimeException('S2S constructed types are not supported yet');
			}
			$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
			$types = Scope_Lookup::types($lexical_scope, $this->collection->token_snapshot()->text_at((int) $type_node->token_index));
			if (q_count($types) !== 1) {
				throw new \RuntimeException('S2S needs one resolved local type');
			}
			$binding->type = $types[0];
		}
		if (($node->declared_type() !== null) || (q_count($previous) === 0)) {
			$binding->resolved_kind = binding_kind::declaration;
			$binding->declaration = $entry;
			$this->locals->register($entry);
		}
		else
		{
			if (q_count($previous) !== 1) {
				throw new \RuntimeException('S2S needs one local assignment target');
			}
			$binding->resolved_kind = binding_kind::assignment;
			$binding->declaration = $previous[0];
			$binding->type = object_cast($previous[0]->node, variable_binding_statement_node::class)->require_preparation()->type;
		}
		if (($binding->type !== $this->integer) || ($value->type !== $this->integer)) {
			throw new \RuntimeException('S2S binding lowering currently supports canonical int only');
		}
		$node->set_preparation($binding);
	}

	/** Literal and reference expressions share resolved type identity before emission. */
	private function expression(ast_node $node): prepared_expression
	{
		$value = new prepared_expression();
		if ($node->kind === node_kind::integer_literal) {
			$value->literal = Integer_Literals::decimal($this->collection->token_snapshot()->text_at((int) $node->token_index));
			$value->type = $this->integer;
			$literal = object_cast($node, integer_literal_node::class);
			$literal->set_preparation($value);
		}
		elseif ($node->kind === node_kind::variable_reference)
		{
			$entry = $this->occurrences[(int) $node->token_index];
			$targets /** vector<collected_name> */ = $this->locals->variables_named($entry->name);
			if (q_count($targets) !== 1) {
				throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
			}
			$value->declaration = $targets[0];
			$value->type = object_cast($targets[0]->node, variable_binding_statement_node::class)->require_preparation()->type;
			$reference = object_cast($node, variable_reference_node::class);
			$reference->set_preparation($value);
		}
		else {
			throw new \RuntimeException('S2S expression lowering is not implemented for this form');
		}
		return $value;
	}
}
