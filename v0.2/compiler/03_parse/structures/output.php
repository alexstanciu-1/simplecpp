<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Quoted source spelling is preserved; semantic preparation owns byte decoding.
 *
 * Output-only literal syntax:
 * ```text
 * echo "line\n";
 * ```
 * The expression retains quoted source spelling; preparation decodes the newline.
 */
final class ast_string_expression extends ast_expression
{
}

/**
 * Bounded one-operand output; this does not admit string values in other expression positions.
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
	public readonly ast_expression $value;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */, ast_expression $value)
	{
		$this->value = $value;
		parent::__construct($start, $end);
		$value->attach($this);
	}
}
