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
	/** Prepared fact: output-only decoded bytes; null means unprepared, empty string is valid. */
	protected ?string $decoded_bytes = null;

	public function settle_bytes(string $bytes): void
	{
		if ($this->decoded_bytes !== null) {
			throw new \LogicException('String is already decoded');
		}
		$this->decoded_bytes = $bytes;
	}

	public function bytes(): string
	{
		return $this->decoded_bytes ?? throw new \LogicException('String is not decoded');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->decoded_bytes = null;
	}
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
