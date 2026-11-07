<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Published hash<V,K> identity; e.g. hash<int,string>. Empty construction, keyed writes and value-copy reads are supported. */
abstract class hash_type_definition extends type_definition
{
	/** PROPERTIES */
	/** Native non-owning canonical value type. */
	public readonly type_definition $value_type;
	/** Native non-owning canonical key type. */
	public readonly type_definition $key_type;

	/** CONSTRUCTOR */
	protected function __construct(string $name, type_definition $value, type_definition $key)
	{
		parent::__construct($name);
		$this->value_type = $value;
		$this->key_type = $key;
	}

	/** METHODS */
	/** Only an explicitly typed local can construct a hash from empty brackets. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if (($value instanceof ast_empty_collection_expression) && ($value->type_def() === $this)
			&& ($context === conversion_context::typed_destination)) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Hash value operations are deferred');
	}

	/** Generated handlers select exact key admission and checked value conversion from the published contract. */
	abstract public function prepare_key(ast_expression $key): void;
	abstract public function prepare_write(ast_expression $value): int /** uint16 */;
	abstract public function lower_cpp_empty(): string;
	abstract public function lower_cpp_index(string $receiver, string $index): string;
	abstract public function lower_cpp_write(string $receiver, string $index, string $value): string;

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->value_type);
	}
}
