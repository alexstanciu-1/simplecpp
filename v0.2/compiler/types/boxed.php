<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Canonical tagged scalar family. Runtime checks consume the actual payload, e.g. (int)$boxed. */
abstract class boxed_type_definition extends type_definition implements indexed_collection_type, index_write_type
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

	public function lower_cpp_key(string $key): string
	{
		return 'scpp::boxed_key(' . $key . ')';
	}

	public function lower_cpp_index(string $receiver, string $index): string
	{
		return '(' . $receiver . ').source_read(' . $this->lower_cpp_key($index) . ')';
	}

	public function lower_cpp_write(string $receiver, string $key, string $value): string
	{
		return '(' . $receiver . ').source_set(' . $key . ', ' . $value . ')';
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
	abstract public function box_cpp(type_definition $source, string $value): string;
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
		$value->settle_binary($value->operator === binary_operator::add ? $this : type_bool::instance(), $this);
	}

	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right): string
	{
		return 'scpp::boxed_' . $expr->operator->name . '_' . $expr->left->type_def()->name . '_'
			. $expr->right->type_def()->name . '(' . $left . ', ' . $right . ')';
	}
}
