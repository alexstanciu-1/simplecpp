<?php

/* Prepare function signatures and record fields before their executable consumers. */
namespace scpp\compiler;

final class Declaration_Preparation
{
	/** Publish a complete signature before any body so calls do not depend on source order. */
	public static function prepare_function(function_node $syntax, preparation_context $context): void
	{
		if (q_count($syntax->template_parameters) !== 0) {
			throw new \RuntimeException('S2S function templates are deferred');
		}

		$facts = new prepared_function();
		$facts->return_type = Type_Preparation::type($syntax->return_type, $context);

		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		$nodes /** Storage<parameter_node> */ = $syntax->parameters;
		foreach ($nodes as $parameter) {
			$parameter->prepare($context);
			$parameters->append($parameter->require_preparation());
		}

		$syntax->set_preparation($facts);
	}

	/** Prepare one parameter with the same signature context used by its enclosing function. */
	public static function prepare_parameter(parameter_node $node, preparation_context $context): void
	{
		$facts = new prepared_parameter();
		$facts->declaration = $node->occurrence();
		$facts->type = Type_Preparation::type($node->type_syntax, $context);
		Type_Preparation::require_value_type($facts->type);
		$facts->mode = $node->mode;
		$node->set_preparation($facts);
	}

	/** Fields belong to their record, never to the surrounding local-variable scope. */
	public static function prepare_struct(struct_node $syntax, preparation_context $context): void
	{
		$facts = new prepared_record();
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;

		$nodes /** Storage<field_node> */ = $syntax->fields;
		foreach ($nodes as $field) {
			$field->prepare($context);
			$fields->add($field->name, $field->require_preparation());
		}
		$syntax->set_preparation($facts);
	}

	/** Keep field eligibility within the current compact-layout contract. */
	public static function prepare_field(field_node $field, preparation_context $context): void
	{
		$prepared = new prepared_field();
		$prepared->declaration = $field->occurrence();
		$prepared->type = Type_Preparation::type($field->type_syntax, $context);
		$type = Type_Preparation::canonical($prepared->type);
		$definition = $type->definition();
		$fixed_integer = ($type->family() === type_family::integer) && ($definition->name() !== 'int');
		$source_record = Type_Preparation::source_record($prepared->type) !== null;
		if ((!$fixed_integer) && ($type->family() !== type_family::boolean) && (!$source_record)) {
			throw new \RuntimeException('S2S struct fields require bool, fixed-width integers or supported structs');
		}
		$context->worker->require_record($prepared->type, $context);
		$field->set_preparation($prepared);
	}
}
