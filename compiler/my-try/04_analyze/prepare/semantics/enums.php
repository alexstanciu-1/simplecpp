<?php

/* Resolve enum backing and exact case values before any executable consumer or backend. */
namespace scpp\compiler;

final class Enum_Preparation
{
	/** Name membership and backing dependencies use the ordinary declaration scheduler. */
	public static function declaration(enum_node $syntax, preparation_context $context): prepared_enum
	{
		$entry = $syntax->occurrence();
		$declaring_scope = object_cast(weakref_get($entry->scope), scope::class);
		$candidates = Observed_Lookup::types($declaring_scope, $entry->name, $context);
		if (q_count($candidates) !== 1) {
			throw new \RuntimeException('S2S duplicate enum/type name: ' . $entry->name);
		}
		$definition = object_cast($candidates[0], enum_type_definition::class);
		$registry = Model::$type_catalog->registry();
		$facts = new prepared_enum();
		$facts->type = $registry->use($registry->canonical($definition)->type_id());
		$backing /** nullable<integer_type_definition> */ = null;
		if ($syntax->backing_type !== null) {
			$backing_syntax /** type_node */ = $syntax->backing_type;
			$type = Type_Preparation::type($backing_syntax, $context);
			$facts->backing_type = $type;
			$backing_definition = Type_Queries::canonical($type)->definition();
			if (!($backing_definition instanceof integer_type_definition)) {
				throw new \RuntimeException('S2S enum backing requires an integer type');
			}
			$backing = object_cast($backing_definition, integer_type_definition::class);
		}
		$cases /** Keyed_Storage<prepared_enum_case> */ = $facts->cases;
		$nodes /** Storage<enum_case_node> */ = $syntax->cases;
		$seen_values /** hash<bool> */ = [];
		$negative = false;
		$ordinal = 0;
		foreach ($nodes as $node) {
			if (isset($cases[$node->name])) {
				throw new \RuntimeException('S2S duplicate enum case name: ' . $node->name);
			}
			$decimal = (string)$ordinal;
			if ($backing === null) {
				if ($node->value !== null) {
					throw new \RuntimeException('S2S unit enum cases cannot have values');
				}
			}
			else {
				if ($node->value === null) {
					throw new \RuntimeException('S2S backed enum cases require integer literals');
				}
				$decimal = self::literal($node->value, $context->collection->token_snapshot());
				$integer_backing /** integer_type_definition */ = $backing;
				if (!Integer_Literals::fits($decimal, $integer_backing->bit_width(), $integer_backing->signed())) {
					throw new \RuntimeException('S2S enum case exceeds backing range: ' . $node->name);
				}
			}
			$value_key = 'value:' . $decimal;
			if (isset($seen_values[$value_key])) {
				throw new \RuntimeException('S2S duplicate enum case value: ' . $decimal);
			}
			$seen_values[$value_key] = true;
			if (string_byte_starts_with($decimal, '-')) {
				$negative = true;
			}
			$cases->add($node->name, new prepared_enum_case($node->name, $decimal));
			$ordinal++;
		}
		if ($cases->is_empty()) {
			throw new \RuntimeException('S2S enums require at least one case');
		}
		if ($backing !== null) {
			if ($backing !== Model::$type_catalog->definition('int')) {
				$fixed_storage /** integer_type_definition */ = $backing;
				$facts->storage = $fixed_storage;
				return $facts;
			}
		}
		$facts->storage = self::compact_storage($cases, $negative);
		return $facts;
	}

	/** Only literal syntax (with one optional sign) is accepted; no constant-expression expansion. */
	private static function literal(expression_node $syntax, token_list $tokens): string
	{
		$operand = $syntax;
		$negative = false;
		if ($syntax instanceof unary_expression_node) {
			$unary = object_cast($syntax, unary_expression_node::class);
			$sign = $tokens->text_at($unary->operator_token_index);
			if (($sign !== '+') && ($sign !== '-')) {
				throw new \RuntimeException('S2S enum case requires an integer literal with optional sign');
			}
			$negative = $sign === '-';
			$operand = $unary->operand;
		}
		if (!($operand instanceof integer_literal_node)) {
			throw new \RuntimeException('S2S enum case requires an integer literal with optional sign');
		}
		$magnitude = Integer_Literals::magnitude($tokens->text_at($operand->start_token()));
		return ($negative && ($magnitude !== '0')) ? '-' . $magnitude : $magnitude;
	}

	/** Select semantic integer storage; backend-native spelling remains outside preparation. */
	private static function compact_storage(Keyed_Storage $cases /** Keyed_Storage<prepared_enum_case> */,
		bool $signed): integer_type_definition
	{
		$widths /** vector<int> */ = [8, 16, 32, 64];
		foreach ($widths as $width) {
			$fits = true;
			foreach ($cases as $case_value) {
				if (!Integer_Literals::fits($case_value->decimal, $width, $signed)) {
					$fits = false;
					break;
				}
			}
			if ($fits) {
				$name = ($signed ? 'int' : 'uint') . (string)$width;
				return object_cast(Model::$type_catalog->definition($name), integer_type_definition::class);
			}
		}
		throw new \RuntimeException('S2S enum cases exceed supported storage');
	}

	/** Type preparation already observes name lookup and requires the source declaration. */
	public static function reference(static_member_reference_node $syntax, preparation_context $context): prepared_enum_case_reference
	{
		$type = Type_Preparation::type($syntax->owner_type, $context);
		$entry = Type_Queries::source_declaration($type);
		if (!($entry instanceof collected_enum)) {
			throw new \RuntimeException('S2S named static member requires an enum owner');
		}
		$enum_entry = object_cast($entry, collected_enum::class);
		$cases /** Keyed_Storage<prepared_enum_case> */ = $enum_entry->enum_syntax()->require_preparation()->cases;
		if (!isset($cases[$syntax->member_name])) {
			throw new \RuntimeException('S2S unknown enum case: ' . $syntax->member_name);
		}
		$facts = new prepared_enum_case_reference();
		$facts->type = $type;
		$facts->selected_case = $cases[$syntax->member_name];
		return $facts;
	}

	/** Effective changes invalidate consumers; equal edits preserve the prior immutable snapshot. */
	public static function same(?prepared_enum $old, prepared_enum $current): bool
	{
		if ($old === null) {
			return false;
		}
		if ($old->storage !== $current->storage) {
			return false;
		}
		if (($old->backing_type === null) !== ($current->backing_type === null)) {
			return false;
		}
		if ($old->backing_type !== null) {
			$old_backing /** canonical_type_use */ = $old->backing_type;
			$current_backing /** canonical_type_use */ = $current->backing_type;
			if (!$old_backing->matches($current_backing)) {
				return false;
			}
		}
		$before /** Keyed_Storage<prepared_enum_case> */ = $old->cases;
		$after /** Keyed_Storage<prepared_enum_case> */ = $current->cases;
		if ($before->count() !== $after->count()) {
			return false;
		}
		$old_names /** vector<string> */ = [];
		foreach ($before as $name => $case_value) {
			$old_names[] = $name;
		}
		$position = 0;
		foreach ($after as $name => $case_value) {
			if ($old_names[$position] !== $name) {
				return false;
			}
			$position++;
			if (!isset($before[$name])) {
				return false;
			}
			if ($before[$name]->decimal !== $case_value->decimal) {
				return false;
			}
		}
		return true;
	}
}
