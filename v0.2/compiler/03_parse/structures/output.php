<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Ordered output of an integer or string value.
 *
 * Output syntax:
 * ```text
 * echo 8;
 * echo "hello", 8;
 * ```
 * Each statement owns its operands; echo never inserts a separator or newline automatically.
 */
final class ast_echo_statement extends ast_statement
{
	/** Native owning syntax children, in output order. @var ast_expression[] */
	public readonly array $values /** list<ast_expression> */;

	/**
	 * @param ast_expression[] $values
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		array $values /** list<ast_expression> */)
	{
		$this->values = $values;
		parent::__construct($start, $end);
		foreach ($values as $value) {
			$value->attach($this);
		}
	}
}
