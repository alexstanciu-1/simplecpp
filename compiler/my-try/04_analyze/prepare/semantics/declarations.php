<?php

/* Prepare function signatures and record fields before their executable consumers. */
namespace scpp\compiler;

final class Declaration_Preparation
{
	/** Publish a complete signature before any body so calls do not depend on source order. */
	public static function prepare_callable(callable_declaration_node $syntax, preparation_context $context): void
	{
		if (($syntax instanceof method_node) && ($syntax->name === '__construct')) {
			Constructor_Preparation::signature(object_cast($syntax, method_node::class), $context);
			return;
		}
		$facts = new prepared_source_callable();
		if ($syntax instanceof method_node) {
			Method_Preparation::signature(object_cast($syntax, method_node::class), $facts, $context);
		}
		else {
			$declaration = $syntax->occurrence();
			$declaring_scope = object_cast(weakref_get($declaration->scope), scope::class);
			// Observe the name pool so adding/removing an unused duplicate invalidates this signature.
			$candidates = Observed_Lookup::functions($declaring_scope, $declaration->name, $context);
			if (q_count($candidates) !== 1) {
				throw new \RuntimeException('S2S duplicate function name: ' . $declaration->name);
			}
		}
		if (q_count($syntax->template_parameters) !== 0) {
			throw new \RuntimeException('S2S function templates are deferred');
		}

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
		$contract->type = Type_Preparation::type($node->require_type_syntax(), $context);
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
		$uses /** Storage<trait_use_node> */ = $syntax->use_traits;
		if ((($syntax->declaration_kind !== type_declaration_kind::struct_type)
			&& ($syntax->declaration_kind !== type_declaration_kind::class_type))
			|| ($syntax->base_class !== null)
			|| !$extended->is_empty() || !$implemented->is_empty()
			|| !$uses->is_empty()) {
			throw new \RuntimeException('Type member semantics are not supported by this plain-record slice');
		}
		$facts = new prepared_record();
		$context->receiver_record = object_cast($syntax->occurrence(), collected_record::class);
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;
		foreach ($syntax->modifiers as $modifier) {
			if ($modifier !== declaration_modifier::data_initializable) {
				throw new \RuntimeException('Type modifier is not supported by this record slice');
			}
			$facts->data_initializable = true;
		}
		foreach ($methods as $method) {
			$members /** Key_Storage_List<collected_method> */ = $facts->methods;
			if (q_count($members->named($method->name)) !== 0) {
				throw new \RuntimeException($method->name === '__construct' ? 'Duplicate constructor' : 'Duplicate method: ' . $method->name);
			}
			$members->add($method->name, object_cast($method->occurrence(), collected_method::class));
			if ($method->name !== '__construct') { continue; }
			if ($facts->constructor !== null) { throw new \RuntimeException('Duplicate constructor'); }
			$facts->constructor = object_cast($method->occurrence(), collected_method::class);
		}

		$copyable = true;
		$query = new Capability_Preparation($context);
		$nodes /** Storage<property_node> */ = $syntax->properties;
		$names /** hash<bool> */ = [];
		foreach ($nodes as $field) {
			if (isset($names[$field->name])) {
				throw new \RuntimeException('Duplicate property ' . $field->name);
			}
			$names[$field->name] = true;
			self::prepare_field($field, $context, $facts->data_initializable || ($facts->constructor !== null));
			$field_facts = $field->require_preparation();
			if ($field_facts->static_member) {
				$static_fields /** Key_Storage_List<prepared_field> */ = $facts->static_fields;
				$static_fields->add($field->name, $field_facts);
				continue;
			}
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
		if ($facts->data_initializable && ($facts->constructor === null)) {
			$facts->initialization = new record_initialization_contract();
			Constructor_Preparation::synthesized_inputs($facts, $context);
		}
		$syntax->set_preparation($facts);
	}

	/** Field eligibility uses the common declared storage capability path. */
	public static function prepare_field(property_node $field, preparation_context $context, bool $allows_required = false): void
	{
		if ($field->type_syntax === null) {
			throw new \RuntimeException('Property type inference is not supported by this plain-record slice');
		}
		Member_Preparation::validate_modifiers($field->modifiers, true);
		$prepared = new prepared_field();
		$prepared->public_access = Member_Preparation::is_public($field->modifiers);
		$prepared->static_member = Member_Preparation::is_static($field->modifiers);
		if ($prepared->static_member) { $allows_required = false; }
		$prepared->declaration = $field->occurrence();
		$prepared->type = Type_Preparation::type($field->require_type_syntax(), $context);
		if ($field->initializer !== null) {
			if ($prepared->static_member && ($field->initializer instanceof data_construction_node)) {
				throw new \RuntimeException('Static properties require type defaults or constant expressions');
			}
			$prepared->default_value = Property_Defaults::prepare($field->initializer, $prepared->type, $context);
		}
		elseif (Type_Queries::canonical($prepared->type)->definition() instanceof enum_type_definition) {
			if (!$allows_required) {
				throw new \RuntimeException('S2S enum-valued fields await explicit field initialization');
			}
		}
		$query = new Capability_Preparation($context);
		if (!$query->has_capability($prepared->type,
			generic_contract::value_storable)) {
			throw new \RuntimeException('S2S struct field type does not declare value_storable');
		}
		if (!Type_Queries::is_class_handle($prepared->type)) {
			$context->worker->require_record($prepared->type, $context);
			if (($prepared->default_value === null) && !$allows_required) {
				$nested = Type_Queries::source_record(Type_Queries::record_payload($prepared->type));
				if ($nested !== null) {
					if (!Object_Construction_Preparation::supports_default($prepared->type, $context)) {
						throw new \RuntimeException('Inline record property requires an explicit initializer');
					}
				}
			}
		}
		$field->set_preparation($prepared);
	}
}
