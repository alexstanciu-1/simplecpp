<?php

/* Compare observable declaration facts and restore unchanged facts. */
namespace scpp\compiler;

final class Preparation_Changes
{
	/** Parameter identity matters because prepared signatures retain links to their declarations. */
	public static function same_signature(?prepared_function $old, prepared_function $current): bool
	{
		if ($old === null) {
			return false;
		}
		if (!self::same_reference_return($old->signature->reference_return, $current->signature->reference_return)) {
			return false;
		}
		$previous /** Storage<prepared_parameter> */ = $old->parameters;
		$parameters /** Storage<prepared_parameter> */ = $current->parameters;
		if ((!$old->signature->return_type->matches($current->signature->return_type)) || (q_count($previous) !== q_count($parameters))) {
			return false;
		}
		foreach ($parameters as $index => $parameter) {
			$before = $previous[$index];
			$before_contract = $before->contract;
			$contract = $parameter->contract;
			if ($before_contract->access !== $contract->access) {
				return false;
			}
			if (($before_contract->element_type === null) !== ($contract->element_type === null)) {
				return false;
			}
			if (!Parameter_Defaults::same($before_contract->default_value, $contract->default_value)) {
				return false;
			}
			if ((!$before_contract->type->matches($contract->type)) || ($before_contract->mode !== $contract->mode) || (weakref_get($before->declaration) !== weakref_get($parameter->declaration))) {
				return false;
			}
		}
		return true;
	}

	/** Result mode and symbolic origin are observable parts of a callable contract. */
	private static function same_reference_return(?reference_return_contract $before,
		?reference_return_contract $after): bool
	{
		if (($before === null) || ($after === null)) {
			return $before === $after;
		}
		return $before->parameter_index() === $after->parameter_index();
	}

	/** Field order, identity and type define the current compact record facts. */
	public static function same_record(?prepared_record $old, prepared_record $current): bool
	{
		if ($old === null) {
			return false;
		}
		if (($old->initialization === null) !== ($current->initialization === null)) {
			return false;
		}
		foreach ($old->capabilities as $capability) {
			if (!$current->has_capability($capability)) {
				return false;
			}
		}
		if (q_count($old->capabilities) !== q_count($current->capabilities)) {
			return false;
		}
		$old_fields /** Key_Storage_List<prepared_field> */ = $old->fields;
		$new_fields /** Key_Storage_List<prepared_field> */ = $current->fields;
		$previous /** vector<prepared_field> */ = $old_fields->items();
		$fields /** vector<prepared_field> */ = $new_fields->items();
		if (q_count($previous) !== q_count($fields)) {
			return false;
		}
		foreach ($fields as $index => $field) {
			$before = $previous[$index];
			if (!Property_Defaults::same($before->default_value, $field->default_value)) {
				return false;
			}
			if ((!$before->type->matches($field->type)) || (weakref_get($before->declaration) !== weakref_get($field->declaration))) {
				return false;
			}
		}
		return true;
	}

	/** Unchanged signatures keep the exact fact objects already referenced by their consumers. */
	public static function restore_parameters(function_node $syntax, prepared_function $facts): void
	{
		$nodes /** Storage<parameter_node> */ = $syntax->parameters;
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		foreach ($nodes as $index => $node) {
			$node->set_preparation($parameters[$index]);
		}
	}

	/** Preserve field fact identities when record preparation finds no effective change. */
	public static function restore_fields(type_declaration_node $syntax, prepared_record $facts): void
	{
		$nodes /** Storage<property_node> */ = $syntax->properties;
		$store /** Key_Storage_List<prepared_field> */ = $facts->fields;
		$fields /** vector<prepared_field> */ = $store->items();
		foreach ($nodes as $index => $node) {
			$node->set_preparation($fields[$index]);
		}
	}
}
