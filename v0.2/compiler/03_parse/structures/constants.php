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

/** Immutable namespace value, e.g. const int LIMIT = 8; published as const:LIMIT with AST-owned literal facts. */
final class ast_constant_declaration extends ast_literal_declaration
{
	public function symbol_key(): string
	{
		return 'const:' . $this->name();
	}

	/** Return: declared-name token position for the flavor's required typed spelling. */
	public function name_position(): int /** uint32 */
	{
		return $this->start + ($this->source()->flavor === flavor::js ? 1 : 2);
	}

	/** Scalar payload is derived from its initializer and approved conversion, never stored a second time. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		return $this->type_def()->convert_constant($this->initialization(), $this->initializer->constant());
	}

	public function constant_known(): bool
	{
		return !($this->type_def() instanceof boxed_type_definition) && $this->initializer->constant_known();
	}
}
