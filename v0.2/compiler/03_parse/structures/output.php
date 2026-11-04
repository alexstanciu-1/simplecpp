<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Bounded one-operand output of an integer or string value.
 *
 * Output syntax:
 * ```text
 * echo 8;
 * echo "hello";
 * ```
 * Each statement owns one operand; neither inserts a separator or newline automatically.
 */
final class ast_echo_statement extends ast_statement
{
	/** Native owning; echo operand syntax child. */
	public readonly ast_expression $value;

	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		ast_expression $value)
	{
		$this->value = $value;
		parent::__construct($start, $end);
		$value->attach($this);
	}
}
