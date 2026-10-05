<?php

/* Shared bracket signature binding and operation-specific admission; no backend spelling. */
namespace scpp\compiler;

final class Indexing_Preparation
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
		$declaration = Model::$indexing_contracts->lookup($type->definition());
		if (($arity !== 1) || ($declaration === null)) {
			throw new \RuntimeException('S2S operator[] with ' . $arity . ' argument(s) overload resolution is deferred');
		}
		$signature = self::signature($receiver, $declaration, $context);
		self::require_key_domain($operands[1], $context);
		$decision = self::decision($receiver, $signature, $declaration->operation());
		$decision->operands[] = Conversion_Preparation::decide($operands[1], $signature->key, conversion_context::argument);
		return $decision;
	}

	/** Bind the registered key/value/context types once for reads, writes and appends. */
	private static function signature(canonical_type_use $receiver, indexing_declaration $declaration,
		preparation_context $context): indexing_signature
	{
		if ($receiver->by_value()) {
			throw new \RuntimeException('S2S indexed access with effective storage modifiers is deferred');
		}
		$type = Type_Queries::canonical($receiver);
		$arguments /** vector<canonical_type_use> */ = [];
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			$arguments = $application->arguments();
		}
		$signature = new indexing_signature();
		$signature->key = Contract_Type_Substitution::resolve($declaration->key(), $receiver, $arguments);
		$signature->value = Contract_Type_Substitution::resolve($declaration->value(), $receiver, $arguments);
		$signature->nested = Contract_Type_Substitution::resolve($declaration->nested(), $receiver, $arguments);
		$path_reference = $declaration->path_element();
		if ($path_reference !== null) {
			$signature->path_element = Contract_Type_Substitution::resolve($path_reference, $receiver, $arguments);
		}
		self::require_key_domain($signature->key, $context);
		Capability_Preparation::require_copy($signature->value, $context);
		return $signature;
	}

	/** Selected results are values even when the operation modifies a receiver. */
	private static function decision(canonical_type_use $receiver, indexing_signature $signature,
		operator_operation $operation): operator_decision
	{
		$decision = new operator_decision();
		$decision->source_operator = operator_kind::index;
		$decision->operation = $operation;
		$decision->result_type = $signature->value;
		$decision->operands[] = Conversion_Preparation::decide($receiver, $receiver, conversion_context::argument);
		return $decision;
	}

	/** Mutation starts at stable whole-wrapper storage, never a copied read result. */
	public static function prepare_write(assignment_expression_node $assignment, index_node $target,
		preparation_context $context): prepared_index_assignment
	{
		$previous = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$facts = new prepared_index_assignment();
			$receiver = self::prepare_receiver($target->base, $facts, $context);
			$declaration = Model::$indexing_contracts->lookup(Type_Queries::canonical($receiver)->definition());
			if ($declaration === null) {
				// Preserve the common unsupported operator diagnostic, including string arity.
				$operands /** vector<canonical_type_use> */ = [$receiver];
				if ($target->index !== null) {
					$key_node /** expression_node */ = $target->index;
					$operands[] = Expression_Preparation::prepare($key_node, $context)->type;
				}
				self::decide($operands, $context);
				throw new \LogicException('Unregistered indexed assignment');
			}
			$signature = self::signature($receiver, $declaration, $context);
			$operation = operator_operation::index_append;
			if ($target->index === null)
			{
				$policy = $declaration->append_policy();
				if (($policy === index_append_policy::unsupported)
					|| (($policy === index_append_policy::integer_key) && !$signature->key->matches($context->integer))) {
					throw new \RuntimeException('S2S receiver does not support append');
				}
			}
			else
			{
				$write_operation = $declaration->write_operation();
				if ($write_operation === null) {
					throw new \RuntimeException('S2S receiver has no indexed assignment contract');
				}
				$operation = $write_operation;
			}
			$decision = self::decision($receiver, $signature, $operation);
			if ($target->index !== null)
			{
				$key_node /** expression_node */ = $target->index;
				$key = Expression_Preparation::prepare($key_node, $context);
				self::require_key_domain($key->type, $context);
				$decision->operands[] = Conversion_Preparation::decide($key->type, $signature->key, conversion_context::argument);
			}
			$value = Expression_Preparation::prepare($assignment->value, $context, $signature->nested);
			$facts->type = $signature->value;
			$facts->decision = $decision;
			$facts->value_conversion = Conversion_Preparation::decide($value->type, $signature->value, conversion_context::container_element);
			if (!$facts->path->is_empty()) {
				if (Mixed_Conversions::is_mixed($receiver)
					&& ($facts->value_conversion->operation === conversion_operation::mixed_dynamic_box)) {
					throw new \RuntimeException('S2S nested write of dynamic into mixed is deferred');
				}
			}
			return $facts;
		}
		finally {
			$context->statement_assignment = $previous;
		}
	}

	/** Keys are prepared root-to-leaf; traversal does not prepare value-read facts. */
	private static function prepare_receiver(expression_node $syntax, prepared_index_assignment $facts,
		preparation_context $context): canonical_type_use
	{
		if ($syntax instanceof index_node)
		{
			$index = object_cast($syntax, index_node::class);
			if ($index->index === null) {
				throw new \RuntimeException('S2S intermediate append in a write path is deferred');
			}
			$receiver = self::prepare_receiver($index->base, $facts, $context);
			$declaration = Model::$indexing_contracts->lookup(Type_Queries::canonical($receiver)->definition());
			if ($declaration === null) {
				throw new \RuntimeException('S2S receiver has no nested write-path contract');
			}
			$signature = self::signature($receiver, $declaration, $context);
			$element = $signature->path_element;
			if ($element === null) {
				throw new \RuntimeException('S2S receiver has no nested write-path contract');
			}
			$key_node /** expression_node */ = $index->index;
			$key = Expression_Preparation::prepare($key_node, $context);
			self::require_key_domain($key->type, $context);
			$step = new prepared_index_path_step();
			$step->syntax = $index;
			$step->element_type = $element;
			$step->key_conversion = Conversion_Preparation::decide($key->type, $signature->key, conversion_context::argument);
			$path /** Storage<prepared_index_path_step> */ = $facts->path;
			$path->append($step);
			return $step->element_type;
		}
		if (!($syntax instanceof variable_reference_node)) {
			throw new \RuntimeException('S2S indexed assignment requires a direct local or parameter root');
		}
		$root = object_cast($syntax, variable_reference_node::class);
		$prepared = Expression_Preparation::prepare($root, $context);
		if (!$prepared->addressable) {
			throw new \RuntimeException('S2S indexed assignment requires stable receiver storage');
		}
		$facts->root = $root;
		return $prepared->type;
	}

	/** No PHP bool/float/null key coercion; unknown mixed payloads remain runtime checked. */
	private static function require_key_domain(canonical_type_use $type, preparation_context $context): void
	{
		if ($type->matches($context->integer) || $type->matches($context->string_type)
			|| Mixed_Conversions::is_mixed($type)) {
			return;
		}
		throw new \RuntimeException('S2S indexed access keys require int, string or mixed');
	}
}
