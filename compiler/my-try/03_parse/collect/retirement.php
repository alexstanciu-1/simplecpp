<?php

/* Frontend-owned disposal after the coordinator has completed semantic retirement. */
namespace scpp\compiler;

final class Collection_Retirement
{
	public static function remove_deleted_sources(Storage $sources /** Storage<collected_file> */): void
	{
		foreach ($sources as $source) {
			self::remove_deleted($source);
		}
	}

	/** Relationships are already detached; remove collected rows and their scope/index memberships. */
	private static function remove_deleted(collected_file $source): void
	{
		$entries /** Storage<collected_name> */ = $source->entries;
		$removed /** vector<int> */ = [];
		foreach ($entries as $entry)
		{
			if (!$source->deleted && ($entry->change_status !== change_state::deleted)) {
				continue;
			}
			$entry_scope = object_cast(weakref_get($entry->scope), scope::class);
			$entry_scope->unregister($entry);
			$global = $entry_scope->published_scope();
			if ($entry->is_exported() && ($global !== null)) {
				$published /** scope */ = $global;
				$published->unregister($entry);
			}
			if ($entry instanceof collected_struct) {
				Model::$type_catalog->source_declarations()->retire(object_cast($entry, collected_struct::class));
			}
			$removed[] = $entry->local_index;
		}
		foreach ($removed as $index) {
			$entries->remove($index);
		}
		$source->defined_elements = self::present($entries, $source->defined_elements);
		$source->variable_references = self::present($entries, $source->variable_references);
		$source->constant_references = self::present($entries, $source->constant_references);
		$source->function_references = self::present($entries, $source->function_references);
		$source->type_references = self::present($entries, $source->type_references);
		$source->field_references = self::present($entries, $source->field_references);
		$source->pending_bindings = self::present($entries, $source->pending_bindings);
		if ($source->deleted)
		{
			$source->parse_complete = false;
			$source->root->declarations = new Storage /** Storage<declaration_node> */();
			$source->root->body->statements = new Storage /** Storage<statement_node> */();
		}
	}

	/** Compact occurrence work lists after their collected rows have been removed. */
	private static function present(Storage $entries /** Storage<collected_name> */, array $indexes /** vector<int> */): array /** vector<int> */
	{
		$result /** vector<int> */ = [];
		foreach ($indexes as $index) {
			if (isset($entries[$index])) {
				$result[] = $index;
			}
		}
		return $result;
	}
}
