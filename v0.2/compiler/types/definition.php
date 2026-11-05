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

	/** Output admission is separate from string conversion. */
	public function supports_output(): bool
	{
		return false;
	}

	/** @return string[] Public headers for the approved output strategy. */
	public function output_headers(): array /** list<string> */
	{
		return [];
	}

	/** Consume an approved operand; null spelling denotes a string literal handled without storage. */
	public function lower_cpp_output(ast_expression $value, ?string $operand): string
	{
		throw new \LogicException('Output was not approved');
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

	/** Attach ordinary binary facts through generated type admission. */
	public function prepare_binary(ast_binary_expression $value): void
	{
		$result = $this->binary_result($value->operator, $value->right->type_def());
		if (($value->left->type_def() !== $this) || ($result === null)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported binary operands or operator');
		}
		$value->settle_binary($result);
	}

	/** Generated operator admission without requiring synthetic syntax; null means the pair is unsupported. */
	public function binary_result(binary_operator $operator, type_definition $right): ?type_definition
	{
		return null;
	}

	/** Compound writes may remain unavailable even when ordinary arithmetic is supported. */
	public function supports_compound(): bool
	{
		return false;
	}

	public function lower_cpp_binary(binary_operator $operator, string $left, string $right): string
	{
		throw new \LogicException('Binary lowering unavailable for type: ' . $this->name);
	}

	/** @return string[] Public includes needed by an approved binary operation. */
	public function binary_headers(binary_operator $operator): array /** list<string> */
	{
		return [];
	}

	/** Backend property: copying a plain value cannot call user code, allocate or throw. */
	public function cpp_trivial_value(): bool
	{
		return false;
	}

	/** Backend property of an approved operation, excluding its operands; conservative for unknown strategies. */
	public function cpp_binary_reorderable(binary_operator $operator): bool
	{
		return false;
	}

	/** Generated numeric prefix admission attaches only the result identity to the original AST. */
	public function prepare_unary(ast_unary_expression $value): void
	{
		throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported unary operand or operator');
	}

	public function lower_cpp_unary(unary_operator $operator, string $operand): string
	{
		throw new \LogicException('Unary lowering unavailable for type: ' . $this->name);
	}

	/** Conservative for unknown lowering strategies; operand safety is checked separately by the backend. */
	public function cpp_unary_reorderable(unary_operator $operator): bool
	{
		return false;
	}

	/** Conditions are a separate semantic boundary from typed boolean conversion. */
	public function prepare_condition(ast_expression $value): void
	{
		throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported condition type: ' . $this->name);
	}

	public function lower_cpp_condition(string $value): string
	{
		throw new \LogicException('Condition lowering is unavailable for type: ' . $this->name);
	}

	/** @return string[] Public includes needed by this type's condition operation. */
	public function condition_headers(): array /** list<string> */
	{
		return [];
	}

	/** Generated capability for discarded-value updates; unsupported types reject by default. */
	public function supports_update(update_operator $operator): bool
	{
		return false;
	}

	public function lower_cpp_update(update_operator $operator, string $target, bool $postfix): string
	{
		throw new \LogicException('Update lowering unavailable for type: ' . $this->name);
	}

	/** Native representation of an already resolved language type. */
	abstract public function cpp_type(): string;

	/** Lower only settled literals; a missing implementation is an internal error, not source admission. */
	public function lower_cpp_literal(ast_expression $syntax): string
	{
		throw new \LogicException('Literal lowering is unavailable for type: ' . $this->name);
	}

	/** Same-type initialization needs no work; other rules are generated on the destination definition. */
	public function prepare_conversion(ast_expression $value, conversion_context $context): value_conversion
	{
		if ($value->type_def() === $this) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(),
			'Unsupported ' . (in_array($context, [conversion_context::explicit_cast, conversion_context::typed_destination], true) ? 'explicit' : 'implicit') . ' conversion from '
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
