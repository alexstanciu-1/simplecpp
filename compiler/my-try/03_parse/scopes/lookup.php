<?php

/* Role: lookup shared names through lexical and publication scopes. */
namespace scpp\compiler;

/** Deletion cleanup precedes resolution; lookups see only active index membership. */
final class Scope_Lookup
{
	/** Resolve the nearest live type pool, including the language/runtime parent. */
	public static function types(scope $start, string $name, ?preparation_context $context = null): array /** vector<type_definition> */
	{
		$current_scope = $start;
		$result /** vector<type_definition> */ = [];
		while (true)
		{
			$current_scope = self::visible($current_scope);
			if ($context !== null) {
				$context->worker->observe($current_scope, $name, preparation_lookup_kind::type, $context->owner);
			}
			$result = $current_scope->types_named($name);
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

	/** Callable lookup follows the same nearest-pool rule as type lookup. */
	public static function functions(scope $start, string $name, ?preparation_context $context = null): array /** vector<collected_name> */
	{
		$current_scope = $start;
		$result /** vector<collected_name> */ = [];
		while (true)
		{
			$current_scope = self::visible($current_scope);
			if ($context !== null) {
				$context->worker->observe($current_scope, $name, preparation_lookup_kind::function_name, $context->owner);
			}
			$result = $current_scope->functions_named($name);
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
