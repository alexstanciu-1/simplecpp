<?php

/* Select explicit conversions whose requested result is the runtime string type. */
namespace scpp\compiler;

final class String_Conversions
{
	public static function is_string(canonical_type_use $type): bool
	{
		return $type->matches(Language_Types::string_type(Model::$language_scope));
	}

	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ((($context === conversion_context::explicit_cast) || ($context === conversion_context::interpolation))
			&& Conversion_Preparation::is_scalar($source)) {
			return Conversion_Preparation::operation(
				$source, $target, $context, conversion_operation::explicit_runtime_cast);
		}

		if ($context !== conversion_context::explicit_cast) {
			throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
		}
		throw new \RuntimeException('S2S explicit string cast is not supported for this source type');
	}
}
