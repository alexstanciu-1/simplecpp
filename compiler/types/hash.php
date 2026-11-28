<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Published hash<V,K> identity; e.g. hash<int,string>. Empty construction, keyed writes and value-copy reads are supported. */
abstract class hash_type_definition extends type_definition implements readonly_index_lookup_type, indexed_mutation_type, counted_collection_type, membership_type
{
	use indexed_entry_read;
	use checked_indexed_mutation;
	use collection_specializations;
	use concrete_value_storage;

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
	public function private_copy_allocation_only(): bool
	{
		return $this->key_type->copy_has_no_observable_effects() && $this->value_type->copy_has_no_observable_effects();
	}

	abstract public function presence_function(): runtime_function;

	public function prepare_membership(ast_binary_expression $site): void
	{
		if ($site->left->type_def() !== $this->key_type) {
			throw new diagnostic($site->source(), $site->left->byte_start(), 'Membership requires key type ' . $this->key_type->name);
		}
		$site->settle_binary(type_bool::instance(), $this);
	}

	/** @param array<string,bool> $headers */
	public function lower_cpp_membership(ast_expression $needle, string $key, string $receiver, array &$headers): string
	{
		$binding = $this->presence_function();
		$headers[$binding->header] = true;
		return '(' . $receiver . ').' . $binding->symbol . '(' . $this->lower_cpp_key($needle, $key) . ')';
	}
}
