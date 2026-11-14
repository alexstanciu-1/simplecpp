<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Empty [] initializer; its explicitly typed local supplies the concrete collection identity during preparation. */
final class ast_empty_collection_expression extends ast_expression
{
	public function settle_collection(type_definition&indexed_collection_type $type): void
	{
		$this->settle_value($type, null);
	}
}

/** Value-copy indexed read, e.g. values[0]; owns its receiver and index, never represents mutable element storage. */
final class ast_index_expression extends ast_expression
{
	/** Native owning receiver expression. */
	public readonly ast_expression $receiver;
	/** Native owning index expression. */
	public readonly ast_expression $index;

	/** $end: exclusive token position after ]. */
	public function __construct(ast_expression $receiver, ast_expression $index, int $end /** uint32 */)
	{
		parent::__construct($receiver->start, $end);
		$this->receiver = $receiver;
		$this->index = $index;
		$receiver->attach($this);
		$index->attach($this);
	}

	public function settle_element(type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}

/** Append statement, e.g. values[] = 8; owns operands and caches the approved element conversion. */
final class ast_append_statement extends ast_statement
{
	/** PROPERTIES */
	/** Native owning receiver; preparation requires writable local or instance-field storage. */
	public readonly ast_name_expression|ast_field_expression $receiver;
	/** Native owning appended value. */
	public readonly ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: checked element conversion; null until preparation succeeds or after reset. */
	protected ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $end: exclusive token position after the semicolon. */
	public function __construct(ast_name_expression|ast_field_expression $receiver, ast_expression $value, int $end /** uint32 */)
	{
		parent::__construct($receiver->start, $end);
		$this->receiver = $receiver;
		$this->value = $value;
		$receiver->attach($this);
		$value->attach($this);
	}

	/** METHODS */
	public function settle_append(int $conversion /** uint16 */): void
	{
		$this->conversion = $conversion;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Append is not prepared');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
	}
}

/** Keyed store, e.g. values["count"] = 8; owns syntax and retains the approved value conversion. */
final class ast_index_assignment extends ast_statement
{
	/** PROPERTIES */
	/** Native owning writable storage expression. */
	public readonly ast_name_expression|ast_field_expression $receiver;
	/** Native owning key expression. The receiver binding determines store argument ordering. */
	public readonly ast_expression $index;
	/** Native owning assigned expression. */
	public readonly ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: value conversion; null before successful preparation or after reset. */
	protected ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $end: exclusive token position after the semicolon. */
	public function __construct(ast_name_expression|ast_field_expression $receiver, ast_expression $index, ast_expression $value, int $end /** uint32 */)
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
	public function settle_write(int $conversion /** uint16 */): void
	{
		$this->conversion = $conversion;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Keyed write is not prepared');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
	}
}

/** Indexed compound, e.g. factory()->items[key()] += rhs(); reuses compound facts on one indexed target. */
final class ast_index_compound extends ast_assignment_statement
{
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
