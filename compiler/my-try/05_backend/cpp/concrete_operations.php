<?php
namespace scpp\compiler;

/** Consume already prepared operation decisions; keep concrete processing out of dispatch branches. */
final class CPP_Concrete_Operations
{
	/** Reject uncovered container protocols before publishing invalid native code. */
	private static function require_vector(canonical_type_use $type): void
	{
		if (Type_Queries::canonical($type)->definition() !== Model::$type_catalog->definition('vector')) {
			throw new \RuntimeException('prelower_templates: container operation requires an admitted family adapter');
		}
	}

	/** Adapt the selected runtime contract using its prepared parameter identity. */
	public static function call_reference(prepared_call $facts, cpp_generation_context $context): string
	{
		$target = object_cast(weakref_get($facts->target), runtime_function_definition::class);
		if ($target->key() !== 'core.count.vector') {
			throw new \RuntimeException('prelower_templates: runtime call needs an adapter: ' . $target->key());
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$mapping = CPP_Types::representation($parameters[0]->type, $context);
		CPP_Types::record_dependencies($mapping, $context);
		$engine /** CPP_Specializations */ = $context->specialization_engine;
		return 'scpp::internal::count_' . $engine->entry($engine->request($parameters[0]->type))->name;
	}

	/** Construct the admitted family through ordered prepared entry conversions. */
	public static function initialize(data_construction_node $node, canonical_type_use $type, cpp_generation_context $context): string
	{
		self::require_vector($type);
		$name = CPP_Declarations::type($type, $context);
		$local = 'concrete_init_' . $context->next_temporary++;
		$text = '([&]() -> ' . $name . ' { ' . $name . ' ' . $local . '; ';
		$emitter = new CPP_Syntax($context);
		$entries /** Storage<data_entry_node> */ = $node->entries;
		foreach ($entries as $entry) {
			if ($entry->key !== null) {
				throw new \RuntimeException('prelower_templates: keyed construction needs a family recipe');
			}
			$value = CPP_Declarations::conversion($emitter->expression($entry->value), $entry->require_preparation()->value_conversion, $context);
			$text .= $local . '.append(' . $value . '); ';
		}
		return $text . 'return ' . $local . '; }())';
	}

	/** Evaluate the receiver and key once before copying a checked element. */
	public static function read(index_node $node, cpp_generation_context $context): string
	{
		self::require_vector($node->base->require_preparation()->type);
		$decision = $node->require_index_preparation()->decision;
		$emitter = new CPP_Syntax($context);
		$base = CPP_Declarations::bind_storage_receiver($node->base, $context);
		$key = CPP_Declarations::conversion($emitter->expression($node->index), $decision->operands[1], $context);
		$id = $context->next_temporary++;
		return '([&]() -> ' . CPP_Declarations::type($decision->result_type, $context) . ' { '
			. $base->statements . 'const auto& concrete_read_' . $id . ' = ' . $base->value . '; '
			. 'auto concrete_key_' . $id . ' = ' . $key . '; return concrete_read_' . $id . '.at(concrete_key_' . $id . '); }())';
	}

	/** Snapshot the key and RHS before committing to the live root container. */
	public static function write(assignment_expression_node $node, prepared_index_assignment $facts,
		cpp_generation_context $context, bool $value_required): string
	{
		self::require_vector($facts->target->root->require_preparation()->type);
		if (!$facts->target->path->is_empty()) {
			throw new \RuntimeException('prelower_templates: nested indexed writes need a concrete path adapter');
		}
		$target = object_cast($node->target, index_node::class);
		$emitter = new CPP_Syntax($context);
		$base = CPP_Declarations::bind_storage_receiver($facts->target->root, $context);
		$id = $context->next_temporary++;
		$receiver = 'concrete_write_' . $id;
		$value = 'concrete_value_' . $id;
		$type = CPP_Declarations::type($facts->destination_type(), $context);
		$text = '{ ' . $base->statements . 'auto& ' . $receiver . ' = ' . $base->value . '; ';
		$key = 'concrete_key_' . $id;
		if ($target->index !== null) {
			$text .= 'auto ' . $key . ' = ' . CPP_Declarations::conversion($emitter->expression($target->index), $facts->target->decision->operands[1], $context) . '; ';
		}
		$text .= $type . ' ' . $value . ' = ' . CPP_Declarations::conversion($emitter->expression($node->value), $facts->value_conversion, $context) . '; ';
		$text .= $target->index === null ? $receiver . '.append(' . $value . '); ' : $receiver . '.at(' . $key . ') = ' . $value . '; ';
		return $value_required ? '([&]() -> ' . $type . ' ' . $text . 'return ' . $value . '; }())' : $text . '}';
	}

	/** Borrow the evaluated source and copy each prepared value binding. */
	public static function iterate(foreach_node $node, cpp_generation_context $context): string
	{
		$emitter = new CPP_Syntax($context);
		self::require_vector($node->iterable->require_preparation()->type);
		$facts = $node->require_preparation()->iteration;
		$id = $context->next_temporary++;
		$source = 'concrete_range_' . $id;
		$index = 'concrete_position_' . $id;
		$key_type = CPP_Declarations::type($facts->key_type, $context);
		$text = '{ const auto& ' . $source . ' = ' . $emitter->expression($node->iterable) . '; '
			. 'for (size_t ' . $index . ' = 0; ' . $index . ' < ' . $source . '.size(); ++' . $index . ') { ';
		if ($facts->key !== null) {
			$text .= $emitter->iteration_binding($facts->key, $key_type . '(' . $index . ')');
		}
		$text .= $emitter->iteration_binding($facts->value, $source . '.at(' . $key_type . '(' . $index . '))');
		return $text . $emitter->loop_body($node) . '} }';
	}

	public static function unary(operator_decision $decision, string $spelling, string $operand, cpp_generation_context $context): string
	{
		return CPP_Declarations::type($decision->result_type, $context) . '(' . $spelling . '(' . $operand . ').native_value())';
	}

	/** Compute on ordered integer snapshots using the already selected operation. */
	public static function binary(operator_decision $decision, string $spelling, string $left, string $right, cpp_generation_context $context): string
	{
		$left_type = Type_Queries::canonical($decision->operands[0]->target_type);
		$right_type = Type_Queries::canonical($decision->operands[1]->target_type);
		if (($left_type->family() !== type_family::integer) || ($right_type->family() !== type_family::integer)) {
			throw new \RuntimeException('prelower_templates: binary operation needs a concrete family adapter');
		}
		$result = CPP_Declarations::type($decision->result_type, $context);
		$l = $left . '.native_value()'; $r = $right . '.native_value()';
		if ($decision->operation === operator_operation::integer_three_way) {
			return $result . '(' . $l . ' < ' . $r . ' ? -1 : (' . $l . ' > ' . $r . ' ? 1 : 0))';
		}
		if ($decision->operation === operator_operation::integer_identical) {
			$spelling = '==';
		}
		if ($decision->operation === operator_operation::integer_not_identical) {
			$spelling = '!=';
		}
		if (($spelling === '/') || ($spelling === '%')) {
			$context->headers['specializations/base.hpp'] = true;
			return '(' . $r . ' == 0 ? (scpp::internal::zero_divisor(' . ($spelling === '%' ? 'true' : 'false') . '), ' . $result . '{}) : ' . $result . '(' . $l . ' ' . $spelling . ' ' . $r . '))';
		}
		if ($spelling === 'scpp::pow') {
			throw new \RuntimeException('prelower_templates: checked arithmetic adapter is not available for ' . $spelling);
		}
		return $result . '(' . $l . ' ' . $spelling . ' ' . $r . ')';
	}

	/** Range-check explicit integer casts using registered width and signedness. */
	public static function cast(string $expression, conversion_decision $decision, cpp_generation_context $context): string
	{
		$source = Type_Queries::canonical($decision->source_type)->definition();
		$target = Type_Queries::canonical($decision->target_type)->definition();
		if (!($source instanceof integer_type_definition) || !($target instanceof integer_type_definition)) {
			throw new \RuntimeException('prelower_templates: noninteger checked casts need a concrete adapter');
		}
		$from = object_cast($source, integer_type_definition::class);
		$to = object_cast($target, integer_type_definition::class);
		$type = CPP_Declarations::type($decision->target_type, $context);
		$safe = $from->signed() === $to->signed()
			? $to->bit_width() >= $from->bit_width()
			: (!$from->signed() && ($to->bit_width() > $from->bit_width()));
		if ($safe) {
			return $type . '((' . $expression . ').native_value())';
		}
		$value = 'cast_value_' . $context->next_temporary++;
		$maximum = ($to->signed() ? 'INT' : 'UINT') . $to->bit_width() . '_MAX';
		$invalid = 'static_cast<uint64_t>(' . $value . ') > static_cast<uint64_t>(' . $maximum . ')';
		if ($from->signed()) {
			$invalid = $to->signed()
			? $value . ' < INT' . $to->bit_width() . '_MIN || ' . $value . ' > ' . $maximum
			: $value . ' < 0 || ' . $invalid;
		}
		$context->headers['specializations/base.hpp'] = true;
		$native = $from->signed() ? 'int64_t' : 'uint64_t';
		return '([&]() -> ' . $type . ' { auto ' . $value . ' = (' . $expression . ').native_value(); '
			. 'if (' . $invalid . ') scpp::internal::integer_cast_range(static_cast<' . $native . '>(' . $value . ')); '
			. 'return ' . $type . '(' . $value . '); }())';
	}
}
