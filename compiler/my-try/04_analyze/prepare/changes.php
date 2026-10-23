<?php

/* Compare observable declaration facts and restore unchanged facts. */
namespace scpp\compiler;

final class Preparation_Changes
{
	/** Parameter identity matters because prepared signatures retain links to their declarations. */
	public static function same_signature(?prepared_source_callable $old, prepared_source_callable $current): bool
	{
		if ($old === null) {
			return false;
		}
		if ($old->visibility !== $current->visibility) { return false; }
		if ($old->static_member !== $current->static_member) { return false; }
		if (($old->abstract_method !== $current->abstract_method) || ($old->final_method !== $current->final_method)
			|| ($old->override_assertion !== $current->override_assertion)
			|| ($old->explicit_virtual !== $current->explicit_virtual)) { return false; }
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
			if ($before->initialized_property !== $parameter->initialized_property) { return false; }
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
		if ($old->data_initializable !== $current->data_initializable) { return false; }
		if (($old->base_type === null) !== ($current->base_type === null)) { return false; }
		if ($old->base_type !== null) {
			if (!$old->base_type->matches($current->base_type)) { return false; }
		}
		$old_methods /** Key_Storage_List<collected_method> */ = $old->methods;
		$new_methods /** Key_Storage_List<collected_method> */ = $current->methods;
		$previous_methods /** vector<collected_method> */ = $old_methods->items();
		$current_methods /** vector<collected_method> */ = $new_methods->items();
		if (q_count($previous_methods) !== q_count($current_methods)) { return false; }
		foreach ($current_methods as $index => $method) {
			if ($previous_methods[$index] !== $method) { return false; }
		}
		if (($old->initialization === null) !== ($current->initialization === null)) {
			return false;
		}
		if ($old->initialization !== null) {
			if ($old->initialization->base_contract !== $current->initialization->base_contract) { return false; }
		}
		if (weakref_get($old->constructor) !== weakref_get($current->constructor)) { return false; }
		foreach ($old->capabilities as $capability) {
			if (!$current->has_capability($capability)) {
				return false;
			}
		}
		if (q_count($old->capabilities) !== q_count($current->capabilities)) {
			return false;
		}
		return self::same_fields($old->fields, $current->fields) && self::same_fields($old->static_fields, $current->static_fields);
	}

	private static function same_fields(Key_Storage_List $old_fields /** Key_Storage_List<prepared_field> */,
		Key_Storage_List $new_fields /** Key_Storage_List<prepared_field> */): bool
	{
		$previous /** vector<prepared_field> */ = $old_fields->items();
		$fields /** vector<prepared_field> */ = $new_fields->items();
		if (q_count($previous) !== q_count($fields)) {
			return false;
		}
		foreach ($fields as $index => $field) {
			$before = $previous[$index];
			if ($before->visibility !== $field->visibility) { return false; }
			if ($before->static_member !== $field->static_member) { return false; }
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
	public static function restore_parameters(callable_syntax_owner_i $syntax, prepared_source_callable $facts): void
	{
		$nodes /** Storage<parameter_node> */ = $syntax->signature_parts()->parameters;
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		foreach ($nodes as $index => $node) {
			$node->set_preparation($parameters[$index]);
		}
	}

	/** Preserve field fact identities when record preparation finds no effective change. */
	public static function restore_fields(type_declaration_node $syntax, prepared_record $facts): void
	{
		$nodes /** Storage<property_node> */ = Member_Inventory::properties(object_cast($syntax->occurrence(), collected_member_owner::class));
		$store /** Key_Storage_List<prepared_field> */ = $facts->fields;
		$static_store /** Key_Storage_List<prepared_field> */ = $facts->static_fields;
		foreach ($nodes as $node) {
			$fields /** vector<prepared_field> */ = Member_Preparation::is_static($node->modifiers)
				? $static_store->named($node->name) : $store->named($node->name);
			$node->set_preparation($fields[0]);
		}
	}
}
