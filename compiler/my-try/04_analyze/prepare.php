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
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			if (($entry->kind === collected_name_kind::variable_declaration) && isset($entry_scope->template_parameters[$entry->name])) {
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
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			$candidates /** vector<collected_name> */ = [];
			$variable_scope = Scope_Lookup::visible($entry_scope);
			if (isset($variable_scope->variables[$entry->name])) {
				$candidates = $variable_scope->variables[$entry->name];
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
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			$current_scope = $entry_scope;
			$candidates /** vector<collected_name> */ = [];
			while (true)
			{
				$current_scope = Scope_Lookup::visible($current_scope);
				$candidates = [];
				if (isset($current_scope->functions[$entry->name])) {
					$candidates = $current_scope->functions[$entry->name];
				}
				if (q_count($candidates) !== 0) {
					break;
				}
				$parent = weakref_get($current_scope->parent);
				if ($parent === null) {
					break;
				}
				$current_scope = object_cast($parent, scope::class);
			}
			if (q_count($candidates) !== 1) {
				throw new \RuntimeException(("Expected one function target for " . $entry->name . " at " . $file->source->file->path . ": token " . $entry->token_index));
			}
			$result->function_references[$entry->token_index] = $candidates[0];
		}
		foreach ($file->type_references as $index)
		{
			$entry = $entries[$index];
			$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
			$current_scope = $entry_scope;
			while (true)
			{
				$current_scope = Scope_Lookup::visible($current_scope);
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
				$parent = weakref_get($current_scope->parent);
				if ($parent === null) {
					break;
				}
				$current_scope = object_cast($parent, scope::class);
			}
		}
		return $result;
	}
}

/** File-local ownership does not introduce a separate language-level global scope. */
final class Scope_Lookup
{
	public static function visible(scope $local_scope): scope
	{
		$published = weakref_get($local_scope->publication);
		if ($published === null) {
			return $local_scope;
		}
		return object_cast($published, scope::class);
	}
}
