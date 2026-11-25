<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Shared carrier transport; e.g. nullable<int> wraps an approved int conversion or checks one payload extraction. */
abstract class carrier_type_definition extends type_definition implements presence_test_type
{
	/** PROPERTIES */
	/** Native non-owning canonical payload definition, shared by all occurrences. */
	public readonly type_definition $payload_type;

	/** CONSTRUCTOR */
	protected function __construct(string $name, type_definition $payload)
	{
		parent::__construct($name);
		$this->payload_type = $payload;
	}

	/** METHODS */
	/** Metadata-generated alternate-state admission and checked extraction binding. */
	abstract public function accepts_empty(): bool;
	/** Generated admission for a value-or-empty carrier; other alternate states are not fallback. */
	abstract public function supports_coalescing(): bool;
	abstract public function sentinel_factory(string $state): ?string;
	abstract public function implicit_extraction(): bool;
	abstract public function lower_cpp_extract(string $value): string;
	abstract public function lower_cpp_presence(string $value): string;

	/** Instance-field publication does not grant static carrier storage. */
	final public function supports_static_property(): bool
	{
		return false;
	}

	/** A strict null comparison operates on the carrier itself and reads only presence. */
	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right, bool $typed_initializer = false,
		bool $bare_comparison = false): string
	{
		$carrier_first = $expr->left->type_def() === $this;
		$literal = $carrier_first ? $expr->right : $expr->left;
		if ($literal instanceof ast_keyword_expression && in_array($literal->word, ['true','false'], true)) {
			$test = '(' . ($carrier_first ? $left : $right) . ').is_' . $literal->word . '()';
			return $expr->operator === binary_operator::not_identical ? '!' . $test : $test;
		}
		return $this->lower_null_presence_comparison($expr, $this->lower_cpp_presence($carrier_first ? $left : $right),
			$carrier_first ? $right : $left);
	}

	/** The presence lowering sequences both original operands without copying the carrier. */
	public function cpp_direct_binary(ast_binary_expression $expr, bool $operands_safe, bool $same_spelling): bool
	{
		return true;
	}

	/** Enforce the metadata extraction policy before selecting a payload operation. */
	public function require_extraction(ast_expression $site, conversion_context $context): void
	{
		if (!$this->implicit_extraction() && ($context !== conversion_context::explicit_cast)) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Carrier requires explicit payload extraction');
		}
	}

	/** Conditions preserve alternate states; only successful payload truthiness delegates to T. */
	public function prepare_condition(ast_expression $value): void
	{
		$this->payload_type->prepare_condition($value);
	}

	/** @return string[] Payload truthiness may need an additional runtime binding. */
	public function condition_headers(): array /** list<string> */
	{
		return $this->payload_type->condition_headers();
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	/** Wrap only an admitted payload conversion, or the metadata-authorized empty state. */
	public function prepare_construction(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->type_def() instanceof carrier_sentinel_type_definition) {
			if ($this->sentinel_factory($value->type_def()->state) !== null) {
				return value_conversion::sentinel_carrier;
			}
			throw new diagnostic($value->source(), $value->byte_start(), 'Sentinel is not supported by this carrier');
		}
		if ($value->type_def() === type_null::instance()) {
			if ($this->accepts_empty()) {
				return value_conversion::empty_carrier;
			}
			throw new diagnostic($value->source(), $value->byte_start(), 'Null cannot construct this carrier');
		}
		if ($value->value_type() === $this->payload_type) {
			return value_conversion::identity | value_conversion::WRAP
				| ($value->type_def() instanceof carrier_type_definition ? value_conversion::EXTRACT : 0);
		}
		$conversion = $this->payload_type->prepare_conversion($value, $context);
		return $conversion | value_conversion::WRAP;
	}
}

/** Canonical nullable transport for a source-declared enum, owned by that enum. */
final class nullable_enum_type_definition extends carrier_type_definition
{
	public function __construct(enum_type_definition $payload)
	{
		parent::__construct('nullable<' . $payload->name . '>', $payload);
	}

	public function dispatch_label_type(): type_definition
	{
		return $this->payload_type;
	}

	public function accepts_empty(): bool
	{
		return true;
	}

	public function supports_coalescing(): bool
	{
		return true;
	}

	public function implicit_extraction(): bool
	{
		return true;
	}

	public function sentinel_factory(string $state): ?string
	{
		return null;
	}


	public function lower_cpp_extract(string $value): string
	{
		return '(' . $value . ').value()';
	}

	public function lower_cpp_presence(string $value): string
	{
		return '(' . $value . ').has_value()';
	}

	public function cpp_type(): string
	{
		return 'std::optional<' . $this->payload_type->cpp_type() . '>';
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return ['optional', ...$this->payload_type->cpp_headers()];
	}
}

/** Compiler-only literal identity for an explicit alternate state; e.g. false_sentinel is not the bool false. */
abstract class carrier_sentinel_type_definition extends type_definition
{
	public readonly string $state;

	protected function __construct(string $name, string $state)
	{
		parent::__construct($name);
		$this->state = $state;
	}

	public function prepare_literal(ast_expression $syntax): void
	{
		if (!($syntax instanceof ast_keyword_expression) || ($syntax->word !== $this->name)) {
			throw new \LogicException('Expected sentinel literal');
		}
		$syntax->settle_keyword($this);
	}

	public function cpp_type(): string
	{
		throw new \LogicException('Sentinel has no independent storage');
	}

	public function lower_cpp_literal(ast_expression $syntax): string
	{
		return 'nullptr';
	}
}
