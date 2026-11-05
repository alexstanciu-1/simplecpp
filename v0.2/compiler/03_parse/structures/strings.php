<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Quoted source spelling is preserved; semantic preparation owns byte decoding.
 *
 * String literal syntax:
 * ```text
 * echo "line\n";
 * ```
 * The expression retains quoted source spelling; preparation decodes the newline.
 */
final class ast_string_expression extends ast_expression
{
	/** Prepared fact: decoded bytes; null means unprepared, empty string is valid. */
	protected ?string $decoded_bytes = null;

	public function settle_bytes(string $bytes): void
	{
		if ($this->decoded_bytes !== null) {
			throw new \LogicException('String is already decoded');
		}
		$this->settle_value(type_registry::string(), null);
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
