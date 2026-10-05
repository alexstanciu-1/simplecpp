<?php

/* Analysis-owned lexical resolution records every visited pool before selecting candidates. */
namespace scpp\compiler;

/** Deletion cleanup precedes resolution; lookups see only active index membership. */
final class Observed_Lookup
{
	/** Resolve the nearest exact constant pool in its independent namespace. */
	public static function constants(scope $start, string $name, preparation_context $context): array /** vector<constant_definition> */
	{
		$current_scope = $start;
		$result /** vector<constant_definition> */ = [];
		while (true)
		{
			$current_scope = Scope_Lookup::visible($current_scope);
			$context->worker->observe($current_scope, $name, preparation_lookup_kind::constant, $context->owner);
			$result = $current_scope->constants_named($name);
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

	/** Resolve the nearest live type pool, including the language/runtime parent. */
	public static function types(scope $start, string $name, preparation_context $context): array /** vector<type_definition_i> */
	{
		$current_scope = $start;
		$result /** vector<type_definition_i> */ = [];
		while (true)
		{
			$current_scope = Scope_Lookup::visible($current_scope);
			$context->worker->observe($current_scope, $name, preparation_lookup_kind::type, $context->owner);
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
	public static function functions(scope $start, string $name, preparation_context $context): array /** vector<callable_target_i> */
	{
		$current_scope = $start;
		$result /** vector<callable_target_i> */ = [];
		while (true)
		{
			$current_scope = Scope_Lookup::visible($current_scope);
			$context->worker->observe($current_scope, $name, preparation_lookup_kind::function_name, $context->owner);
			$result = $current_scope->callables_named($name);
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
}
