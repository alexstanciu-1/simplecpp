<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Binary syntax identities, independent of token IDs; native uint8. */
enum binary_operator: int
{
	case equal = 1;
	case not_equal = 2;
	case less = 3;
	case less_equal = 4;
	case greater = 5;
	case greater_equal = 6;
	case add = 7;
	case subtract = 8;
	case multiply = 9;
	case divide = 10;
	case remainder = 11;
	case logical_and = 12;
	case logical_or = 13;

	public function lazy(): bool
	{
		return ($this === self::logical_and) || ($this === self::logical_or);
	}

	/** Higher levels bind more tightly; binary levels associate left. */
	public function precedence(): int /** uint8 */
	{
		return match ($this) {
			self::logical_or => 1,
			self::logical_and => 2,
			self::equal, self::not_equal => 3,
			self::less, self::less_equal, self::greater, self::greater_equal => 4,
			self::add, self::subtract => 5,
			self::multiply, self::divide, self::remainder => 6,
		};
	}
}

/** Owns operands of e.g. value < limit or ready && valid; inherited type readiness records successful admission. */
final class ast_binary_expression extends ast_expression
{
	/** Native owning left operand; lazy operators test its truthiness before deciding whether to evaluate the RHS. */
	public readonly ast_expression $left;
	/** Native owning right operand. */
	public readonly ast_expression $right;
	public readonly binary_operator $operator;

	/** Attach both source operands once; semantic links remain on their original occurrences. */
	public function __construct(ast_expression $left, binary_operator $operator, ast_expression $right)
	{
		parent::__construct($left->start, $right->end);
		$this->left = $left;
		$this->right = $right;
		$this->operator = $operator;
		$left->attach($this);
		$right->attach($this);
	}

	/** Settles the approved result identity; no constant folding or duplicate prepared tree. */
	public function settle_binary(type_definition $result): void
	{
		$this->settle_value($result, null);
	}
}

/** Logical negation, e.g. !ready(); owns one operand and retains only the inherited bool result fact. */
final class ast_not_expression extends ast_expression
{
	/** Native owning operand; interpreted through its generated condition handler. */
	public readonly ast_expression $operand;

	/** $start: position of !; $end: exclusive token position including operand grouping. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $operand)
	{
		parent::__construct($start, $end);
		$this->operand = $operand;
		$operand->attach($this);
	}

	public function settle_not(): void
	{
		$this->settle_value(type_bool::instance(), null);
	}
}
