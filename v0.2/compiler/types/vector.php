<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Resolved vector<T> identity supplied by runtime publication, with empty construction, append and value-copy reads.
 * Copying, argument passing, returns and other vector value operations remain deferred.
 */
abstract class vector_type_definition extends type_definition
{
	/** PROPERTIES */
	/** Native non-owning canonical element definition, shared by all occurrences. */
	public readonly type_definition $element_type;

	/** CONSTRUCTOR */
	protected function __construct(string $name, type_definition $element)
	{
		parent::__construct($name);
		$this->element_type = $element;
	}

	/** METHODS */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if (($value instanceof ast_empty_collection_expression) && ($value->type_def() === $this)
			&& ($context === conversion_context::typed_destination)) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Vector value operations are deferred');
	}

	/** Generated handlers consume validated runtime member signatures; no source-time metadata lookup. */
	abstract public function prepare_append(ast_expression $value): int /** uint16 */;
	abstract public function prepare_index(ast_index_expression $value): void;
	abstract public function lower_cpp_empty(): string;
	abstract public function lower_cpp_append(string $receiver, string $value): string;
	abstract public function lower_cpp_index(string $receiver, string $index): string;
}
