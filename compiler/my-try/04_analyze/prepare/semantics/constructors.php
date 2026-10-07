<?php

/* Constructor contracts share callable work and ordinary argument/default boundaries. */
namespace scpp\compiler;

final class Constructor_Preparation
{
	/** Resolve public by-value constructor inputs after the declaring layout has settled. */
	public static function signature(method_node $syntax, preparation_context $context): void
	{
		if (($syntax->name !== '__construct') || ($syntax->return_type !== null) || $syntax->returns_reference) {
			throw new \RuntimeException('Only __construct without a return type or reference return is supported');
		}
		foreach ($syntax->modifiers as $modifier) {
			if ($modifier !== declaration_modifier::public_access) {
				throw new \RuntimeException('The first constructor slice requires public constructors');
			}
		}
		$entry = object_cast($syntax->occurrence(), collected_method::class);
		$record = $entry->declaring_record();
		$context->worker->require_declaration($context->owner, $record);
		$layout = $record->syntax()->require_preparation();
		$facts = new prepared_source_callable();
		$facts->signature->return_type = Language_Types::no_value(Model::$language_scope);
		$facts->receiver = Method_Preparation::receiver($entry);
		$contract = new record_initialization_contract();
		$contract->constructor = $entry;
		$facts->initialization = $contract;
		$inputs /** Key_Storage_List<record_initialization_input> */ = $contract->inputs;
		$fields /** Key_Storage_List<prepared_field> */ = $layout->fields;
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		$contracts /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$nodes /** Storage<parameter_node> */ = $syntax->parameters;
		$initialized /** hash<bool> */ = [];
		foreach ($nodes as $node) {
			if (($node->name === 'this') || (q_count($inputs->named($node->name)) !== 0)) {
				throw new \RuntimeException('Duplicate or reserved constructor parameter: ' . $node->name);
			}
			if (($node->mode !== passing_mode::value) || $node->variadic || ($node->access !== storage_access::writable)) {
				throw new \RuntimeException('Constructor parameters require fixed by-value inputs');
			}
			$parameter = new prepared_parameter();
			$parameter->declaration = $node->occurrence();
			$parameter->contract = new callable_parameter();
			$parameter->contract->mode = passing_mode::value;
			if ($node->property_backed) {
				$matches /** vector<prepared_field> */ = $fields->named($node->name);
				if (q_count($matches) !== 1) { throw new \RuntimeException('set: requires an existing instance property: ' . $node->name); }
				$field = $matches[0];
				$parameter->initialized_property = $field;
				$parameter->contract->type = $field->type;
				$initialized[$node->name] = true;
				if ($field->default_value !== null) {
					$parameter->contract->default_value = new prepared_property_parameter_default($field->default_value);
				}
				// The parameter remains independently usable in the body; do not silently move it.
				Capability_Preparation::require_copy($field->type, $context);
			}
			else {
				$parameter->contract->type = Type_Preparation::type($node->require_type_syntax(), $context);
				Type_Preparation::require_value_type($parameter->contract->type);
			}
			if ($node->default_value !== null) {
				$parameter->contract->default_value = new prepared_property_parameter_default(
					Property_Defaults::prepare($node->default_value, $parameter->contract->type, $context));
			}
			$node->set_preparation($parameter);
			$parameters->append($parameter);
			$contracts->append($parameter->contract);
			$inputs->add($node->name, new record_initialization_input($node->name, $parameter->contract));
		}
		foreach ($fields->items() as $field) {
			$name = object_cast(weakref_get($field->declaration), collected_name::class)->name;
			if (!isset($initialized[$name]) && ($field->default_value === null)) {
				if (!Object_Construction_Preparation::supports_default($field->type, $context)) {
					throw new \RuntimeException('Property requires set: initialization before the constructor body: ' . $name);
				}
			}
		}
		Callable_Contracts::validate($facts->signature);
		$syntax->set_preparation($facts);
	}

	/** Synthesis exposes only public properties; excluded fields still require initialization. */
	public static function synthesized_inputs(prepared_record $record, preparation_context $context): void
	{
		$contract /** record_initialization_contract */ = $record->initialization;
		$inputs /** Key_Storage_List<record_initialization_input> */ = $contract->inputs;
		$fields /** Key_Storage_List<prepared_field> */ = $record->fields;
		foreach ($fields->items() as $field) {
			$name = object_cast(weakref_get($field->declaration), collected_name::class)->name;
			$parameter = new callable_parameter();
			$parameter->type = $field->type;
			$parameter->mode = passing_mode::value;
			if ($field->default_value !== null) {
				$parameter->default_value = new prepared_property_parameter_default($field->default_value);
			}
			elseif (Object_Construction_Preparation::supports_default($field->type, $context)) {
				$parameter->default_value = new prepared_type_parameter_default($field->type);
			}
			if ($field->public_access) {
				$inputs->add($name, new record_initialization_input($name, $parameter, $field));
			}
			elseif ($parameter->default_value === null) {
				throw new \RuntimeException('Non-public property requires a default or an authored constructor: ' . $name);
			}
		}
	}

	/** Caller work depends on the constructor signature, never its implementation body. */
	public static function inputs(collected_record $record, preparation_context $context): record_initialization_contract
	{
		$layout = $record->syntax()->require_preparation();
		$method = weakref_get($layout->constructor);
		if ($method !== null) {
			Call_Preparation::require_signature($method, $context);
			return $method->callable_syntax()->require_preparation()->initialization;
		}
		return $layout->initialization;
	}
}
