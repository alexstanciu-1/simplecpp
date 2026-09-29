<?php

/* Compare observable declaration facts, not worker state or transient allocations. */
namespace scpp\compiler;

final class Preparation_Changes
{
	/** Parameter identity matters because prepared signatures retain links to their declarations. */
	public static function function_signature(?prepared_function $old, prepared_function $current): bool
	{
		if ($old === null) {
			return false;
		}
		$previous /** Storage<prepared_parameter> */ = $old->parameters;
		$parameters /** Storage<prepared_parameter> */ = $current->parameters;
		if (($old->return_type !== $current->return_type) || (q_count($previous) !== q_count($parameters))) {
			return false;
		}
		foreach ($parameters as $index => $parameter) {
			$before = $previous[$index];
			if (($before->type !== $parameter->type) || ($before->mode !== $parameter->mode) || (weakref_get($before->declaration) !== weakref_get($parameter->declaration))) {
				return false;
			}
		}
		return true;
	}

	/** Field order, identity and type define the current compact record facts. */
	public static function record(?prepared_record $old, prepared_record $current): bool
	{
		if ($old === null) {
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
			if (($before->type !== $field->type) || (weakref_get($before->declaration) !== weakref_get($field->declaration))) {
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
	public static function restore_fields(struct_node $syntax, prepared_record $facts): void
	{
		$nodes /** Storage<field_node> */ = $syntax->fields;
		$store /** Key_Storage_List<prepared_field> */ = $facts->fields;
		$fields /** vector<prepared_field> */ = $store->items();
		foreach ($nodes as $index => $node) {
			$node->set_preparation($fields[$index]);
		}
	}
}
