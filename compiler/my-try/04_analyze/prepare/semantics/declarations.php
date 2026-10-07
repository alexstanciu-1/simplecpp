<?php

/* Prepare function signatures and record fields before their executable consumers. */
namespace scpp\compiler;

final class Declaration_Preparation
{
	/** Publish a complete signature before any body so calls do not depend on source order. */
	public static function prepare_function(function_node $syntax, preparation_context $context): void
	{
		$declaration = $syntax->occurrence();
		$declaring_scope = object_cast(weakref_get($declaration->scope), scope::class);
		// Observe the name pool so adding/removing an unused duplicate invalidates this signature.
		$candidates = Observed_Lookup::functions($declaring_scope, $declaration->name, $context);
		if (q_count($candidates) !== 1) {
			throw new \RuntimeException('S2S duplicate function name: ' . $declaration->name);
		}
		if (q_count($syntax->template_parameters) !== 0) {
			throw new \RuntimeException('S2S function templates are deferred');
		}

		$facts = new prepared_function();
		$facts->signature->return_type = Type_Preparation::type($syntax->require_return_type(), $context);

		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		$contracts /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$nodes /** Storage<parameter_node> */ = $syntax->parameters;
		$names /** hash<bool> */ = [];
		foreach ($nodes as $parameter) {
			if (isset($names[$parameter->name])) {
				throw new \RuntimeException('S2S duplicate parameter name: ' . $parameter->name);
			}
			$names[$parameter->name] = true;
			self::prepare_parameter($parameter, $context);
			$prepared = $parameter->require_preparation();
			$parameters->append($prepared);
			$contracts->append($prepared->contract);
		}

		Reference_Preparation::prepare_signature($syntax, $facts->signature);
		Callable_Contracts::validate($facts->signature);
		$syntax->set_preparation($facts);
	}

	/** Prepare one parameter with the same signature context used by its enclosing function. */
	public static function prepare_parameter(parameter_node $node, preparation_context $context): void
	{
		$facts = new prepared_parameter();
		$facts->declaration = $node->occurrence();
		$contract = new callable_parameter();
		$contract->type = Type_Preparation::type($node->type_syntax, $context);
		Type_Preparation::require_value_type($contract->type);
		$contract->mode = $node->mode;
		$contract->access = $node->access;
		if ($node->variadic) {
			if (($node->mode !== passing_mode::value) || ($node->default_value !== null)) {
				throw new \RuntimeException('S2S variadic parameters must be by value without an explicit default');
			}
			Capability_Preparation::require_copy($contract->type, $context);
			$contract->element_type = $contract->type;
			$contract->type = Type_Preparation::vector_of($contract->type, $context);
		}
		if ($node->default_value !== null) {
			$contract->default_value = Parameter_Defaults::prepare($node->default_value, $contract, $context);
		}
		$facts->contract = $contract;
		$node->set_preparation($facts);
	}

	/** Fields belong to their record, never to the surrounding local-variable scope. */
	public static function prepare_record(type_declaration_node $syntax, preparation_context $context): void
	{
		// The common AST does not confer support for new declaration/member semantics.
		$extended /** Storage<named_type_node> */ = $syntax->extended_interfaces;
		$implemented /** Storage<named_type_node> */ = $syntax->implemented_interfaces;
		$methods /** Storage<method_node> */ = $syntax->methods;
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		$uses /** Storage<trait_use_node> */ = $syntax->use_traits;
		if ((($syntax->declaration_kind !== type_declaration_kind::struct_type)
			&& ($syntax->declaration_kind !== type_declaration_kind::class_type))
			|| ($syntax->base_class !== null) || (q_count($syntax->modifiers) !== 0)
			|| !$extended->is_empty() || !$implemented->is_empty()
			|| !$methods->is_empty() || !$constants->is_empty() || !$uses->is_empty()) {
			throw new \RuntimeException('Type member semantics are not supported by this plain-record slice');
		}
		$facts = new prepared_record();
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;

		$copyable = true;
		$query = new Capability_Preparation($context);
		$nodes /** Storage<property_node> */ = $syntax->properties;
		$names /** hash<bool> */ = [];
		foreach ($nodes as $field) {
			if (isset($names[$field->name])) {
				throw new \RuntimeException('Duplicate property ' . $field->name);
			}
			$names[$field->name] = true;
			self::prepare_field($field, $context);
			$field_facts = $field->require_preparation();
			$fields->add($field->name, $field_facts);
			if (!$query->has_capability($field_facts->type, generic_contract::copyable_value)) {
				$copyable = false;
			}
		}
		$capabilities /** vector<generic_contract> */ = [generic_contract::value_storable];
		if ($copyable) {
			$capabilities[] = generic_contract::copyable_value;
		}
		$facts->capabilities = $capabilities;
		$syntax->set_preparation($facts);
	}

	/** Field eligibility uses the common declared storage capability path. */
	public static function prepare_field(property_node $field, preparation_context $context): void
	{
		if (($field->type_syntax === null) || ($field->initializer !== null)) {
			throw new \RuntimeException('Property inference and initializers are not supported by this plain-record slice');
		}
		foreach ($field->modifiers as $modifier) {
			if ($modifier !== declaration_modifier::public_access) {
				throw new \RuntimeException('Property modifier is not supported by this plain-record slice');
			}
		}
		$prepared = new prepared_field();
		$prepared->declaration = $field->occurrence();
		$prepared->type = Type_Preparation::type($field->require_type_syntax(), $context);
		if (Type_Queries::canonical($prepared->type)->definition() instanceof enum_type_definition) {
			throw new \RuntimeException('S2S enum-valued fields await explicit field initialization');
		}
		$query = new Capability_Preparation($context);
		if (!$query->has_capability($prepared->type,
			generic_contract::value_storable)) {
			throw new \RuntimeException('S2S struct field type does not declare value_storable');
		}
		if (!Type_Queries::is_class_handle($prepared->type)) {
			$context->worker->require_record($prepared->type, $context);
		}
		$field->set_preparation($prepared);
	}
}
