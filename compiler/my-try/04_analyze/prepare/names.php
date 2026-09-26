<?php

/*
 * Role: resolve occurrence indexes to exact declarations.
 * Call map: LLVM_Preparation::prepare_program -> Name_Preparation::prepare.
 */
namespace scpp\compiler;

final class Name_Preparation
{
	/** Prepare only unambiguous explicit variables for the initial LLVM experiment. */
	public function prepare(collected_file $file): prepared_names
	{
		$result = new prepared_names();
		$entries /** Storage<collected_name> */ = $file->entries;
		foreach ($file->defined_elements as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			if (($entry->kind === collected_name_kind::variable_declaration) && $entry_scope->has_template($entry->name)) {
				throw new \RuntimeException('Variable conflicts with template parameter: ' . $entry->name);
			}
			$result->declarations[$entry->token_index] = $entry;
		}
		$references /** vector<int> */ = $file->variable_references;
		foreach ($file->pending_bindings as $index) {
			$references[] = $index;
		}
		foreach ($references as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			$candidates /** vector<collected_name> */ = [];
			$variable_scope = Scope_Lookup::visible($entry_scope);
			$candidates = Scope_Lookup::live($variable_scope->variables_named($entry->name));
			if (q_count($candidates) !== 1) {
				throw new \RuntimeException(("LLVM experiment needs one same-file declaration for " . $entry->name . " at token " . $entry->token_index));
			}
			if ($candidates[0]->collection !== $file) {
				throw new \RuntimeException('LLVM experiment needs one same-file declaration for ' . $entry->name . ' at token ' . $entry->token_index);
			}
			if (($entry->kind === collected_name_kind::binding) && ($candidates[0]->token_index >= $entry->token_index)) {
				throw new \RuntimeException('Assignment requires an existing declaration');
			}
			$result->references[$entry->token_index] = $candidates[0];
		}
		foreach ($file->function_references as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			$current_scope = $entry_scope;
			$candidates /** vector<collected_name> */ = [];
			while (true)
			{
				$current_scope = Scope_Lookup::visible($current_scope);
				$candidates = [];
				$candidates = Scope_Lookup::live($current_scope->functions_named($entry->name));
				if (q_count($candidates) !== 0) {
					break;
				}
				$parent = $current_scope->parent_scope();
				if ($parent === null) {
					break;
				}
				$current_scope = object_cast($parent, scope::class);
			}
			if (q_count($candidates) !== 1) {
				throw new \RuntimeException(("Expected one function target for " . $entry->name . " at " . $file->source_file()->path . ": token " . $entry->token_index));
			}
			$result->function_references[$entry->token_index] = $candidates[0];
		}
		foreach ($file->type_references as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			$current_scope = $entry_scope;
			while (true)
			{
				$current_scope = Scope_Lookup::visible($current_scope);
				if ($current_scope->has_template($entry->name)) {
					$result->template_slots[$entry->token_index] = $current_scope->template_slot($entry->name);
					break;
				}
				$type_candidates /** vector<collected_name> */ = [];
				$type_candidates = Scope_Lookup::live($current_scope->source_types_named($entry->name));
				if (q_count($type_candidates) !== 0) {
					if (q_count($type_candidates) !== 1) {
						throw new \RuntimeException('Ambiguous struct type: ' . $entry->name);
					}
					$result->types[$entry->token_index] = $type_candidates[0];
					break;
				}
				$parent = $current_scope->parent_scope();
				if ($parent === null) {
					break;
				}
				$current_scope = object_cast($parent, scope::class);
			}
		}
		return $result;
	}
}
