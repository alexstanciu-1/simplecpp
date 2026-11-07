<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Resolve written types once at declaration/cast boundaries; generated registration owns available families and exports. */
final class Type_Resolution
{
	/** Preserve simple-name lookup; validate arity and resolve each argument once before specialization lookup. */
	public static function resolve(ast_type_reference $syntax, bool $allow_void = false): ?type_definition
	{
		$arity = type_registry::generic_arity($syntax->name);
		$child = $syntax->first_child;
		if ($arity === null) {
			if ($child !== null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type does not accept arguments: ' . $syntax->name);
			}
			return ($allow_void && $syntax->name === 'void') ? type_void::instance() : type_registry::resolve($syntax->name);
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
