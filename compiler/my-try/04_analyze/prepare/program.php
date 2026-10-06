<?php
namespace scpp\compiler;

/** Executable-program admission is semantic preparation, not backend policy. */
final class Program_Preparation
{
	public static function entry(Storage $files /** Storage<prepared_file> */,
		?program_entry_selection $selection, bool $required): ?prepared_file
	{
		$selected /** nullable<prepared_file> */ = null;
		$names /** hash<bool> */ = [];
		foreach (Model::$modules as $module) {
			if ($module->changes === change_state::deleted) { continue; }
			$name = Source_Registry::logical_module($module);
			if (isset($names[$name])) { throw new \RuntimeException('Duplicate logical module: ' . $name); }
			$names[$name] = true;
		}
		if ($selection === null) {
			if (q_count($files) === 1) { $selected = $files[0]; }
			elseif (!$required) { return null; }
		}
		else {
			$choice /** program_entry_selection */ = $selection;
			foreach ($files as $prepared) {
				$source = $prepared->source;
				if (($source->source_file()->path === $choice->source_path)
					&& (Source_Registry::collection_module($source) === $choice->module_name)) {
					if ($selected !== null) { throw new \RuntimeException('Ambiguous program entry'); }
					$selected = $prepared;
				}
			}
		}
		if ($selected === null) { throw new \RuntimeException('Select an existing program entry by logical module and relative source path'); }
		$entry /** prepared_file */ = $selected;
		foreach ($files as $prepared) {
			if ($prepared !== $entry) {
				if (!$prepared->source->root->body->statements->is_empty()) {
					throw new \RuntimeException('Top-level executable statements are only allowed in the selected entry: '
						. $prepared->source->source_file()->path);
				}
			}
		}
		return $entry;
	}
}
