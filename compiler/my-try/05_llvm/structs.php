<?php

/*
 * Role: prepare struct layouts and field bindings.
 * Call map: LLVM_Preparation -> LLVM_Struct_Preparation::prepare / fields.
 */
namespace scpp\compiler;

final class LLVM_Struct_Preparation
{
	/** Prepare all definitions before file-local storage, including forward/cross-file types.
	 */
	public function prepare(Storage $files /** Storage<collected_file> */, llvm_policy $policy): \SplObjectStorage
	{
		$result = new \SplObjectStorage();
		foreach ($files as $file)
		{
			foreach ($file->defined_elements as $index)
			{
				$entry = $file->entries[$index];
				if ($entry->kind !== collected_name_kind::struct_declaration) {
					continue;
				}
				if (isset($policy->types[$entry->name]) || (count($entry->scope->types[$entry->name]) !== 1)) {
					throw new \RuntimeException('Conflicting struct type: ' . $entry->name);
				}
				$type = new llvm_struct_type();
				$type->declaration = $entry;
				$type->name = '%' . LLVM_Names::encode($entry->name);
				foreach ($entry->node->specialization->fields as $field_node)
				{
					$syntax = $field_node->specialization;
					$name = substr($file->source->tokens[$syntax->name_token_index]->text, 1);
					$source_type = $file->source->tokens[$syntax->type_syntax->token_index]->text;
					$field_type = $policy->types[$source_type] ?? null;
					if ($field_type !== $policy->integer_type) {
						throw new \RuntimeException('Struct proof supports only int fields');
					}
					if (isset($type->fields[$name])) {
						throw new \RuntimeException('Duplicate struct field: ' . $name);
					}
					$field = new llvm_field();
					$field->index = count($type->fields);
					$field->type = $field_type;
					$type->fields->add($name, $field);
				}
				if ($type->fields->is_empty()) {
					throw new \RuntimeException('Empty structs are not supported in this proof');
				}
				$result[$entry] = $type;
			}
		}
		return $result;
	}

	/** Resolve member occurrences once; emission consumes field identities and ordinals. */
	public function fields(llvm_prepared_function $function): void
	{
		$file = $function->file;
		foreach ($file->source->field_references as $index)
		{
			$entry = $file->source->entries[$index];
			if ($entry->scope !== $function->body->specialization->scope) {
				continue;
			}
			$base = $entry->node->specialization->base;
			if ($base->kind !== node_kind::variable_reference) {
				throw new \RuntimeException('Nested aggregate field access is not supported yet');
			}
			$declaration = $file->names->references[$base->token_index];
			$type = $function->locals[$declaration->local_index]->struct_type;
			if ($type === null) {
				throw new \RuntimeException('Field access requires a struct');
			}
			$function->fields[$entry->token_index] = $type->fields[$entry->name]
				?? throw new \RuntimeException('Unknown struct field: ' . $entry->name);
		}
	}
}
