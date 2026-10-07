<?php

/* Bounded scalar constants and integer-label queries, independent of backend spelling. */
namespace scpp\compiler;

/** Bounded compile-time scalar evaluation. Never evaluates host PHP or runtime calls. */
final class Constant_Preparation
{
	public static function declaration(constant_declaration_node $syntax, preparation_context $context): void
	{
		$entry = object_cast($syntax->occurrence(), collected_constant::class);
		$public = true;
		if ($syntax instanceof member_constant_node) {
			$modifiers = object_cast($syntax, member_constant_node::class)->modifiers;
			Member_Preparation::validate_modifiers($modifiers, false);
			$public = Member_Preparation::is_public($modifiers);
			$context->receiver_record = weakref_get($entry->record_owner);
		}
		$declaring_scope = object_cast(weakref_get($entry->scope), scope::class);
		$candidates = Observed_Lookup::constants($declaring_scope, $entry->name, $context);
		if (q_count($candidates) !== 1) {
			throw new \RuntimeException('S2S duplicate constant name: ' . $entry->name);
		}
		Preparation_Cleanup::tree($syntax->initializer);
		$value = Constant_Expression_Preparation::evaluate($syntax->initializer, $context);
		if (!$value->type->matches($context->integer) && !$value->type->matches($context->floating)
			&& !$value->type->matches($context->boolean) && !$value->type->matches($context->string_type)) {
			throw new \RuntimeException('S2S source constants require bool, int, float or string in a constant initializer');
		}
		$syntax->set_preparation(new prepared_source_constant($value, $public));
	}

}

/** Shared expression evaluator; declaration lookup/visibility policy remains with its caller. */
final class Constant_Expression_Preparation
{
	public static function evaluate(expression_node $syntax, preparation_context $context): constant_value
	{
		if ($syntax instanceof null_literal_node) {
			$null_value = new sentinel_constant_value();
			$null_value->type = Expression_Preparation::prepare($syntax, $context)->type;
			return $null_value;
		}
		if ($syntax instanceof static_member_reference_node) {
			object_cast($syntax, static_member_reference_node::class)->require_type_owner();
			$static_value = Expression_Preparation::prepare($syntax, $context);
			if ($static_value instanceof prepared_constant_reference) { return object_cast($static_value, prepared_constant_reference::class)->value; }
			if ($static_value instanceof prepared_enum_case_reference) {
				$enum_reference = object_cast($static_value, prepared_enum_case_reference::class);
				$enum_value = new enum_constant_value();
				$enum_value->type = $enum_reference->type;
				$enum_value->selected_case = $enum_reference->selected_case;
				return $enum_value;
			}
			throw new \RuntimeException('S2S constant initializer requires a supported constant value');
		}
		if ($syntax instanceof unary_expression_node) {
			return self::signed_value(object_cast($syntax, unary_expression_node::class), $context);
		}
		if ($syntax instanceof constant_reference_node) {
			$reference = object_cast($syntax, constant_reference_node::class);
			Expression_Preparation::prepare($reference, $context);
			return $reference->require_constant_reference_preparation()->value;
		}
		if ($syntax instanceof integer_literal_node) {
			$facts = object_cast(Expression_Preparation::prepare($syntax, $context), prepared_integer_literal::class);
			$value = new integer_constant_value();
			$value->type = $facts->type;
			$value->decimal = $facts->decimal;
			return $value;
		}
		if ($syntax instanceof float_literal_node) {
			$floating = object_cast(Expression_Preparation::prepare($syntax, $context), prepared_float_literal::class);
			$float_value = new float_constant_value();
			$float_value->type = $floating->type;
			$float_value->decimal = $floating->decimal;
			return $float_value;
		}
		if ($syntax instanceof boolean_literal_node) {
			$boolean = object_cast(Expression_Preparation::prepare($syntax, $context), prepared_boolean_literal::class);
			$bool_value = new boolean_constant_value();
			$bool_value->type = $boolean->type;
			$bool_value->value = $boolean->value;
			return $bool_value;
		}
		if ($syntax instanceof string_literal_node) {
			$string = object_cast(Expression_Preparation::prepare($syntax, $context), prepared_string_literal::class);
			$string_value = new string_constant_value();
			$string_value->type = $string->type;
			$string_value->value = $string->value;
			return $string_value;
		}
		throw new \RuntimeException('S2S constant initializer requires a scalar literal, constant reference or numeric sign');
	}

