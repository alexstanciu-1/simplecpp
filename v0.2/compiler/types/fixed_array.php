<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Inline byte storage, e.g. fixed_array<fixed_array<byte,5>,3>. Each dimension keeps its logical bound. */
final class fixed_array_type extends type_definition implements indexed_collection_type, index_write_type, counted_collection_type
{
	/** CONSTANTS */
	public const int PAYLOAD_LIMIT /** uint32 */ = 4294967295;
	public const int FRAME_LIMIT /** uint32 */ = 65536;

	/** STATIC PROPERTIES */
	/** @var array<string,\WeakReference<self>> Shapes retire with their last prepared AST fact. */
	private static array $shapes /** array<string,\WeakReference> */ = [];

	/** PROPERTIES */
	public readonly type_definition $element;
	public readonly int $extent /** int64 */;
	public readonly int $bytes /** uint32 */;

	/** CONSTRUCTOR */
	/** Validate native storage before multiplying, including the physical slot of an empty dimension. */
	private function __construct(type_definition $element, ast_extent_literal $bound)
	{
		$this->element = $element;
		$this->extent = $bound->extent;
		$width = $element instanceof self ? $element->bytes : 1;
		// Standard C++ needs an addressable placeholder for an empty dimension.
		$physical = max(1, $this->extent);
		if ($physical > intdiv(self::PAYLOAD_LIMIT, $width)) {
			throw new diagnostic($bound->source(), $bound->byte_start(), 'Fixed array exceeds uint32 inline payload capacity');
		}
		$this->bytes = $width * $physical;
		parent::__construct('fixed_array<' . $element->name . ',' . $this->extent . '>');
	}

	/** DESTRUCTOR */
	public function __destruct()
	{
		unset(self::$shapes[$this->name]);
	}

	/** METHODS */
	public function cpp_type(): string
	{
		return $this->declarator('');
	}

	public function declarator(string $name): string
	{
		$name .= '[' . max(1, $this->extent) . ']';
		return $this->element instanceof self ? $this->element->declarator($name) : trim($this->element->cpp_type() . ' ' . $name);
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return [...type_uint8::instance()->cpp_headers(), 'scpp/fixed_array.hpp'];
	}

	public function supports_instance_field(): bool
	{
		return true;
	}

	public function supports_static_property(): bool
	{
		return false;
	}

	public function lower_cpp_empty(): string
	{
		return '{}';
	}

	public function write_type(): type_definition
	{
		return $this->element;
	}


	public function cardinality_result(collection_cardinality $operation): type_definition
	{
		return $operation === collection_cardinality::count ? type_int64::instance() : type_bool::instance();
	}

	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string
	{
		// A selected row can have a checked, effectful index even though its extent is known.
		$result = $operation === collection_cardinality::count ? $this->extent . 'LL' : ($this->extent === 0 ? 'true' : 'false');
		return '(static_cast<void>(' . $receiver . '), ' . $result . ')';
	}

	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== type_int64::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Fixed-array index requires int64');
		}
	}

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->element);
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		return '(' . $receiver . ')[scpp::fixed_array_index(' . $index . ', ' . $this->extent . ')]';
	}

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$target = $this->lower_cpp_index($write->index, $receiver, $key);
		$operand = $this->element->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		// Select and bounds-check the destination before evaluating a possibly effectful RHS.
		return '{ auto& fixed_element = ' . $target . '; fixed_element = ' . $operand . '; }';
	}

	/** Admit exact shape copies only where lowering has an explicit array destination. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$parent = $value->parent;
		$local_copy = ($parent instanceof ast_local_declaration && !($parent->parent instanceof ast_for_statement))
			|| ($parent instanceof ast_assignment_statement && !$parent->calculates_value());
		if ($value->type_def() === $this && ($value instanceof ast_struct_default || $value instanceof ast_collection_expression
			|| ($local_copy && ($value instanceof ast_name_expression || $value instanceof ast_index_expression || $value instanceof ast_field_expression)))) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Fixed-array value requires initialization in inline storage');
	}

	/** STATIC METHODS */
	/** Intern one shape by its resolved element and normalized extent. */
	public static function resolve(type_definition $element, ast_extent_literal $bound): self
	{
		$name = 'fixed_array<' . $element->name . ',' . $bound->extent . '>';
		$shape = (self::$shapes[$name] ?? null)?->get();
		if ($shape !== null) return $shape;
		$shape = new self($element, $bound);
		self::$shapes[$name] = \WeakReference::create($shape);
		return $shape;
	}
}
