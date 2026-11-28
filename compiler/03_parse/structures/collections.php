<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Contextual keyed record invocation is discarded whenever its literal is prepared again. */
trait record_literal_preparation
{
	public ?ast_data_construction $record_call = null;

	public function settle_record(ast_data_construction $call): void
	{
		$this->record_call = $call;
		$this->settle_value($call->type_def(), null);
	}
}

/** Typed [] or [a, b] initializer; owns ordered syntax and uses the destination's collection contract. */
class ast_collection_expression extends ast_expression
{
	use record_literal_preparation;

	/** PROPERTIES */
	/** @var ast_expression[] Native owning elements, in source order. */
	public readonly array $elements /** list<ast_expression> */;

	/** PREP FACTS */
	/** Prepared fact: an operand suspends in the enclosing coroutine. */
	public bool $suspends = false;
	/** @var int[] Prepared fact: approved element conversions; empty before preparation or after reset. */
	private array $conversions /** list<uint16> */ = [];

	/** CONSTRUCTOR */
	/** $start/$end: token boundaries. @param ast_expression[] $elements */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, array $elements /** list<ast_expression> */ = [])
	{
		parent::__construct($start, $end);
		$this->elements = $elements;
		foreach ($elements as $element) $element->attach($this);
	}

	/** METHODS */
	/** @param int[] $conversions */
	public function settle_collection(type_definition&indexed_collection_type $type, array $conversions /** list<uint16> */ = []): void
	{
		$this->conversions = $conversions;
		$this->settle_value($type, null);
	}

	public function element_conversion(int $position /** uint32 */): int /** uint16 */
	{
		return $this->conversions[$position] ?? throw new \LogicException('Collection element is not prepared');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversions = [];
	}
}

/** Parenthesized Python tuple display. The slots share the ordered initializer model. */
final class ast_tuple_expression extends ast_collection_expression
{
}

/** One ordered key/value input occurrence, e.g. key() => value(); owns both expressions. */
final class ast_keyed_entry extends ast_node
{
	public readonly ast_expression $key;
	public readonly ast_expression $value;
	/** Null until value conversion is prepared, or after reset. Keys use the strict identity contract. */
	public ?int $conversion /** uint16 */ = null;

	public function __construct(ast_expression $key, ast_expression $value)
	{
		parent::__construct($key->start, $value->end);
		$this->key = $key;
		$this->value = $value;
		$key->attach($this);
		$value->attach($this);
	}
}

/** Nonempty bracket-keyed input; duplicates remain ordered syntax, never a PHP map. */
final class ast_keyed_collection_expression extends ast_expression
{
	use record_literal_preparation;


	/** @var ast_keyed_entry[] Native owning entries. */
	public readonly array $entries /** list<ast_keyed_entry> */;

	/** Prepared fact: an operand suspends in the enclosing coroutine. */
	public bool $suspends = false;

	/** @param ast_keyed_entry[] $entries */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, array $entries /** list<ast_keyed_entry> */)
	{
		parent::__construct($start, $end);
		$this->entries = $entries;
		foreach ($entries as $entry) $entry->attach($this);
	}

	public function settle_collection((type_definition&keyed_initializer_type)|boxed_type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}

/** Value-copy indexed read, e.g. values[0]; owns its receiver and index, never represents mutable element storage. */
class ast_index_expression extends ast_expression
{
	/** PROPERTIES */
	/** Native owning receiver expression. */
	public readonly ast_expression $receiver;
	/** Native owning index expression. */
	public readonly ast_expression $index;

	/** CONSTRUCTOR */
	/** $end: exclusive token position after ]. */
	public function __construct(ast_expression $receiver, ast_expression $index, int $end /** uint32 */)
	{
		parent::__construct($receiver->start, $end);
		$this->receiver = $receiver;
		$this->index = $index;
		$receiver->attach($this);
		$index->attach($this);
	}

	/** METHODS */
	/** A run of successive receivers ends at its guard; an index inside a key is a separate expression. */
	public function in_nullsafe_index_run(): bool
	{
		$read = $this;
		while (($parent = $read->parent) instanceof ast_index_expression || $parent instanceof ast_field_expression || $parent instanceof ast_call_expression) {
			if ($parent->receiver !== $read) return false;
			$read = $parent;
		}
		return $read->parent instanceof ast_nullsafe_member && $read->parent->operand === $read;
	}

	public function settle_element(type_definition $type): void
	{
		$this->settle_value($type, null);
	}

	/** STATIC METHODS */
	/** A boxed mutation requires a local or stable instance field. */
	public static function boxed_path_root(ast_expression $row): ast_name_expression|ast_field_expression|null
	{
		if ($row->source()->flavor === flavor::python) return null;
		$root = $row;
		while ($root instanceof ast_index_expression || ($root instanceof ast_field_expression && $root->is_dynamic_key())) {
			$root = $root->receiver;
		}
		if ($root === $row) return null;
		if (!($root->type_def() instanceof boxed_type_definition)) return null;
		return self::collection_storage_root($root);
	}

