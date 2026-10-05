<?php

/* One read candidate path; no syntax rewriting or backend type spelling. */
namespace scpp\compiler;

final class Index_Read_Preparation
{
	/** Registered signatures select key/result types and runtime failure policy. */
	public static function decide(array $operands /** vector<canonical_type_use> */,
		preparation_context $context): operator_decision
	{
		$arity = q_count($operands) - 1;
		if (($arity < 0) || ($arity > 1)) {
			throw new \LogicException('operator[] requires a receiver and zero or one explicit argument');
		}
		$receiver = $operands[0];
		if (String_Conversions::is_string($receiver)) {
			throw new \RuntimeException('S2S string operator[] with ' . $arity . ' argument(s) is deferred; no string overload is defined');
		}
		$type = Type_Queries::canonical($receiver);
		$declaration = Model::$index_reads->lookup($type->definition());
		if (($arity !== 1) || ($declaration === null)) {
			throw new \RuntimeException('S2S operator[] with ' . $arity . ' argument(s) overload resolution is deferred');
		}
		if ($receiver->by_value()) {
			throw new \RuntimeException('S2S indexed reads with effective storage modifiers are deferred');
		}
		$arguments /** vector<canonical_type_use> */ = [];
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			$arguments = $application->arguments();
		}
		$key = Contract_Type_Substitution::resolve($declaration->key(), $receiver, $arguments);
		$value = Contract_Type_Substitution::resolve($declaration->value(), $receiver, $arguments);
		self::require_key_domain($key, $context);
		self::require_key_domain($operands[1], $context);
		Capability_Preparation::require_copy($value, $context);
		$decision = new operator_decision();
		$decision->source_operator = operator_kind::index;
		$decision->operation = $declaration->operation();
		$decision->result_type = $value;
		$decision->operands[] = Conversion_Preparation::decide($receiver, $receiver, conversion_context::argument);
		$decision->operands[] = Conversion_Preparation::decide($operands[1], $key, conversion_context::argument);
		return $decision;
	}

	/** No PHP bool/float/null key coercion; unknown mixed payloads remain runtime checked. */
	private static function require_key_domain(canonical_type_use $type, preparation_context $context): void
	{
		if ($type->matches($context->integer) || $type->matches($context->string_type)
			|| Mixed_Conversions::is_mixed($type)) {
			return;
		}
		throw new \RuntimeException('S2S indexed read keys require int, string or mixed');
	}
}
