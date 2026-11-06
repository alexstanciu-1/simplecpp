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
		// The currently generated family has exactly one argument; no list, recount or repeated traversal.
		if (($child === null) || ($child->next_sibling !== null)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type requires exactly one argument: ' . $syntax->name);
		}
		$element = self::resolve($child);
		if ($element === null) {
			throw new diagnostic($child->source(), $child->byte_start(), 'Unknown or unsupported type: ' . $child->spelling());
		}
		$type = type_registry::specialize($syntax->name, $element);
		if ($type === null) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unavailable runtime specialization: ' . $syntax->spelling());
		}
		return $type;
	}
}
