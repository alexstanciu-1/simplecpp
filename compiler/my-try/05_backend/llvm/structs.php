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
	public function prepare(Storage $files /** Storage<collected_file> */, llvm_policy $policy): \SplObjectStorage /** hash<llvm_struct_type, shared<collected_name>> */
	{
		$result /** hash<llvm_struct_type, shared<collected_name>> */ = new \SplObjectStorage /** hash<llvm_struct_type, shared<collected_name>> */();
		foreach ($files as $file)
		{
			$entries /** Storage<collected_name> */ = $file->entries;
			$tokens /** Storage<token> */ = $file->token_snapshot()->tokens;
			foreach ($file->defined_elements as $index)
			{
				$entry = $entries[$index];
				if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
					continue;
				}
				if ($entry->kind !== collected_name_kind::struct_declaration) {
					continue;
				}
				if (isset($policy->types[$entry->name]) || (q_count(Scope_Lookup::live(Scope_Lookup::visible(object_cast(weakref_get($entry->scope), scope::class))->source_types_named($entry->name))) !== 1)) {
					throw new \RuntimeException('Conflicting struct type: ' . $entry->name);
				}
				$type = new llvm_struct_type();
				$type->declaration = $entry;
				$type->name = '%' . LLVM_Names::encode($entry->name);
				$fields /** Keyed_Storage<llvm_field> */ = $type->fields;
				foreach (Syntax_Nodes::struct_data($entry->node)->fields as $field_node)
				{
					$syntax = Syntax_Nodes::field_data($field_node);
					$spelling = $tokens[$syntax->name_token_index]->text();
					$name = string_byte_slice($spelling, 1, string_byte_len($spelling) - 1);
					$source_type = $tokens[(int) $syntax->type_syntax->token_index]->text();
					if (!isset($policy->types[$source_type])) {
						throw new \RuntimeException('Struct proof supports only int fields');
					}
					$field_type = $policy->types[$source_type];
					if ($field_type !== $policy->integer_type) {
						throw new \RuntimeException('Struct proof supports only int fields');
					}
					if (isset($fields[$name])) {
						throw new \RuntimeException('Duplicate struct field: ' . $name);
					}
					$field = new llvm_field();
					$field->index = q_count($fields);
					$field->type = $field_type;
					$fields->add($name, $field);
				}
				if ($fields->is_empty()) {
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
		$entries /** Storage<collected_name> */ = $file->source->entries;
		foreach ($file->source->field_references as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			if (object_cast(weakref_get($entry->scope), scope::class) !== Syntax_Nodes::block_data($function->body)->lexical_scope()) {
				continue;
			}
			$base = Syntax_Nodes::field_access_data($entry->node)->base;
			if ($base->kind() !== node_kind::variable_reference) {
				throw new \RuntimeException('Nested aggregate field access is not supported yet');
			}
			$declaration = $file->names->references[(int) $base->token_index];
			$type = $function->locals[$declaration->local_index]->struct_type;
			if ($type === null) {
				throw new \RuntimeException('Field access requires a struct');
			}
			$fields /** Keyed_Storage<llvm_field> */ = $type->fields;
			if (!isset($fields[$entry->name])) {
				throw new \RuntimeException('Unknown struct field: ' . $entry->name);
			}
			$function->fields[$entry->token_index] = $fields[$entry->name];
		}
	}
}
