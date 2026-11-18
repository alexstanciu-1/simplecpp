<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One suspension: yield value; or yield key => value. Outputs use ordinary typed conversions. */
final class ast_yield_statement extends ast_statement
{
	/** Native owning yielded expression. */
	public readonly ast_expression $value;
	/** Native owning optional key expression. */
	public readonly ?ast_expression $key;

	/** Prepared output conversion; null until preparation or after reset. */
	public ?int $conversion /** uint16 */ = null;
	/** Prepared key conversion; null for value-only yields or before preparation. */
	public ?int $key_conversion /** uint16 */ = null;

	/** $start/$end are the statement's token boundaries. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $value, ?ast_expression $key)
	{
		parent::__construct($start, $end);
		$this->value = $value;
		$this->key = $key;
		$value->attach($this);
		$key?->attach($this);
	}
}
