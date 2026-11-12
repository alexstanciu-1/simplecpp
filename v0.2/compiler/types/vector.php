<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Resolved vector<T> identity supplied by runtime publication, with checked element access and mutation.
 * Copying, argument passing, returns and other vector value operations remain deferred.
 */
abstract class vector_type_definition extends type_definition implements appendable_collection_type, indexed_collection_type, index_write_type, counted_collection_type
{
	/** Native non-owning canonical element definition, shared by all occurrences. */
	public readonly type_definition $element_type;

	protected function __construct(string $name, type_definition $element)
	{
		parent::__construct($name);
		$this->element_type = $element;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	/** Empty construction and owning runtime results can initialize a local with the same specialization. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if (($value instanceof ast_empty_collection_expression) && ($value->type_def() === $this)
			&& ($context === conversion_context::typed_destination)) {
			return value_conversion::identity;
		}
		if ($value instanceof ast_call_expression && $value->parent instanceof ast_local_declaration
			&& $value->type_def() === $this && $value->function_def() instanceof runtime_function
			&& in_array($context, [conversion_context::typed_destination, conversion_context::inference], true)) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Vector value operations are deferred');
	}

	public function write_type(): type_definition
	{
		return $this->element_type;
	}

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->element_type);
	}
}
