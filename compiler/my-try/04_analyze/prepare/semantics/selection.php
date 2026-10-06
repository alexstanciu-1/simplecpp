<?php
namespace scpp\compiler;

/** Shared selector-domain and constant-label admission for switch and match. */
final class Selection_Preparation
{
	public static function require_selector(canonical_type_use $type, preparation_context $context, string $role): void
	{
		if ($type->matches($context->integer) || Type_Queries::is_enum($type)) { return; }
		throw new \RuntimeException('S2S ' . $role . ' selector requires canonical int or enum');
	}

	public static function label(expression_node $syntax, canonical_type_use $selector,
		preparation_context $context, string $role): prepared_selection_constant
	{
		if ($selector->matches($context->integer)) {
			$integer = new prepared_integer_selection();
			$integer->type = $selector;
			$integer->decimal = Integer_Constants::label($syntax, $context, $role);
			return $integer;
		}
		$value = Expression_Preparation::prepare($syntax, $context);
		if (!$value->type->matches($selector)) {
			throw new \RuntimeException('S2S ' . $role . ' requires the selector enum type');
		}
		if (!($value instanceof prepared_enum_case_reference)) {
			throw new \RuntimeException('S2S ' . $role . ' requires a constant enum case reference');
		}
		$reference = object_cast($value, prepared_enum_case_reference::class);
		$enumeration = new prepared_enum_selection();
		$enumeration->type = $selector;
		$enumeration->selected_case = $reference->selected_case;
		return $enumeration;
	}
}
