<?php

/* Prepare signatures and executable bodies without changing source syntax or scopes. */
namespace scpp\compiler;

final class File_Preparation
{
	private preparation_context $context;

	/** Every invocation owns fresh results and source-order declaration state. */
	public function __construct(collected_file $source, scope $language_scope)
	{
		$context = new preparation_context();
		$context->collection = $source;
		$context->locals = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();
		$context->integer = Language_Types::integer($language_scope);
		$context->boolean = Language_Types::boolean($language_scope);
		$context->floating = Language_Types::floating($language_scope);

		$this->context = $context;
	}

	/** Prepare declarations before bodies and discard every attached fact on failure. */
	public function prepare(): prepared_file
	{
		$context = $this->context;
		Preparation_Cleanup::tree($context->collection->root);
		$context->locals = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();

		try
		{
			$child = $context->collection->root->first_child();
			while ($child !== null) {
				$node /** ast_node */ = $child;
				$node->payload()->prepare_declaration($node, $context);
				$child = $node->next();
			}
			self::prepare_statements($context->collection->root, $context);
		}
		catch (\Throwable $error) {
			Preparation_Cleanup::tree($context->collection->root);
			throw $error;
		}

		$result = new prepared_file();
		$result->source = $context->collection;
		return $result;
	}

	/** Workers control body traversal; specializations dispatch individual operations. */
	public static function prepare_statements(ast_node $body, preparation_context $context): void
	{
		$child = $body->first_child();
		while ($child !== null) {
			$node /** ast_node */ = $child;
			$node->payload()->prepare_statement($node, $context);
			$child = $node->next();
		}
	}

	/** Establish source-order local storage or resolve a member write before publishing facts. */
	public static function prepare_binding(binding_structure $syntax, preparation_context $context): void
	{
		$binding = new prepared_binding();
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		if ($syntax->target !== null)
		{
			$target /** ast_node */ = $syntax->target;
			$place = $target->payload()->prepare_expression($target, $context);
			if (!$place->addressable) {
				throw new \RuntimeException('S2S assignment requires stable storage');
			}
			$binding->type = $place->type;
			$binding->resolved_kind = binding_kind::assignment;
			// Member writes are emitted through their prepared target, not a local declaration.
			$member = Syntax_Nodes::field_access_data($target)->require_preparation();
			$binding->declaration = $member->field->declaration;
		}
		else
		{
			$entry = $syntax->occurrence();
			$previous /** vector<prepared_storage> */ = $locals->named($entry->name);
			if (($syntax->type_syntax !== null) || (q_count($previous) === 0))
			{
				$binding->resolved_kind = binding_kind::declaration;
				$binding->declaration = $entry;
				if ($syntax->type_syntax !== null) {
					$type_node /** ast_node */ = $syntax->type_syntax;
					$binding->type = Declaration_Preparation::type($type_node);
				}
			}
			else
			{
				if (q_count($previous) !== 1) {
					throw new \RuntimeException('S2S needs one local assignment target');
				}
				$binding->resolved_kind = binding_kind::assignment;
				$binding->declaration = $previous[0]->declaration;
				$binding->type = $previous[0]->type;
			}
		}

		if ($syntax->value !== null)
		{
			$initializer /** ast_node */ = $syntax->value;
			$value = $initializer->payload()->prepare_expression($initializer, $context);
			if (($binding->resolved_kind === binding_kind::declaration) && ($syntax->type_syntax === null)) {
				$binding->type = $value->type;
			}
			Declaration_Preparation::require_assignable($binding->type, $value->type);
		}
		Declaration_Preparation::require_value_type($binding->type);
		$syntax->set_preparation($binding);
		if ($binding->resolved_kind === binding_kind::declaration) {
			$entry = $syntax->occurrence();
			$locals->add($entry->name, $binding);
		}
	}

	public static function prepare_expression_statement(expression_statement_structure $syntax, preparation_context $context): void
	{
		$expression = $syntax->expression;
		$expression->payload()->prepare_expression($expression, $context);
	}

	/** Function returns use the signature; program-entry returns remain scalar exit values. */
	public static function prepare_return(return_structure $syntax, preparation_context $context): void
	{
		if ($syntax->expression === null)
		{
			if ($context->return_type !== null) {
				$type /** type_definition */ = $context->return_type;
				if ($type->kind !== type_kind::void_type) {
					throw new \RuntimeException('S2S non-void return requires a value');
				}
			}
			return;
		}
		$expression /** ast_node */ = $syntax->expression;
		$value = $expression->payload()->prepare_expression($expression, $context);
		if ($context->return_type !== null) {
			$type /** type_definition */ = $context->return_type;
			Declaration_Preparation::require_assignable($type, $value->type);
		}
		elseif (($value->type->kind === type_kind::record) || ($value->type->kind === type_kind::void_type)) {
			throw new \RuntimeException('S2S entry return requires a scalar value');
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
		$entry = $node->payload()->occurrence();
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		$targets /** vector<prepared_storage> */ = $locals->named($entry->name);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
		}

		$reference = new prepared_variable_reference();
		$reference->declaration = $targets[0]->declaration;
		$reference->type = $targets[0]->type;
		$reference->addressable = true;
		return $reference;
	}
}
