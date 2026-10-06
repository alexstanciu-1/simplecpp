<?php

/* Role: lookup shared names through lexical and publication scopes. */
namespace scpp\compiler;

/** Source path interpretation only; no registry mutation, imports or backend spelling. */
final class Source_Name_Path
{
	public static function separator(string $name): int
	{
		$last = -1;
		for ($index = 0; $index < string_byte_len($name); $index++) {
			if (string_byte_at($name, $index) === 92) {
				$last = $index;
			}
		}
		return $last;
	}

	public static function member(string $name): string
	{
		$offset = self::separator($name) + 1;
		return string_byte_slice($name, $offset, string_byte_len($name) - $offset);
	}

	/** Call only for qualified spelling; an empty result denotes the source root. */
	public static function namespace_path(scope $start, string $name): string
	{
		$last = self::separator($name);
		if ($last < 0) {
			throw new \LogicException('Namespace path requires a qualified source name');
		}
		if (string_byte_at($name, 0) === 92) {
			return $last === 0 ? '' : string_byte_slice($name, 1, $last - 1);
		}
		$path = string_byte_slice($name, 0, $last);
		$identity = $start->source_namespace();
		if ($identity !== null) {
			return $identity->member_name($path);
		}
		return $path;
	}
}

/** Deletion cleanup precedes resolution; lookups see only active index membership. */
final class Scope_Lookup
{
	/** Resolve the nearest exact constant pool in its independent namespace. */
	public static function constants(scope $start, string $name): array /** vector<constant_target_i> */
	{
		if (Source_Name_Path::separator($name) >= 0) {
			$identity = Model::$namespaces->find(Source_Name_Path::namespace_path($start, $name));
			if ($identity === null) {
				return /** vector<constant_target_i> */ [];
			}
			return $identity->published_scope()->constants_named(Source_Name_Path::member($name));
		}
		$current_scope = $start;
		$result /** vector<constant_target_i> */ = [];
		while (true)
		{
			foreach ($current_scope->imports_named($name) as $binding) {
				if (($binding->restriction !== import_kind::symbol) && ($binding->restriction !== import_kind::constant)) { continue; }
				$imported = self::constants($current_scope, $binding->target);
				if (q_count($imported) !== 0) { return $imported; }
			}
			$current_scope = self::visible($current_scope);
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
	public static function types(scope $start, string $name): array /** vector<type_definition_i> */
	{
		if (Source_Name_Path::separator($name) >= 0) {
			$identity = Model::$namespaces->find(Source_Name_Path::namespace_path($start, $name));
			if ($identity === null) {
				return /** vector<type_definition_i> */ [];
			}
			return $identity->published_scope()->types_named(Source_Name_Path::member($name));
		}
		$current_scope = $start;
		$result /** vector<type_definition_i> */ = [];
		while (true)
		{
			foreach ($current_scope->imports_named($name) as $binding) {
				if ($binding->restriction !== import_kind::symbol) { continue; }
				$imported = self::types($current_scope, $binding->target);
				if (q_count($imported) !== 0) { return $imported; }
			}
			$current_scope = self::visible($current_scope);
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
	public static function functions(scope $start, string $name): array /** vector<callable_definition_i> */
	{
		if (Source_Name_Path::separator($name) >= 0) {
			$identity = Model::$namespaces->find(Source_Name_Path::namespace_path($start, $name));
			if ($identity === null) {
				return /** vector<callable_definition_i> */ [];
			}
			return $identity->published_scope()->callables_named(Source_Name_Path::member($name));
		}
		$current_scope = $start;
		$result /** vector<callable_definition_i> */ = [];
		while (true)
		{
			foreach ($current_scope->imports_named($name) as $binding) {
				if (($binding->restriction !== import_kind::symbol) && ($binding->restriction !== import_kind::function_name)) { continue; }
				$imported = self::functions($current_scope, $binding->target);
				if (q_count($imported) !== 0) { return $imported; }
			}
			$current_scope = self::visible($current_scope);
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

	public static function visible(scope $local_scope): scope
	{
		$published = $local_scope->published_scope();
		if ($published === null) {
			return $local_scope;
		}
		return $published;
	}
}
