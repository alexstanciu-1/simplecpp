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
		foreach ($file->defined_elements as $index) {
			$entry = $entries[$index];
			if (($entry->kind === collected_name_kind::variable_declaration) && isset($entry->scope->template_parameters[$entry->name])) {
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
			$candidates /** vector<collected_name> */ = [];
			if (isset($entry->scope->variables[$entry->name])) {
				$candidates = $entry->scope->variables[$entry->name];
			}
			if (q_count($candidates) !== 1) {
				throw new \RuntimeException(("LLVM experiment needs one same-file declaration for " . $entry->name . " at token " . $entry->token_index));
			}
			if ($candidates[0]->file !== $file) {
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
			$current_scope = $entry->scope;
			$candidates /** vector<collected_name> */ = [];
			while (true)
			{
				$candidates = [];
				if (isset($current_scope->functions[$entry->name])) {
					$candidates = $current_scope->functions[$entry->name];
				}
				if (q_count($candidates) !== 0) {
					break;
				}
				if ($current_scope->parent === null) {
					break;
				}
				$current_scope = object_cast($current_scope->parent, scope::class);
			}
			if (q_count($candidates) !== 1) {
				throw new \RuntimeException(("Expected one function target for " . $entry->name . " at " . $file->source->file->path . ": token " . $entry->token_index));
			}
			$result->function_references[$entry->token_index] = $candidates[0];
		}
		foreach ($file->type_references as $index)
		{
			$entry = $entries[$index];
			$current_scope = $entry->scope;
			while (true)
			{
				if (isset($current_scope->template_parameters[$entry->name])) {
					$result->template_slots[$entry->token_index] = $current_scope->template_parameters[$entry->name];
					break;
				}
				$type_candidates /** vector<collected_name> */ = [];
				if (isset($current_scope->types[$entry->name])) {
					$type_candidates = $current_scope->types[$entry->name];
				}
				if (q_count($type_candidates) !== 0) {
					if (q_count($type_candidates) !== 1) {
						throw new \RuntimeException('Ambiguous struct type: ' . $entry->name);
					}
					$result->types[$entry->token_index] = $type_candidates[0];
					break;
				}
				if ($current_scope->parent === null) {
					break;
				}
				$current_scope = object_cast($current_scope->parent, scope::class);
			}
		}
		return $result;
	}
}
