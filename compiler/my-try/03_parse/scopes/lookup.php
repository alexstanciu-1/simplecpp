<?php

/* Role: lookup shared names through lexical and publication scopes. */
namespace scpp\compiler;

/** File-local ownership does not introduce a separate language-level global scope. */
final class Scope_Lookup
{
	/** Tombstones remain indexed for update consumers but are never resolution candidates. */
	public static function live(array $entries /** vector<collected_name> */): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		foreach ($entries as $entry) {
			if ($entry->changes !== \scpp\compiler\SYNC_DELETED) {
				$result[] = $entry;
			}
		}
		return $result;
	}

	/** Resolve the nearest live type pool, including the language/runtime parent. */
	public static function types(scope $start, string $name): array /** vector<type_definition> */
	{
		$current_scope = $start;
		$result /** vector<type_definition> */ = [];
		while (true)
		{
			$current_scope = self::visible($current_scope);
			$result = [];
			foreach ($current_scope->types_named($name) as $definition)
			{
				if ($definition->declaration !== null) {
					$entry /** collected_name */ = $definition->declaration;
					if ($entry->changes === \scpp\compiler\SYNC_DELETED) {
						continue;
					}
				}
				$result[] = $definition;
			}
			if (q_count($result) !== 0) {
				break;
			}
			$parent = $current_scope->parent_scope();
			if ($parent === null) {
				break;
			}
			$parent_scope /** scope */ = $parent;
			$current_scope = $parent_scope;
		}
		return $result;
	}

	public static function visible(scope $local_scope): scope
	{
		$published = $local_scope->published_scope();
		if ($published === null) {
			return $local_scope;
		}
		return $published;
	}
}