	/** Find local or instance-field storage reached through checked collection slots. */
	public static function nested_collection_root(ast_expression $row): ast_name_expression|ast_field_expression|null
	{
		if (!($row instanceof ast_index_expression)) return null;
		$root = $row;
		do {
			if (!($root->type_def() instanceof nested_index_storage_type)) return null;
			$root = $root->receiver;
		} while ($root instanceof ast_index_expression);
		if (!($root->type_def() instanceof nested_index_storage_type)) return null;
		return self::collection_storage_root($root);
	}

	/** Keep inline paths rooted in local storage or in a retainable receiver. */
	private static function collection_storage_root(ast_expression $root): ast_name_expression|ast_field_expression|null
	{
		$base = $root;
		while ($base instanceof ast_field_expression && $base->shares_receiver_storage()) {
			$base = $base->receiver;
		}
		if ($root instanceof ast_field_expression && ($base instanceof ast_this_expression
			|| $base->member_type() instanceof shared_record_type_definition)) return $root;
		return $base instanceof ast_name_expression && $base->local_binding() !== null ? $root : null;
	}
}

/** PHP $d->{expr} retains the computed-member spelling for read-only admission, then lowers as an indexed read. */
final class ast_computed_member_read extends ast_index_expression
{
}

/** Append statement, e.g. values[] = 8; owns operands and caches the approved element conversion. */
final class ast_append_statement extends ast_statement
{
	/** PROPERTIES */
	/** Native owning receiver; preparation requires a writable local, field or indexed local path. */
	public readonly ast_name_expression|ast_field_expression|ast_index_expression $receiver;
	/** Native owning appended value. */
	public readonly ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: non-owning root of a checked boxed path; null after reset. */
	public ast_name_expression|ast_field_expression|null $boxed_root = null;
	/** Prepared fact: checked element conversion; null until preparation succeeds or after reset. */
	protected ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $end: exclusive token position after the semicolon. */
	public function __construct(ast_name_expression|ast_field_expression|ast_index_expression $receiver, ast_expression $value, int $end /** uint32 */)
	{
		parent::__construct($receiver->start, $end);
		$this->receiver = $receiver;
		$this->value = $value;
		$receiver->attach($this);
		$value->attach($this);
	}

	/** METHODS */
	public function settle_append(int $conversion /** uint16 */, ast_name_expression|ast_field_expression|null $boxed_root): void
	{
		$this->conversion = $conversion;
		$this->boxed_root = $boxed_root;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Append is not prepared');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
		$this->boxed_root = null;
	}
}

/** Keyed store, e.g. values["count"] = 8; owns syntax and retains the approved value conversion. */
final class ast_index_assignment extends ast_statement
{
	/** PROPERTIES */
	/** Native owning writable storage expression. */
	public readonly ast_name_expression|ast_field_expression|ast_index_expression $receiver;
	/** Native owning key expression. The receiver binding determines store argument ordering. */
	public readonly ast_expression $index;
	/** Native owning assigned expression. */
	public readonly ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: non-owning root of a checked boxed path; null after reset. */
	public ast_name_expression|ast_field_expression|null $boxed_root = null;
	/** Prepared fact: value conversion; null before successful preparation or after reset. */
	protected ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $end: exclusive token position after the semicolon. */
	public function __construct(ast_name_expression|ast_field_expression|ast_index_expression $receiver, ast_expression $index, ast_expression $value, int $end /** uint32 */)
	{
		parent::__construct($receiver->start, $end);
		$this->receiver = $receiver;
		$this->index = $index;
		$this->value = $value;
		$receiver->attach($this);
		$index->attach($this);
		$value->attach($this);
	}

	/** METHODS */
	public function settle_write(int $conversion /** uint16 */, ast_name_expression|ast_field_expression|null $boxed_root): void
	{
		$this->conversion = $conversion;
		$this->boxed_root = $boxed_root;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Keyed write is not prepared');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
		$this->boxed_root = null;
	}
}

/** Non-owning prepared root shared by indexed compound and update statements. */
trait prepared_boxed_mutation
{
	/** Prepared fact: storage selected before evaluating the keys. */
	public ast_name_expression|ast_field_expression|null $boxed_root = null;

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->boxed_root = null;
	}
}

/** Indexed compound, e.g. factory()->items[key()] += rhs(); reuses compound facts on one indexed target. */
final class ast_index_compound extends ast_assignment_statement
{
	use prepared_boxed_mutation;

	/** Native owning indexed target; its receiver is any prepared collection expression. */
	public readonly ast_index_expression $target;

	/** $end: exclusive token position after the semicolon. */
	public function __construct(ast_index_expression $target, ast_expression $value, binary_operator $operator, int $end /** uint32 */)
	{
		parent::__construct($target->start, $end, '', $value, assignment_intent::existing_binding, $operator);
		$this->target = $target;
		$target->attach($this);
	}
}

/** Discarded indexed update, e.g. factory()->items[key()]++; shares generated numeric update admission. */
final class ast_index_update extends ast_update_statement
{
	use prepared_boxed_mutation;

	/** Native owning indexed target; receiver and key are evaluated once. */
	public readonly ast_index_expression $target;

	/** $start/$end: token positions including the update operator and statement delimiter. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_index_expression $target,
		update_operator $operator, bool $postfix)
	{
		parent::__construct($start, $end, '', $operator, $postfix);
		$this->target = $target;
		$target->attach($this);
	}
}
