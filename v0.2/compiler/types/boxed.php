<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Canonical tagged scalar family. Runtime checks consume the actual payload, e.g. (int)$boxed. */
abstract class boxed_type_definition extends type_definition implements indexed_mutation_type
{
	public function prepare_key(ast_expression $key): void
	{
		$type = $key->type_def();
		if ($type !== type_int64::instance() && $type !== type_string::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Boxed container key requires int or string');
		}
	}

	public function prepare_index(ast_index_expression $value): void
	{
		if ($value->receiver instanceof ast_index_expression) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Nested boxed indexing is deferred');
		}
		$this->prepare_key($value->index);
		$value->settle_element($this);
	}

	public function write_type(): type_definition
	{
		return $this;
	}

	public function lower_cpp_empty(): string
	{
		return $this->cpp_type() . '::container()';
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		return $receiver . '.source_read(scpp::boxed_key(' . $index . '))';
	}

	/** Generated source_has applies the same source-key normalization as indexed reads. */
	public function lower_cpp_key_presence(string $receiver, string $key): string
	{
		return '(' . $receiver . ').source_has(' . $key . ')';
	}

	public function lower_cpp_mutation_read(ast_expression $key, string $receiver, string $index): string
	{
		return $receiver . '.source_require(scpp::boxed_key(' . $index . '))';
	}

	public function lower_cpp_replace(ast_expression $key, string $receiver, string $index, string $value): string
	{
		return $receiver . '.source_set(scpp::boxed_key(' . $index . '), ' . $value . ');';
	}

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$value = $this->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return $receiver . '.source_set(scpp::boxed_key(' . $key . '), ' . $value . ');';
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function cpp_type(): string
	{
		return 'scpp::' . $this->name;
	}

	abstract public function accepts_scalar(type_definition $source): bool;
	abstract public function admits_extraction(type_definition $target, conversion_context $context): bool;
	abstract public function box_cpp(ast_expression $syntax, string $value): string;
	abstract public function admits_pair(binary_operator $operator, type_definition $peer, bool $box_left): bool;

	/** Admit copies within this family and box scalar values through the generated bindings. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->type_def() === $this) {
			return value_conversion::identity;
		}
		if ($this->accepts_scalar($value->type_def())) {
			return value_conversion::box_scalar;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxing into ' . $this->name);
	}

	/** Select the box execution type once and attach the scalar or boxed result to the operator. */
	public function prepare_binary(ast_binary_expression $value): void
	{
		$box_left = $value->left->type_def() === $this;
		$peer = $box_left ? $value->right->type_def() : $value->left->type_def();
		if (!$this->admits_pair($value->operator, $peer, $box_left)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxed binary operands or operator');
		}
		$value->settle_binary($this->boxed_result($value->operator) ?? throw new \LogicException('Missing boxed result'), $this);
	}

	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right, bool $typed_initializer = false,
		bool $bare_comparison = false): string
	{
		return 'scpp::boxed_' . $expr->operator->name . '_' . $expr->left->type_def()->name . '_'
			. $expr->right->type_def()->name . '(' . $left . ', ' . $right . ', ' . $this->flavor_mask($expr) . ')';
	}

	abstract protected function boxed_result(binary_operator $operator): ?type_definition;
	abstract protected function flavor_mask(ast_node $site): int /** uint8 */;

	public function prepare_compound(ast_assignment_statement $value): void
	{
		if (!$this->supports_compound($value->operator) || $this->binary_result($value->operator, $value->value->type_def()) !== $this) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported compound operands or write-back type');
		}
		$conversion = $this->prepare_scalar_conversion($value->value, conversion_context::typed_destination);
		$value->settle_compound($this, value_conversion::identity, $conversion, value_conversion::identity);
	}

	public function lower_prepared_compound(ast_assignment_statement $site, string $left, string $right): string
	{
		return 'scpp::boxed_' . $site->operator->name . '_' . $this->name . '_' . $this->name
			. '(' . $left . ', ' . $right . ', ' . $this->flavor_mask($site) . ')';
	}

	public function lower_cpp_update(update_operator $operator, string $target, bool $postfix): string
	{
		return $target . ' = scpp::boxed_' . $operator->name . '_' . $this->name . '(' . $target . ')';
	}
}
