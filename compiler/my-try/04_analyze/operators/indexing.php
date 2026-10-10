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
		Capability_Preparation::require_copy($signature->value, $context);
		self::require_key_domain($operands[1], $context);
		$decision = self::decision($receiver, $signature, $declaration->operation());
		$decision->operands[] = Conversion_Preparation::decide($operands[1], $signature->key, conversion_context::argument);
		return $decision;
	}

	/** Bind the registered key/value/context types once for reads, writes and appends. */
	public static function signature(canonical_type_use $receiver, indexing_declaration $declaration,
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
					$unsupported_key /** expression_node */ = $target->index;
					$operands[] = Expression_Preparation::prepare($unsupported_key, $context)->type;
				}
				self::decide($operands, $context);
				throw new \LogicException('Unregistered indexed assignment');
			}
			$signature = self::signature($receiver, $declaration, $context);
			Capability_Preparation::require_copy($signature->value, $context);
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
			$facts->value_conversion = Conversion_Preparation::decide($value->type, $signature->value, conversion_context::container_element,
				new Conversion_Relationships($context));
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
		$root = $syntax;
		$prepared = Expression_Preparation::prepare($root, $context);
		self::require_storage_receiver($root);
		if (!$prepared->addressable()) {
			throw new \RuntimeException('S2S indexed assignment requires stable receiver storage');
		}
		Reference_Preparation::require_mutable_contents($prepared->place, $prepared->type);
		$facts->root = $root;
		return $prepared->type;
	}

	/** Select real storage, not a copied indexed read or a temporary receiver. */
	public static function require_storage_receiver(expression_node $syntax): void
	{
		if ($syntax instanceof variable_reference_node) { return; }
		if ($syntax instanceof field_access_node) {
			$field = object_cast($syntax, field_access_node::class);
			self::require_storage_receiver($field->base);
			$facts = $field->require_field_access_preparation();
			if (($facts->receiver_storage !== record_storage_kind::shared)
				&& ($facts->receiver_storage !== record_storage_kind::inline_value)) {
				throw new \RuntimeException('Indexed field storage requires shared or inline receiver lifetime');
			}
			$base = $field->base->require_preparation();
			if ($base->type->by_value()) {
				// Class $this is a borrowed instance, not authored value<T> storage.
				if ($base->place === null) {
					throw new \RuntimeException('Indexed field storage through ownership-modified storage is deferred');
				}
				$place /** prepared_place */ = $base->place;
				if (!($place->storage() instanceof prepared_receiver)) {
					throw new \RuntimeException('Indexed field storage through ownership-modified storage is deferred');
				}
			}
			return;
		}
		throw new \RuntimeException('S2S indexed storage requires local, parameter or field storage');
	}

	/** No PHP bool/float/null key coercion; unknown mixed payloads remain runtime checked. */
	public static function require_key_domain(canonical_type_use $type, preparation_context $context): void
	{
		if ($type->matches($context->integer) || $type->matches($context->string_type)
			|| Boxed_Conversions::is_mixed($type)) {
			return;
		}
		throw new \RuntimeException('S2S indexed access keys require int, string or mixed');
	}
}
