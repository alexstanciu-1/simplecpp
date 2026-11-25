<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One owned match arm, e.g. Kind::Word => classify(); an empty label list denotes default. */
final class ast_match_arm extends ast_node
{
	/** @var ast_expression[] Labels in written order. */
	public readonly array $labels /** list<ast_expression> */;
	public readonly ast_expression $result;

	/** @param list<ast_expression> $labels */
	public function __construct(array $labels /** list<ast_expression> */, ast_expression $result,
		int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->labels = $labels;
		$this->result = $result;
		foreach ($labels as $label) $label->attach($this);
		$result->attach($this);
	}
}

/** A selection returns a value. Its result type is shared; branch values stay on the owned operands. */
abstract class ast_selection_expression extends ast_expression
{
	public function settle_result(type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}

/** A conditional expression with two alternative results. */
final class ast_ternary_expression extends ast_selection_expression
{
	/** Between xor and coalescing in the common expression grammar. */
	public const int PRECEDENCE = 1;

	public function __construct(public readonly ast_expression $condition, public readonly ast_expression $when_true,
		public readonly ast_expression $when_false, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$condition->attach($this);
		$when_true->attach($this);
		$when_false->attach($this);
	}
}

/** One lazy selection; the syntax inventory owns arms, with no retained label index or prepared tree. */
final class ast_match_expression extends ast_selection_expression
{
	public readonly ast_expression $subject;
	/** @var ast_match_arm[] Owning arms in written order, including erroneous duplicate labels/defaults. */
	public readonly array $arms /** list<ast_match_arm> */;

	/** Prepared fact: an operand suspends in the enclosing coroutine. */
	public bool $suspends = false;

	/** @param ast_match_arm[] $arms */
	public function __construct(ast_expression $subject, array $arms /** list<ast_match_arm> */, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->subject = $subject;
		$this->arms = $arms;
		$subject->attach($this);
		foreach ($arms as $arm) $arm->attach($this);
	}

	/** Literal true selects ordered conditions; do not infer this mode from mutable value facts. */
	public static function is_condition_subject(ast_expression $subject): bool
	{
		return $subject instanceof ast_keyword_expression && $subject->word === 'true';
	}
}
