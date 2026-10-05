<?php

/* Shared bounded value-result admission, independent of selection syntax. */
namespace scpp\compiler;

final class Selection_Result_Preparation
{
	/** Boxed-led selections retain their family; match keeps its scalar-only domain. */
	public static function boxed_join(canonical_type_use $left, canonical_type_use $right,
		preparation_context $context): canonical_type_use
	{
		$result = Boxed_Conversions::is_boxed($left) ? $left : $right;
		// Conversion admission rejects wrappers, bare null, modifiers and distinct boxed identities.
		Conversion_Preparation::decide($left, $result, conversion_context::conditional_branch);
		Conversion_Preparation::decide($right, $result, conversion_context::conditional_branch);
		Capability_Preparation::require_copy($left, $context);
		Capability_Preparation::require_copy($right, $context);
		Capability_Preparation::require_copy($result, $context);
		return $result;
	}

	/** Establish an owned scalar result, without inventing a cross-type promotion. */
	public static function begin(canonical_type_use $type, preparation_context $context, string $selection): canonical_type_use
	{
		self::require_scalar($type, $selection);
		Capability_Preparation::require_copy($type, $context);
		return $type;
	}

	/** Every alternative must agree with the selected result domain before emission. */
	public static function branch(canonical_type_use $source, canonical_type_use $result, string $selection): conversion_decision
	{
		self::require_scalar($source, $selection);
		if (!$source->matches($result)) {
			throw new \RuntimeException('S2S ' . $selection . ' branches require matching scalar types');
		}
		return Conversion_Preparation::decide($source, $result, conversion_context::conditional_branch);
	}

	private static function require_scalar(canonical_type_use $type, string $selection): void
	{
		if (!Conversion_Preparation::is_scalar($type)) {
			throw new \RuntimeException('S2S ' . $selection . ' branches require supported scalar value types');
		}
	}
}
