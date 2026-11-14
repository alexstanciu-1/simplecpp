<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Resolved vector<T> identity supplied by runtime publication, with checked element access and mutation.
 * Copies and by-value calls use the common conversion path; instance fields default to empty.
 */
abstract class vector_type_definition extends type_definition implements appendable_collection_type, indexed_mutation_type, counted_collection_type
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
	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function supports_instance_field(): bool
	{
		return true;
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
