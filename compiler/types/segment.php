<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Provider lends stable read-only slots through join, with optional observable keys. */
interface read_segment_source
{
	public function iteration_value_type(): type_definition;
	public function read_segment_key_type(): ?type_definition;
	public function read_segment_borrow(): string;
}

/** Borrowed worker parameter. Only the joined launch can create a view over collection slots. */
final class segment_type extends type_definition implements python_index_read_type, index_write_type, counted_collection_type
{
	public function __construct(public readonly type_definition $element, public readonly bool $read_only, public readonly ?type_definition $key = null)
	{
		$family = ($read_only ? 'read_' : '') . ($key === null ? 'segment' : 'hash_segment');
		parent::__construct($family . '<' . $element->name . ($key === null ? '' : ',' . $key->name) . '>');
	}

	public function cpp_type(): string
	{
		return 'scpp::mt::segment';
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return ['scpp/mt_segment.hpp', ...$this->element->cpp_headers(), ...($this->key?->cpp_headers() ?? [])];
	}

	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->type_def() instanceof self && $value->type_def()->element === $this->element
			&& $value->type_def()->key === $this->key && ($this->read_only || !$value->type_def()->read_only)) return value_conversion::identity;
		return parent::prepare_scalar_conversion($value, $context);
	}

	public function prepare_key(ast_expression $key): void
	{
		if ($this->key !== null) throw new diagnostic($key->source(), $key->byte_start(), 'Traverse hash segments with foreach');
		if ($key->type_def() !== type_int64::instance()) throw new diagnostic($key->source(), $key->byte_start(), 'Segment index requires int');
	}

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->element);
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		$method = $this->read_only ? 'read_at' : 'at';
		return '(*static_cast<' . ($this->read_only ? 'const ' : '') . $this->element->cpp_type() . '*>((' . $receiver . ').' . $method . '(' . $index . ')))';
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

/** Two owned coordinates returned by balanced_range; no collection or grant is retained. */
final class type_segment_range extends type_definition
{
	private function __construct()
	{
		parent::__construct('segment_range');
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function cpp_type(): string
	{
		return 'scpp::mt::segment_range';
	}

	/** @return list<string> */
	public function cpp_headers(): array /** list<string> */
	{
		return ['scpp/mt_segment.hpp'];
	}

	public static function instance(): self
	{
		static $type = null;
		return $type ??= new self();
	}
}
