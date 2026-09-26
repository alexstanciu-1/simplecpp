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
				$node /** ast_node */ = $child;
				$child = $node->next();
				if ($node->kind() === node_kind::variable_binding_statement) {
					$this->binding(Syntax_Nodes::binding_data($node));
				}
				elseif ($node->kind() === node_kind::return_statement) {
					$return_data = Syntax_Nodes::return_data($node);
					if ($return_data->expression !== null) {
						$expression /** ast_node */ = $return_data->expression;
						$this->expression($expression);
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
	private function binding(binding_structure $syntax): void
	{
		if (($syntax->target !== null) || ($syntax->value === null)) {
			throw new \RuntimeException('S2S currently requires a local binding with an initializer');
		}
		$entry = $this->occurrences[(int) $syntax->name_token_index];
		$initializer /** ast_node */ = $syntax->value;
		$value = $this->expression($initializer);
		$binding = new prepared_binding();
		$binding->type = $value->type;
		$previous /** vector<collected_name> */ = $this->locals->variables_named($entry->name);
		if ($syntax->type_syntax !== null)
		{
			$type_node /** ast_node */ = $syntax->type_syntax;
			if ($type_node->kind() !== node_kind::identifier) {
				throw new \RuntimeException('S2S constructed types are not supported yet');
			}
			$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
			$types = Scope_Lookup::types($lexical_scope, $this->collection->token_snapshot()->text_at((int) $type_node->token_index));
			if (q_count($types) !== 1) {
				throw new \RuntimeException('S2S needs one resolved local type');
			}
			$binding->type = $types[0];
		}
		if (($syntax->type_syntax !== null) || (q_count($previous) === 0)) {
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
			$binding->type = Syntax_Nodes::binding_data($previous[0]->node)->require_preparation()->type;
		}
		if (($binding->type !== $this->integer) || ($value->type !== $this->integer)) {
			throw new \RuntimeException('S2S binding lowering currently supports canonical int only');
		}
		$syntax->set_preparation($binding);
	}

	/** Literal and reference expressions share resolved type identity before emission. */
	private function expression(ast_node $node): prepared_expression
	{
		if ($node->kind() === node_kind::integer_literal) {
			$value = new prepared_integer_literal();
			$value->decimal = Integer_Literals::decimal($this->collection->token_snapshot()->text_at((int) $node->token_index));
			$value->type = $this->integer;
			Syntax_Nodes::integer_data($node)->set_preparation($value);
			return $value;
		}
		elseif ($node->kind() === node_kind::variable_reference)
		{
			$entry = $this->occurrences[(int) $node->token_index];
			$targets /** vector<collected_name> */ = $this->locals->variables_named($entry->name);
			if (q_count($targets) !== 1) {
				throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
			}
			$reference = new prepared_variable_reference();
			$reference->declaration = $targets[0];
			$reference->type = Syntax_Nodes::binding_data($targets[0]->node)->require_preparation()->type;
			Syntax_Nodes::reference_data($node)->set_preparation($reference);
			return $reference;
		}
		else {
			throw new \RuntimeException('S2S expression lowering is not implemented for this form');
		}
	}
}
