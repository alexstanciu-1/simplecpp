<?php

/* Prepare the straight-line scalar slice by attaching facts without changing source syntax or scopes. */
namespace scpp\compiler;

final class File_Preparation
{
	private preparation_context $context;

	/** Every invocation owns fresh results and source-order declaration state. */
	public function __construct(collected_file $source, scope $language_scope)
	{
		$context = new preparation_context();
		$context->collection = $source;
		$context->locals = new scope();
		$context->integer = Language_Types::integer($language_scope);
		$context->boolean = Language_Types::boolean($language_scope);
		$context->floating = Language_Types::floating($language_scope);

		$entries /** Storage<collected_name> */ = $source->entries;
		foreach ($entries as $entry) {
			if ($entry->changes !== \scpp\compiler\SYNC_DELETED) {
				$context->occurrences[$entry->token_index] = $entry;
			}
		}

		$this->context = $context;
	}

	/** This slice handles one top-level body; other constructs remain explicit blockers. */
	public function prepare(): prepared_file
	{
		$context = $this->context;
		Preparation_Cleanup::tree($context->collection->root);
		$context->locals = new scope();

		try
		{
			$child = $context->collection->root->first_child();
			while ($child !== null) {
				$node /** ast_node */ = $child;
				$child = $node->next();
				$node->payload()->prepare_statement($node, $context);
			}
		}
		catch (\Throwable $error) {
			Preparation_Cleanup::tree($context->collection->root);
			throw $error;
		}

		$result = new prepared_file();
		$result->source = $context->collection;
		return $result;
	}

	/** Explicit types resolve through source scopes; inference uses the initializer's type. */
	public static function prepare_binding(binding_structure $syntax, preparation_context $context): void
	{
		if (($syntax->target !== null) || ($syntax->value === null)) {
			throw new \RuntimeException('S2S currently requires a local binding with an initializer');
		}

		// Establish initializer facts before deciding declaration versus reassignment.
		$entry = $context->occurrences[(int) $syntax->name_token_index];
		$initializer /** ast_node */ = $syntax->value;
		$value = $initializer->payload()->prepare_expression($initializer, $context);
		$binding = new prepared_binding();
		$binding->type = $value->type;
		$previous /** vector<collected_name> */ = $context->locals->variables_named($entry->name);

		// Explicit source types resolve through lexical scopes; inference keeps the initializer type.
		if ($syntax->type_syntax !== null)
		{
			$type_node /** ast_node */ = $syntax->type_syntax;
			if ($type_node->kind() !== node_kind::identifier) {
				throw new \RuntimeException('S2S constructed types are not supported yet');
			}
			$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
			$types = Scope_Lookup::types($lexical_scope, $context->collection->token_snapshot()->text_at((int) $type_node->token_index));
			if (q_count($types) !== 1) {
				throw new \RuntimeException('S2S needs one resolved local type');
			}
			$binding->type = $types[0];
		}

		if (($syntax->type_syntax !== null) || (q_count($previous) === 0)) {
			$binding->resolved_kind = binding_kind::declaration;
			$binding->declaration = $entry;
			$context->locals->register($entry);
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

		// Publish only a complete binding in the currently supported scalar slice.
		if ($binding->type !== $value->type) {
			throw new \RuntimeException('S2S binding requires matching scalar types; conversions are not implemented');
		}
		if (($binding->type !== $context->integer) && ($binding->type !== $context->boolean) && ($binding->type !== $context->floating)) {
			throw new \RuntimeException('S2S binding requires a supported canonical scalar type');
		}
		$syntax->set_preparation($binding);
	}

	/** Return owns evaluation of its optional expression; no generic child walk runs here. */
	public static function prepare_return(return_structure $syntax, preparation_context $context): void
	{
		if ($syntax->expression !== null) {
			$expression /** ast_node */ = $syntax->expression;
			$expression->payload()->prepare_expression($expression, $context);
		}
	}

	public static function prepare_integer(ast_node $node, preparation_context $context): prepared_integer_literal
	{
		$value = new prepared_integer_literal();
		$value->decimal = Integer_Literals::decimal($context->collection->token_snapshot()->text_at((int) $node->token_index));
		$value->type = $context->integer;
		return $value;
	}

	/** Retain source spelling so the target, rather than host PHP, performs rounding. */
	public static function prepare_float(ast_node $node, preparation_context $context): prepared_float_literal
	{
		$value = new prepared_float_literal();
		$value->decimal = $context->collection->token_snapshot()->text_at((int) $node->token_index);
		$value->type = $context->floating;
		return $value;
	}

	public static function prepare_boolean(bool $value, preparation_context $context): prepared_boolean_literal
	{
		$facts = new prepared_boolean_literal();
		$facts->value = $value;
		$facts->type = $context->boolean;
		return $facts;
	}

	/** Resolve source-order locals without modifying the retained declaration inventory. */
	public static function prepare_reference(ast_node $node, preparation_context $context): prepared_variable_reference
	{
		$entry = $context->occurrences[(int) $node->token_index];
		$targets /** vector<collected_name> */ = $context->locals->variables_named($entry->name);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
		}

		$reference = new prepared_variable_reference();
		$reference->declaration = $targets[0];
		$reference->type = Syntax_Nodes::binding_data($targets[0]->node)->require_preparation()->type;
		return $reference;
	}
}
