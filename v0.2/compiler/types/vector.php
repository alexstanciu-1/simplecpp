<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Resolved vector<T> identity supplied by runtime publication; no construction, casts or value operations yet. */
abstract class vector_type_definition extends type_definition
{
	/** Native non-owning canonical element definition, shared by all occurrences. */
	public readonly type_definition $element_type;

	protected function __construct(string $name, type_definition $element)
	{
		parent::__construct($name);
		$this->element_type = $element;
	}

	public function prepare_conversion(ast_expression $value, conversion_context $context): value_conversion
	{
		throw new diagnostic($value->source(), $value->byte_start(), 'Vector value operations are deferred');
	}
}
