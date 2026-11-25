<?php

/* Shared non-creating path admission; runtime traversal remains lazy. */
namespace scpp\compiler;

final class Keyed_Path_Preparation
{
	public static function prepare(expression_node $syntax, preparation_context $context, bool $removing): prepared_keyed_path
	{
		if (!($syntax instanceof index_node)) {
			throw new \RuntimeException('S2S unset requires one keyed target');
		}
		$previous = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			$path = new prepared_keyed_path();
			self::step(object_cast($syntax, index_node::class), $path, $context, $removing, true);
			if ($removing) {
				Indexing_Preparation::require_storage_receiver($path->root);
				if (!$path->root->require_preparation()->addressable()) {
					throw new \RuntimeException('S2S unset requires stable storage');
				}
				$root_value = $path->root->require_preparation();
				Reference_Preparation::require_mutable_contents($root_value->place, $root_value->type);
			}
			return $path;
		}
		finally { $context->statement_assignment = $previous; }
	}

	private static function step(index_node $syntax, prepared_keyed_path $path,
		preparation_context $context, bool $removing, bool $leaf): canonical_type_use
	{
		if ($syntax->index === null) {
			throw new \RuntimeException('S2S keyed probes/removal require explicit keys');
		}
		$receiver = self::receiver($syntax->base, $path, $context, $removing);
		$declaration = Model::$indexing_contracts->lookup(Type_Queries::canonical($receiver)->definition());
		if ($declaration === null) {
			throw new \RuntimeException('S2S receiver has no keyed probe/removal contract');
		}
		if (!$removing && !$declaration->supports_probes()) {
			throw new \RuntimeException('S2S receiver does not support keyed probes');
		}
		if ($removing && $leaf && !$declaration->supports_removal()) {
			throw new \RuntimeException('S2S keyed removal for this receiver is deferred (including vector elements)');
		}
		$signature = Indexing_Preparation::signature($receiver, $declaration, $context);
		$element = $signature->value;
		if (!$leaf) {
			$traversal = $signature->path_element;
			if ($traversal === null) {
				throw new \RuntimeException('S2S receiver has no keyed traversal contract');
			}
			$element = $traversal;
		}
		$key_syntax /** expression_node */ = $syntax->index;
		$key = Expression_Preparation::prepare($key_syntax, $context);
		$key_target = $signature->key;
		if (Boxed_Conversions::is_mixed($key->type)) {
			// Preserve unknown key kinds for nonthrowing probes / checked removal.
			$key_target = $key->type;
		}
		$step = new prepared_keyed_step();
		$step->syntax = $syntax;
		$step->element_type = $element;
		$step->key_conversion = Indexing_Preparation::key_conversion($key->type, $key_target, $context);
		$steps /** Storage<prepared_keyed_step> */ = $path->steps;
		$steps->append($step);
		return $element;
	}

	private static function receiver(expression_node $syntax, prepared_keyed_path $path,
		preparation_context $context, bool $removing): canonical_type_use
	{
		if ($syntax instanceof index_node) {
			return self::step(object_cast($syntax, index_node::class), $path, $context, $removing, false);
		}
		$path->root = $syntax;
		$prepared = Expression_Preparation::prepare($syntax, $context);
		if ($syntax instanceof field_access_node) {
			Indexing_Preparation::require_storage_receiver($syntax);
			if (!$prepared->addressable()) {
				throw new \RuntimeException('S2S keyed field probes/removal require stable storage');
			}
		}
		return $prepared->type;
	}
}
