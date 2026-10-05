<?php

/* Ordered construction preparation consumes interface bindings, never container names. */
namespace scpp\compiler;

final class Data_Construction_Preparation
{
	public static function prepare(data_construction_node $node, preparation_context $context,
		?canonical_type_use $expected_type): prepared_data_construction
	{
		$target = $expected_type;
		if ($node->target_type !== null) { $target = Type_Preparation::type($node->target_type, $context); }
		if ($target === null) { throw new \RuntimeException('S2S data construction requires a known concrete target'); }
		$shape = self::shape($node, $context->collection->token_snapshot());
		$binding = Initialization_Preparation::binding($target, $shape, $context);
		$facts = new prepared_data_construction();
		$facts->binding = $binding;
		$facts->type = $binding->target();
		$entries /** Storage<data_entry_node> */ = $node->entries;
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			foreach ($entries as $entry) {
				self::prepare_entry($entry, $binding, $context);
			}
		}
		finally { $context->statement_assignment = $statement_assignment; }
		return $facts;
	}

	/** Empty brackets are the only case needing the original delimiter. */
	private static function shape(data_construction_node $node, token_list $tokens): initialization_shape
	{
		$entries /** Storage<data_entry_node> */ = $node->entries;
		if ($entries->is_empty()) {
			return $node->source_representation($tokens) === data_source_representation::php_like
				? initialization_shape::empty_brackets : initialization_shape::keyed;
		}
		$keyed = $entries[0]->key !== null;
		foreach ($entries as $entry) {
			if (($entry->key !== null) !== $keyed) {
				throw new \RuntimeException('S2S mixed positional/keyed initialization is deferred');
			}
		}
		return $keyed ? initialization_shape::keyed : initialization_shape::positional;
	}

	private static function prepare_entry(data_entry_node $entry, initialization_binding $binding,
		preparation_context $context): void
	{
		$facts = new prepared_data_entry();
		if ($entry->key !== null) {
			$key_type = $binding->key();
			if ($key_type === null) { throw new \LogicException('Keyed syntax requires a keyed binding'); }
			$key = Expression_Preparation::prepare($entry->key, $context, $key_type);
			$facts->key_conversion = Conversion_Preparation::decide($key->type, $key_type, conversion_context::initializer_key);
		}
		$value = Expression_Preparation::prepare($entry->value, $context, $binding->nested_target());
		$facts->value_conversion = Conversion_Preparation::decide($value->type, $binding->value(), conversion_context::initializer_value);
		$entry->set_preparation($facts);
	}
}
