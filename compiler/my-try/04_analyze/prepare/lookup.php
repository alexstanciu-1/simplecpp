<?php

/* Analysis-owned lexical resolution records every visited pool before selecting candidates. */
namespace scpp\compiler;

/** Deletion cleanup precedes resolution; lookups see only active index membership. */
final class Observed_Lookup
{
	/** Preparation runs after the parse join; intern misses in the existing registry.
	 * Empty namespaces are identity/pool owners, not declarations. Later publication
	 * fills that same pool, so ordinary membership observations detect additions.
	 */
	private static function qualified_pool(scope $start, string $name, preparation_lookup_kind $kind,
		preparation_context $context): scope
	{
		$identity = Model::$namespaces->register(Source_Name_Path::namespace_path($start, $name));
		$members = $identity->published_scope();
		$context->worker->observe($members, Source_Name_Path::member($name), $kind, $context->owner);
		return $members;
	}

	/** Resolve the nearest exact constant pool in its independent namespace. */
	public static function constants(scope $start, string $name, preparation_context $context): array /** vector<constant_target_i> */
	{
		if (Source_Name_Path::separator($name) >= 0) {
			$members = self::qualified_pool($start, $name, preparation_lookup_kind::constant, $context);
			return $members->constants_named(Source_Name_Path::member($name));
		}
		$current_scope = $start;
		$result /** vector<constant_target_i> */ = [];
		while (true)
		{
			if ($current_scope->has_import_environment()) {
				$context->worker->observe($current_scope, $name, preparation_lookup_kind::import_name, $context->owner);
			}
			foreach ($current_scope->imports_named($name) as $binding) {
				if (($binding->restriction !== import_kind::symbol) && ($binding->restriction !== import_kind::constant)) { continue; }
				$imported = self::constants($current_scope, $binding->target, $context);
				if (q_count($imported) !== 0) { return $imported; }
			}
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
		if (Source_Name_Path::separator($name) >= 0) {
			$members = self::qualified_pool($start, $name, preparation_lookup_kind::type, $context);
			return $members->types_named(Source_Name_Path::member($name));
		}
		$current_scope = $start;
		$result /** vector<type_definition_i> */ = [];
		while (true)
		{
			if ($current_scope->has_import_environment()) {
				$context->worker->observe($current_scope, $name, preparation_lookup_kind::import_name, $context->owner);
			}
			foreach ($current_scope->imports_named($name) as $binding) {
				if ($binding->restriction !== import_kind::symbol) { continue; }
				$imported = self::types($current_scope, $binding->target, $context);
				if (q_count($imported) !== 0) { return $imported; }
			}
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
		if (Source_Name_Path::separator($name) >= 0) {
			$members = self::qualified_pool($start, $name, preparation_lookup_kind::function_name, $context);
			return $members->callables_named(Source_Name_Path::member($name));
		}
		$current_scope = $start;
		$result /** vector<callable_target_i> */ = [];
		while (true)
		{
			if ($current_scope->has_import_environment()) {
				$context->worker->observe($current_scope, $name, preparation_lookup_kind::import_name, $context->owner);
			}
			foreach ($current_scope->imports_named($name) as $binding) {
				if (($binding->restriction !== import_kind::symbol) && ($binding->restriction !== import_kind::function_name)) { continue; }
				$imported = self::functions($current_scope, $binding->target, $context);
				if (q_count($imported) !== 0) { return $imported; }
			}
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
