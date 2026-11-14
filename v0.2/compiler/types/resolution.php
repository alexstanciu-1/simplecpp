<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Resolve written types once at declaration/cast boundaries; generated registration owns available families and exports. */
final class Type_Resolution
{
	/** Runtime types resolve directly. Application types register a body watch even when lookup fails. */
	public static function local(ast_type_reference $syntax, scope $symbols, ast_function_body $body, ?Dependency_Watches $dependencies): ?type_definition
	{
		return self::named($syntax, $symbols, $body->scope->namespace, $body, $dependencies);
	}

	/** Resolve runtime signature types directly; callables watch the nominal records named in their signatures. */
	public static function signature(ast_type_reference $syntax, scope $symbols, ast_function_declaration $function,
		?Dependency_Watches $dependencies, bool $allow_void = false): ?type_definition
	{
		if (in_array($syntax->name, ['shared', 'weak'], true) && $syntax->first_child !== null) {
			$function->watch_signature($dependencies);
			return self::handle($syntax, $symbols, $function->namespace, $function, $dependencies);
		}
		$type = self::resolve($syntax, $allow_void);
		if ($type !== null) return $type;
		$function->watch_signature($dependencies);
		return self::application($syntax, $symbols, $function, $dependencies);
	}

	/** Both handle families require exactly one nominal struct payload. */
	private static function handle(ast_type_reference $syntax, scope $symbols, ?string $namespace,
		dependency_watcher $watcher, ?Dependency_Watches $dependencies): struct_handle_definition
	{
		$child = $syntax->first_child;
		if ($child->next_sibling === null) {
			$payload = self::named($child, $symbols, $namespace, $watcher, $dependencies);
			if ($payload instanceof struct_type_definition) return $syntax->name === 'weak' ? $payload->weak : $payload->shared;
		}
		throw new diagnostic($syntax->source(), $syntax->byte_start(), ucfirst($syntax->name) . ' type requires one struct payload: ' . $syntax->spelling());
	}

	/** Share canonical lookup and missing-symbol watches between local and inline field declarations. */
	public static function named(ast_type_reference $syntax, scope $symbols, ?string $namespace, dependency_watcher $watcher,
		?Dependency_Watches $dependencies): ?type_definition
	{
		if (in_array($syntax->name, ['shared', 'weak'], true) && $syntax->first_child !== null) {
			return self::handle($syntax, $symbols, $namespace, $watcher, $dependencies);
		}
		$type = self::resolve($syntax);
		if ($type !== null) return $type;
		return self::application($syntax, $symbols, $watcher, $dependencies);
	}

	/** Subscribe to the stable nominal key, including a missing declaration that may be added later. */
	private static function application(ast_type_reference $syntax, scope $symbols, dependency_watcher $watcher,
		?Dependency_Watches $dependencies): ?type_definition
	{
		$entry = $symbols->entry($syntax->root()->type_key($syntax->name));
		$dependencies?->watch($entry, $watcher);
		$record = $entry->declaration();
		return $record instanceof ast_type_declaration ? $record->type_definition() : null;
	}

	/** Preserve simple-name lookup; validate arity and resolve each argument once before specialization lookup. */
	public static function resolve(ast_type_reference $syntax, bool $allow_void = false): ?type_definition
	{
		$arity = type_registry::generic_arity($syntax->name);
		$child = $syntax->first_child;
		if ($arity === null) {
			if ($child !== null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type does not accept arguments: ' . $syntax->name);
			}
			$type = ($allow_void && $syntax->name === 'void') ? type_void::instance() : type_registry::resolve($syntax->name);
			$module = type_registry::required_module($syntax->name);
			if ($type !== null && $module !== null && !$syntax->root()->type_availability->allows($module)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type ' . $syntax->name . ' requires module ' . $module);
			}
			return $type;
		}
		// Current families have one or two arguments; walk the chain once without an intermediate collection.
		$second = $child?->next_sibling;
		if (($child === null) || (($arity === 1) && ($second !== null))
			|| (($arity === 2) && (($second === null) || ($second->next_sibling !== null)))) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(),
				'Type requires exactly ' . ($arity === 1 ? 'one argument: ' : 'two arguments: ') . $syntax->name);
		}
		$element = self::argument($child);
		$key = $second === null ? null : self::argument($second);
		$type = type_registry::specialize($syntax->name, $element, $key);
		if ($type === null) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unavailable runtime specialization: ' . $syntax->spelling());
		}
		return $type;
	}
	/** Resolve each written argument once and retain its own diagnostic location. */
	private static function argument(ast_type_reference $syntax): type_definition
	{
		return self::resolve($syntax) ?? throw new diagnostic($syntax->source(), $syntax->byte_start(),
			'Unknown or unsupported type: ' . $syntax->spelling());
	}

}
