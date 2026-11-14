<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Published integer literal from runtime metadata, e.g. PHP STR_PAD_LEFT. */
final readonly class runtime_integer_constant
{
	private string $key;
	public int $value /** int64 */;

	public function __construct(string $key, int $value /** int64 */)
	{
		$this->key = $key;
		$this->value = $value;
	}

	public function symbol_key(): string
	{
		return $this->key;
	}
}

/** Immutable namespace or struct constant with AST-owned literal facts, e.g. Point::LIMIT. */
final class ast_constant_declaration extends ast_literal_declaration
{
	public function name(): string
	{
		return ($this->parent ?? null) instanceof ast_struct_declaration
			? $this->parent->name() . '::' . $this->short_name : parent::name();
	}

	public function symbol_key(): string
	{
		return 'const:' . $this->name();
	}

	/** Return: declared-name token position for the flavor's required typed spelling. */
	public function name_position(): int /** uint32 */
	{
		return $this->source()->flavor === flavor::js ? $this->start + 1 : $this->type_reference->end;
	}

	/** Scalar payload is derived from its initializer and approved conversion, never stored a second time. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$value = $this->type_def()->evaluate_constant($this->initialization(), $this->initializer);
		return $value === constant_evaluation::unavailable ? null : $value;
	}

	public function constant_known(): bool
	{
		return $this->type_def()->evaluate_constant($this->initialization(), $this->initializer) !== constant_evaluation::unavailable;
	}
}