	private static function signed_value(unary_expression_node $syntax, preparation_context $context): constant_value
	{
		$tokens = $context->collection->token_snapshot();
		$sign = $tokens->text_at($syntax->operator_token_index);
		if (($sign !== '+') && ($sign !== '-')) {
			throw new \RuntimeException('S2S constant initializer supports only numeric unary signs');
		}
		// The sole magnitude outside positive int range that has a representable negative value.
		if ($sign === '-') {
			if ($syntax->operand instanceof integer_literal_node) {
				if ($tokens->text_at($syntax->operand->start_token()) === '9223372036854775808') {
					$minimum = new integer_constant_value();
					$minimum->type = $context->integer;
					$minimum->decimal = '-9223372036854775808';
					return $minimum;
				}
			}
		}
		$operand = self::evaluate($syntax->operand, $context);
		if ($operand instanceof integer_constant_value) {
			$integer = object_cast($operand, integer_constant_value::class);
			$result = new integer_constant_value();
			$result->type = $integer->type;
			$result->decimal = $sign === '-' ? Integer_Constants::negate($integer->decimal) : $integer->decimal;
			return $result;
		}
		if ($operand instanceof float_constant_value) {
			$floating = object_cast($operand, float_constant_value::class);
			$float_result = new float_constant_value();
			$float_result->type = $floating->type;
			$decimal = $floating->decimal;
			if ($sign === '-') {
				$decimal = string_byte_starts_with($decimal, '-')
					? string_byte_slice($decimal, 1, string_byte_len($decimal) - 1) : '-' . $decimal;
			}
			$float_result->decimal = $decimal;
			return $float_result;
		}
		throw new \RuntimeException('S2S constant numeric sign requires int or float');
	}
}

final class Integer_Constants
{
	/** Switch and match share canonical integer admission and exact label resolution. */
	public static function label(expression_node $syntax, preparation_context $context, string $role): string
	{
		$prepared = Expression_Preparation::prepare($syntax, $context);
		if (!$prepared->type->matches($context->integer)) {
			throw new \RuntimeException('S2S ' . $role . ' requires canonical int');
		}
		return self::value($syntax);
	}

	/** Read already-prepared constant forms; never traverse arbitrary expressions. */
	public static function value(expression_node $syntax): string
	{
		if ($syntax instanceof static_member_reference_node) {
			object_cast($syntax, static_member_reference_node::class)->require_type_owner();
			return self::reference(object_cast($syntax->require_preparation(), prepared_constant_reference::class));
		}
		if ($syntax instanceof integer_literal_node) {
			return object_cast($syntax, integer_literal_node::class)->require_integer_literal_preparation()->decimal;
		}
		if ($syntax instanceof constant_reference_node) {
			return self::reference(object_cast($syntax, constant_reference_node::class)->require_constant_reference_preparation());
		}
		if ($syntax instanceof unary_expression_node) {
			return self::unary(object_cast($syntax, unary_expression_node::class));
		}
		throw new \RuntimeException('S2S requires a supported constant integer expression');
	}

	public static function reference(prepared_constant_reference $facts): string
	{
		if (!($facts->value instanceof integer_constant_value)) {
			throw new \RuntimeException('S2S requires a resolved integer constant');
		}
		$integer = object_cast($facts->value, integer_constant_value::class);
		return $integer->decimal;
	}

	/** Unary signs preserve exact decimal identity; arithmetic folding remains deferred. */
	public static function unary(unary_expression_node $syntax): string
	{
		$operation = $syntax->require_unary_preparation()->decision->operation;
		if (($operation !== operator_operation::integer_positive) && ($operation !== operator_operation::integer_negative)) {
			throw new \RuntimeException('S2S requires a supported constant integer expression');
		}
		$value = self::value($syntax->operand);
		if (($operation === operator_operation::integer_positive) || ($value === '0')) { return $value; }
		return self::negate($value);
	}

	public static function negate(string $value): string
	{
		if ($value === '0') { return '0'; }
		if ($value === '-9223372036854775808') {
			throw new \RuntimeException('S2S integer constant negation exceeds signed 64-bit representation');
		}
		if (string_byte_starts_with($value, '-')) { return string_byte_slice($value, 1, string_byte_len($value) - 1); }
		return '-' . $value;
	}
}
