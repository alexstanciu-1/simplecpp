<?php

/* Prepare function signatures and record fields before their executable consumers. */
namespace scpp\compiler;

final class Declaration_Preparation
{
	/** Publish a complete signature before any body so calls do not depend on source order. */
	public static function prepare_callable(callable_declaration_node $syntax, preparation_context $context): void
	{
		if (($syntax instanceof method_node) && ($syntax->name === '__construct')) {
			if (object_cast($syntax->occurrence(), collected_method::class)->declaring_type() instanceof collected_interface) {
				throw new \RuntimeException('Interface constructor contracts are deferred');
			}
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

		if (($syntax instanceof method_node) && ($syntax->name === '__destruct')) {
			$facts->signature->return_type = Language_Types::no_value(Model::$language_scope);
		}
		else { $facts->signature->return_type = Type_Preparation::type($syntax->require_return_type(), $context); }
		if (!$syntax->signature_syntax->returns_reference) {
			Capability_Preparation::require_concrete_value($facts->signature->return_type, $context);
		}

		self::prepare_parameters($syntax->signature_syntax, $facts, $context);
		Reference_Preparation::prepare_signature($syntax->signature_syntax, $facts->signature);
		Callable_Contracts::validate($facts->signature);
		$syntax->set_preparation($facts);
	}

	/** Shared authored parameter admission, independent of named lookup. */
	public static function prepare_parameters(callable_signature_syntax $syntax, prepared_source_callable $facts, preparation_context $context,
		?callable_signature $expected = null): void
	{
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		$contracts /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$nodes /** Storage<parameter_node> */ = $syntax->parameters;
		$names /** hash<bool> */ = [];
		if ($expected !== null) {
			if (q_count($nodes) !== q_count($expected->parameters)) { throw new \RuntimeException('Contextual callable parameter count mismatch'); }
		}
		foreach ($nodes as $index => $parameter) {
			if (isset($names[$parameter->name])) {
				throw new \RuntimeException('S2S duplicate parameter name: ' . $parameter->name);
			}
			$names[$parameter->name] = true;
			$expected_parameter /** nullable<canonical_type_use> */ = null;
			if ($expected !== null) {
				$expected_parameters /** Storage<callable_parameter> */ = $expected->parameters;
				$expected_parameter = $expected_parameters[$index]->type;
			}
			self::prepare_parameter($parameter, $context, $expected_parameter);
			$prepared = $parameter->require_preparation();
			$parameters->append($prepared);
			$contracts->append($prepared->contract);
		}

	}

	/** Anonymous admission reuses named-callable parameter and signature contracts. */
	public static function prepare_anonymous(closure_node $syntax, preparation_context $context, ?canonical_type_use $expected_type = null): prepared_source_callable
	{
		$facts = new prepared_source_callable();
		$expected /** nullable<callable_signature> */ = null;
		if ($expected_type !== null) {
			$expected = object_cast(Type_Queries::canonical($expected_type)->definition(), callable_type_definition::class)->signature();
		}
		$return_type = $syntax->signature_syntax->return_type;
		if ($return_type !== null) { $facts->signature->return_type = Type_Preparation::type($return_type, $context); }
		elseif ($expected !== null) { $facts->signature->return_type = $expected->return_type; }
		else { throw new \RuntimeException('Closure requires an explicit return type or expected callable contract'); }
		if (!$syntax->signature_syntax->returns_reference) {
			Capability_Preparation::require_concrete_value($facts->signature->return_type, $context);
		}
		self::prepare_parameters($syntax->signature_syntax, $facts, $context, $expected);
		Reference_Preparation::prepare_signature($syntax->signature_syntax, $facts->signature);
		Callable_Contracts::validate($facts->signature);
		return $facts;
	}

	/** Prepare one parameter with the same signature context used by its enclosing function. */
	public static function prepare_parameter(parameter_node $node, preparation_context $context, ?canonical_type_use $expected_type = null): void
	{
		$facts = new prepared_parameter();
		$facts->declaration = $node->occurrence();
		$contract = new callable_parameter();
		if ($node->type_syntax !== null) { $contract->type = Type_Preparation::type($node->type_syntax, $context); }
		elseif ($expected_type !== null) { $contract->type = $expected_type; }
		else { throw new \RuntimeException('Parameter requires an explicit type or expected callable contract'); }
		if (Type_Queries::canonical($contract->type)->definition() instanceof callable_type_definition) {
			if (($node->mode !== passing_mode::value) || $node->variadic || ($node->default_value !== null)) {
				throw new \RuntimeException('Callable parameters currently require ordinary by-value inputs');
			}
		}
		Type_Preparation::require_value_type($contract->type);
		$contract->mode = $node->mode;
		$contract->access = $node->access;
		if ($node->mode === passing_mode::value) {
			Capability_Preparation::require_concrete_value($contract->type, $context);
		}
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
		$methods /** Storage<method_node> */ = Member_Inventory::methods(object_cast($syntax->occurrence(), collected_member_owner::class));
		$uses /** Storage<trait_use_node> */ = $syntax->use_traits;
		if ((($syntax->declaration_kind !== type_declaration_kind::struct_type)
			&& ($syntax->declaration_kind !== type_declaration_kind::class_type))
			|| !$extended->is_empty()) {
			throw new \RuntimeException('Type member semantics are not supported by this plain-record slice');
		}
		if (!$uses->is_empty()) {
			$instances /** Storage<trait_instance> */ = object_cast($syntax->occurrence(), collected_record::class)->trait_instances;
			$targets = Trait_Preparation::targets(object_cast($syntax->occurrence(), collected_record::class));
			if (q_count($targets) !== q_count($instances)) { throw new \RuntimeException('Trait use requires coordinator composition'); }
			foreach ($targets as $index => $target) {
				if ($instances[$index]->origin !== $target) { throw new \RuntimeException('Trait use requires coordinator composition'); }
			}
		}
		$facts = new prepared_record();
		$context->member_owner = object_cast($syntax->occurrence(), collected_record::class);
		Trait_Preparation::validate(object_cast($syntax->occurrence(), collected_record::class));
		Inheritance_Preparation::base(object_cast($syntax->occurrence(), collected_record::class), $facts, $context);
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;
		foreach ($syntax->modifiers as $modifier) {
			if (($modifier === declaration_modifier::final_declaration) || ($modifier === declaration_modifier::abstract_declaration)) { continue; }
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
			if (weakref_get($facts->constructor) !== null) { throw new \RuntimeException('Duplicate constructor'); }
			$facts->constructor = object_cast($method->occurrence(), collected_method::class);
		}

		$copyable = true;
		$query = new Capability_Preparation($context);
		if ($facts->base_type !== null) {
			$copyable = $query->has_capability($facts->base_type, generic_contract::copyable_value);
		}
		$nodes /** Storage<property_node> */ = Member_Inventory::properties(object_cast($syntax->occurrence(), collected_member_owner::class));
		$names /** hash<bool> */ = [];
		foreach ($nodes as $field) {
			if (isset($names[$field->name])) {
				throw new \RuntimeException('Duplicate property ' . $field->name);
			}
			$names[$field->name] = true;
			self::prepare_field($field, $context, $facts->data_initializable || (weakref_get($facts->constructor) !== null));
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
		$capabilities /** vector<generic_contract> */ = [];
		if (!$context->worker->require_relationships($context->member_owner, $context)->abstract_type) {
			$capabilities[] = generic_contract::value_storable;
		}
		if ($copyable) {
			$capabilities[] = generic_contract::copyable_value;
		}
		$facts->capabilities = $capabilities;
		Inheritance_Preparation::inherit_constructor($facts, $context);
		if ($facts->data_initializable && (weakref_get($facts->constructor) === null)) {
			$facts->initialization = new record_initialization_contract();
			Constructor_Preparation::synthesized_inputs($facts, $context);
		}
		Inheritance_Preparation::require_default_base($facts, $context);
		$syntax->set_preparation($facts);
	}

	/** Field eligibility uses the common declared storage capability path. */
	public static function prepare_field(property_node $field, preparation_context $context, bool $allows_required = false): void
	{
		$previous_source = $context->collection;
		$context->collection = $field->occurrence()->collection;
		try { self::prepare_field_value($field, $context, $allows_required); }
		finally { $context->collection = $previous_source; }
	}

	/** Source spelling follows the field; dependencies and access remain owned by the record. */
	private static function prepare_field_value(property_node $field, preparation_context $context, bool $allows_required): void
	{
		Member_Preparation::validate_modifiers($field->modifiers, true);
		$prepared = new prepared_field();
		$prepared->visibility = Member_Preparation::visibility($field->modifiers);
		$prepared->static_member = Member_Preparation::is_static($field->modifiers);
		if ($prepared->static_member) { $allows_required = false; }
		$prepared->declaration = $field->occurrence();
		$type /** nullable<canonical_type_use> */ = null;
		if ($field->type_syntax !== null) {
			$type = Type_Preparation::type($field->require_type_syntax(), $context);
		}
		if ($field->initializer !== null) {
			if ($prepared->static_member && ($field->initializer instanceof data_construction_node)) {
				throw new \RuntimeException('Static properties require type defaults or constant expressions');
			}
			$prepared->default_value = Property_Defaults::prepare($field->initializer, $type, $context);
			$type = $prepared->default_value->conversion->target_type;
		}
		if ($type === null) { throw new \RuntimeException('Property without a default requires an explicit type'); }
		$prepared->type = $type;
		Type_Lifecycle_Preparation::require_storage($type, $context);
		if (($field->initializer === null) && (Type_Queries::canonical($type)->definition() instanceof enum_type_definition)) {
			if (!$allows_required) {
				throw new \RuntimeException('S2S enum-valued fields await explicit field initialization');
			}
		}
		$query = new Capability_Preparation($context);
		if (!$query->has_capability($prepared->type,
			generic_contract::value_storable)) {
			throw new \RuntimeException('S2S struct field type does not declare value_storable');
		}
		if (!Type_Queries::is_nominal_handle($prepared->type)) {
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
