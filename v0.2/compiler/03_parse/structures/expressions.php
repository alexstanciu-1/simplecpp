<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Comparison syntax identities, independent of token IDs; native uint8. */
enum comparison_operator: int
{
	case equal = 1;
	case not_equal = 2;
	case less = 3;
	case less_equal = 4;
	case greater = 5;
	case greater_equal = 6;

	public function precedence(): int /** uint8 */
	{
		return (($this === self::equal) || ($this === self::not_equal)) ? 1 : 2;
	}
}

/** Owns both operands of e.g. value < limit; inherited type readiness records successful generated admission. */
final class ast_binary_expression extends ast_expression
{
	/** Native owning left operand; evaluated and snapshotted first. */
	public readonly ast_expression $left;
	/** Native owning right operand. */
	public readonly ast_expression $right;
	public readonly comparison_operator $operator;

	/** Attach both source operands once; semantic links remain on their original occurrences. */
	public function __construct(ast_expression $left, comparison_operator $operator, ast_expression $right)
	{
		parent::__construct($left->start, $right->end);
		$this->left = $left;
		$this->right = $right;
		$this->operator = $operator;
		$left->attach($this);
		$right->attach($this);
	}

	/** Generated admission supplies the result identity; no constant folding or duplicate prepared tree. */
	public function settle_comparison(type_definition $result): void
	{
		$this->settle_value($result, null);
	}
}
