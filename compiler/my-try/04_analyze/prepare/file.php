<?php

/* Prepare signatures and executable bodies without changing source syntax or scopes. */
namespace scpp\compiler;

final class File_Preparation
{
	private collected_file $source;
	private scope $language;

	public function __construct(collected_file $source, scope $language_scope)
	{
		$this->source = $source;
		$this->language = $language_scope;
	}

	/** Standalone callers use the same incremental worker and selections as the compiler. */
	public function prepare(): prepared_file
	{
		$sources /** Storage<collected_file> */ = new Storage();
		$sources->append($this->source);
		$prepared /** Storage<prepared_file> */ = (new Preparation_Worker($this->language))->prepare($sources);
		return $prepared[0];
	}

	/** Workers control body traversal; specializations dispatch individual operations. */
	public static function prepare_statements(function_body_node $body, preparation_context $context): void
	{
		$nodes /** Storage<statement_node> */ = $body->statements;
		$worker = new Syntax_Preparation($context);
		foreach ($nodes as $node) {
			$node->prepare($worker);
		}
	}

	/** Establish source-order local storage or resolve a member write before publishing facts. */
	public static function prepare_storage(?collected_name $entry, ?type_node $type_syntax, ?assignable_expression_node $target, ?expression_node $initializer, preparation_context $context): prepared_binding
	{
		$binding = new prepared_binding();
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		if ($target !== null)
		{
			$write_target /** assignable_expression_node */ = $target;
			$place = Syntax_Preparation::expression($write_target, $context);
			if (!$place->addressable) {
				throw new \RuntimeException('S2S assignment requires stable storage');
			}

			$binding->type = $place->type;
			$binding->resolved_kind = binding_kind::assignment;

			// Member writes are emitted through their prepared target, not a local declaration.
			$member = object_cast($target, field_access_node::class)->require_preparation();
			$binding->declaration = $member->field->declaration;
		}
		else
		{

			$previous /** vector<prepared_storage> */ = $locals->named($entry->name);
			if (($type_syntax !== null) || (q_count($previous) === 0))
			{
				$binding->resolved_kind = binding_kind::declaration;
				$binding->declaration = $entry;
				if ($type_syntax !== null) {
					$type_node /** type_node */ = $type_syntax;
					$binding->type = Declaration_Preparation::type($type_node, $context);
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

		// An initializer cannot see the declaration currently being introduced.
		if ($initializer !== null)
		{
			$value_node /** expression_node */ = $initializer;
			$value = Syntax_Preparation::expression($value_node, $context);
			if (($binding->resolved_kind === binding_kind::declaration) && ($type_syntax === null)) {
				$binding->type = $value->type;
			}
			Declaration_Preparation::require_assignable($binding->type, $value->type);
		}

		// Publish complete facts before making a new declaration available to later statements.
		Declaration_Preparation::require_value_type($binding->type);

		if ($binding->resolved_kind === binding_kind::declaration) {

			$locals->add($entry->name, $binding);
		}
		return $binding;
	}

	public static function prepare_expression_statement(expression_statement_node $syntax, preparation_context $context): void
	{
		$expression = $syntax->expression;
		Syntax_Preparation::expression($expression, $context);
	}

	/** Function returns use the signature; program-entry returns remain scalar exit values. */
	public static function prepare_return(return_node $syntax, preparation_context $context): void
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
		$value = Syntax_Preparation::expression($expression, $context);
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
		$value->decimal = Integer_Literals::decimal($context->collection->token_snapshot()->text_at($node->start_token()));
		$value->type = $context->integer;
		return $value;
	}

	/** Retain source spelling so the target, rather than host PHP, performs rounding. */
	public static function prepare_float(ast_node $node, preparation_context $context): prepared_float_literal
	{
		$value = new prepared_float_literal();
		$value->decimal = $context->collection->token_snapshot()->text_at($node->start_token());
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
		$entry = $node->occurrence();
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
