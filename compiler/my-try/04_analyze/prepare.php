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
		foreach ($file->defined_elements as $index) {
			$entry = $file->entries[$index];
			if (($entry->kind === collected_name_kind::variable_declaration) && isset($entry->scope->template_parameters[$entry->name])) {
				throw new \RuntimeException('Variable conflicts with template parameter: ' . $entry->name);
			}
			$result->declarations[$entry->token_index] = $entry;
		}
		foreach (array_merge($file->variable_references, $file->pending_bindings) as $index)
		{
			$entry = $file->entries[$index];
			$candidates = $entry->scope->variables[$entry->name] ?? [];
			if ((count($candidates) !== 1) || ($candidates[0]->file !== $file)) {
				throw new \RuntimeException("LLVM experiment needs one same-file declaration for {$entry->name} at token {$entry->token_index}");
			}
			if (($entry->kind === collected_name_kind::binding) && ($candidates[0]->token_index >= $entry->token_index)) {
				throw new \RuntimeException('Assignment requires an existing declaration');
			}
			$result->references[$entry->token_index] = $candidates[0];
		}
		foreach ($file->function_references as $index)
		{
			$entry = $file->entries[$index];
			$scope = $entry->scope;
			$candidates /** vector<collected_name> */ = [];
			while ($scope !== null) {
				$candidates = $scope->functions[$entry->name] ?? [];
				if ($candidates !== []) {
					break;
				}
				$scope = $scope->parent;
			}
			if (count($candidates) !== 1) {
				throw new \RuntimeException("Expected one function target for {$entry->name} at {$file->source->file->path}: token {$entry->token_index}");
			}
			$result->function_references[$entry->token_index] = $candidates[0];
		}
		foreach ($file->type_references as $index)
		{
			$entry = $file->entries[$index];
			$scope = $entry->scope;
			while ($scope !== null)
			{
				if (isset($scope->template_parameters[$entry->name])) {
					$result->template_slots[$entry->token_index] = $scope->template_parameters[$entry->name];
					break;
				}
				$candidates = $scope->types[$entry->name] ?? [];
				if ($candidates !== []) {
					if (count($candidates) !== 1) {
						throw new \RuntimeException('Ambiguous struct type: ' . $entry->name);
					}
					$result->types[$entry->token_index] = $candidates[0];
					break;
				}
				$scope = $scope->parent;
			}
		}
		return $result;
	}
}
