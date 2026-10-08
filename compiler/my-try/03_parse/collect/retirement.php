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
			if ($entry instanceof collected_source_callable) {
				$syntax = object_cast($entry, collected_source_callable::class)->callable_syntax();
				$retired /** Storage<function_body_node> */ = $syntax->retired_bodies;
				if (!$retired->is_empty()) { $syntax->retired_bodies = new Storage /** Storage<function_body_node> */(); }
			}
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
			if ($entry instanceof collected_type_definition) {
				Model::$type_catalog->source_declarations()->retire(object_cast($entry, collected_type_definition::class));
			}
			$removed[] = $entry->local_index;
		}
		foreach ($removed as $index) {
			$entries->remove($index);
		}
		$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
		$removed_anonymous /** vector<int> */ = [];
		foreach ($anonymous as $identity) {
			if ($source->deleted || $identity->deleted) {
				Model::$type_catalog->closures()->retire($identity);
				$identity->deleted = true;
				$removed_anonymous[] = $identity->local_index;
			}
		}
		foreach ($removed_anonymous as $index) { $anonymous->remove($index); }
		$source->defined_elements = self::present($entries, $source->defined_elements);
		$source->variable_references = self::present($entries, $source->variable_references);
		$source->constant_references = self::present($entries, $source->constant_references);
		$source->call_references = self::present($entries, $source->call_references);
		$source->type_references = self::present($entries, $source->type_references);
		$source->field_references = self::present($entries, $source->field_references);
		$source->pending_bindings = self::present($entries, $source->pending_bindings);
		$retired_bodies /** Storage<namespace_body_node> */ = $source->root->retired_namespace_bodies;
		if (!$retired_bodies->is_empty()) {
			$source->root->retired_namespace_bodies = new Storage /** Storage<namespace_body_node> */();
		}
		$retired_scopes /** Storage<scope> */ = $source->retired_scopes;
		if (!$retired_scopes->is_empty()) {
			$source->retired_scopes = new Storage /** Storage<scope> */();
		}
		if ($source->deleted)
		{
			$source->parse_complete = false;
			$source->root->namespace_bodies = new Storage /** Storage<namespace_body_node> */();
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
