<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Exclusive worker parameter. Only the joined launch can create a view over vector slots. */
final class segment_type extends type_definition implements index_read_type, index_write_type, counted_collection_type
{
	public function __construct(public readonly type_definition $element)
	{
		parent::__construct('segment<' . $element->name . '>');
	}

	public function cpp_type(): string
	{
		return 'scpp::mt::segment';
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return ['scpp/mt_segment.hpp', ...$this->element->cpp_headers()];
	}

	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->type_def() instanceof self && $value->type_def()->element === $this->element) return value_conversion::identity;
		return parent::prepare_scalar_conversion($value, $context);
	}

	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== type_int64::instance()) throw new diagnostic($key->source(), $key->byte_start(), 'Segment index requires int');
	}

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->element);
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		return '(*static_cast<' . $this->element->cpp_type() . '*>((' . $receiver . ').at(' . $index . ')))';
	}

	public function write_type(): type_definition
	{
		return $this->element;
	}

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value, ?Cpp_Adapters $adapters = null, ?string $rhs_steps = null): string
	{
		$operand = $this->element->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return '{ auto& slot = ' . $this->lower_cpp_index($write->index, $receiver, $key) . '; ' . $rhs_steps . ' slot = ' . $operand . '; }';
	}

	public function cardinality_result(collection_cardinality $operation): type_definition
	{
		return $operation === collection_cardinality::count ? type_int64::instance() : type_bool::instance();
	}

	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string
	{
		$length = '(' . $receiver . ').count()';
		return $operation === collection_cardinality::count ? $length : '(' . $length . ' == 0)';
	}
}
