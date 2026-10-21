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
		Type_Lifecycle_Preparation::require_storage($target, $context);
		$payload = Type_Queries::record_payload($target);
		if (Type_Queries::source_record($payload) !== null) {
			$saved_assignment = $context->statement_assignment;
			$context->statement_assignment = false;
			try {
				return Record_Construction_Preparation::prepare($node, $target, $context);
			}
			finally {
				$context->statement_assignment = $saved_assignment;
			}
		}
		if ($target->by_value()) {
			throw new \RuntimeException('S2S data construction with effective storage modifiers is deferred');
		}
		$shape = self::shape($node, $context->collection->token_snapshot());
		$binding = Initialization_Preparation::binding($target, $shape, $context);
		$facts = new prepared_sequence_construction();
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
			$facts->key_conversion = Conversion_Preparation::decide($key->type, $key_type, conversion_context::initializer_key,
				new Conversion_Relationships($context));
		}
		$value = Expression_Preparation::prepare($entry->value, $context, $binding->nested_target());
		$facts->value_conversion = Conversion_Preparation::decide($value->type, $binding->value(), conversion_context::container_element,
			new Conversion_Relationships($context));
		$entry->set_preparation($facts);
	}
}

/** Bind literal names to declaration-owned inputs; do not infer a record from its keys. */
final class Record_Construction_Preparation
{
	/** Resolve explicit keyed inputs and prove every omitted input can initialize its property. */
	public static function prepare(data_construction_node $node, canonical_type_use $target,
		preparation_context $context): prepared_record_construction
	{
		$payload = Type_Queries::record_payload($target);
		$record /** collected_record */ = Type_Queries::source_record($payload);
		Type_Lifecycle_Preparation::require_construction($target, $context);
		if ($context->worker->require_relationships($record, $context)->abstract_type) {
			throw new \RuntimeException('Cannot construct an abstract type');
		}
		$context->worker->require_declaration($context->owner, $record);
		if (!$record->syntax()->require_preparation()->data_initializable) {
			throw new \RuntimeException('S2S record data construction requires explicit data opt-in');
		}
		$contract = Constructor_Preparation::inputs($record, $context);
		$storage = Type_Queries::record_storage($target);
		if ($storage === record_storage_kind::weak) {
			throw new \RuntimeException('S2S weak handles cannot allocate');
		}
		$fields /** vector<record_initialization_input> */ = $contract->inputs_in_order();
		$positions /** hash<int> */ = [];
		foreach ($fields as $position => $input) {
			$positions[$input->name] = $position;
		}
		$seen /** hash<bool> */ = [];
		$entries /** Storage<data_entry_node> */ = $node->entries;
		foreach ($entries as $entry) {
			if (!($entry->key instanceof string_literal_node)) {
				throw new \RuntimeException('S2S record construction requires literal string keys');
			}
			$key = object_cast(Expression_Preparation::prepare($entry->key, $context), prepared_string_literal::class)->value;
			if (!isset($positions[$key])) {
				throw new \RuntimeException('S2S unknown record construction input: ' . $key);
			}
			if (isset($seen[$key])) {
				throw new \RuntimeException('S2S duplicate record construction input: ' . $key);
			}
			$seen[$key] = true;
			$field = $fields[$positions[$key]]->parameter;
			$value = Expression_Preparation::prepare_for_consumer($entry->value, $context, $field->type, expression_consumer::value);
			$entry_facts = new prepared_data_entry();
			$entry_facts->input_position = $positions[$key];
			$entry_facts->value_conversion = Conversion_Preparation::decide($value->type, $field->type, conversion_context::argument,
				new Conversion_Relationships($context));
			// Both admitted forms materialize a private input value; lowering then moves that
			// temporary, never the original source storage, into the generated constructor.
			Value_Boundary_Preparation::decide_value($field->type, $entry_facts->value_conversion, $context, $value);
			$entry->set_preparation($entry_facts);
		}
		foreach ($positions as $name => $position) {
			if (!isset($seen[$name])) {
				$field = $fields[$position]->parameter;
				if ($field->default_value === null) {
						throw new \RuntimeException('S2S missing required record construction input: ' . $name);
				}
			}
		}
		$facts = new prepared_record_construction();
		$facts->type = $target;
		$facts->payload_type = $payload;
		$facts->storage = $storage;
		$facts->contract = $contract;
		$facts->value_origin = expression_value_origin::fresh_construction;
		return $facts;
	}
}
