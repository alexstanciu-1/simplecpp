<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Shared semantic type contract; canonical definitions own type-specific behavior, never occurrence facts. */
abstract class type_definition
{
	/** PROPERTIES */
	public readonly string $name;

	/** CONSTRUCTOR */
	protected function __construct(string $name)
	{
		$this->name = $name;
	}

	/** METHODS */
	/** Definitions are canonical shared identities, not copyable per-expression records. */
	final protected function __clone(): void
	{
	}

	public function accepts_name(string $name): bool
	{
		return $name === $this->name;
	}

	/** Unsupported literal kinds fail during preparation; successful overrides attach facts to the AST. */
	public function prepare_literal(ast_expression $syntax): void
	{
		throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported literal for type: ' . $this->name);
	}

	/** Native representation of an already resolved language type. */
	abstract public function cpp_type(): string;

	/** Lower only settled literals; a missing implementation is an internal error, not source admission. */
	public function lower_cpp_literal(ast_expression $syntax): string
	{
		throw new \LogicException('Literal lowering is unavailable for type: ' . $this->name);
	}

	/** Same-type initialization needs no work; other rules are generated on the destination definition. */
	public function prepare_conversion(ast_expression $value, bool $explicit): value_conversion
	{
		if ($value->type_def() === $this) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(),
			'Unsupported ' . ($explicit ? 'explicit' : 'implicit') . ' conversion from '
			. $value->type_def()->name . ' to ' . $this->name);
	}

	public function lower_cpp_conversion(value_conversion $conversion, string $value): string
	{
		return $conversion === value_conversion::identity ? $value
			: throw new \LogicException('Conversion does not belong to this destination type');
	}

	/** Convert only an already-checked constant; null also carries an unknown payload beside its known flag. */
	public function convert_constant(value_conversion $conversion, int|float|bool|null $value /** int64|float64|bool|null */): int|float|bool|null /** int64|float64|bool|null */
	{
		return $conversion === value_conversion::identity ? $value
			: throw new \LogicException('Constant conversion does not belong to this destination type');
	}

	/** @return string[] Public runtime includes required by storage of this type. */
	public function cpp_headers(): array /** list<string> */
	{
		return [];
	}

	/** @return string[] Public runtime includes required by the selected conversion only. */
	public function conversion_headers(value_conversion $conversion): array /** list<string> */
	{
		return [];
	}
}
