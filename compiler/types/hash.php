<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Published hash<V,K> identity; e.g. hash<int,string>. Empty construction, keyed writes and value-copy reads are supported. */
abstract class hash_type_definition extends type_definition implements subset_source, keyed_result_builder, readonly_index_lookup_type, writable_iteration_type, nested_index_storage_type, counted_collection_type, membership_type
{
	use same_type_subset;
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
	/** A keyed probe of a nullable shared value inspects the stored owner after lookup. */
	public function probes_stored_owner(): bool
	{
		return $this->value_type instanceof shared_record_type_definition && !$this->value_type->required;
	}

	public function private_copy_allocation_only(): bool
	{
		return $this->key_type->copy_has_no_observable_effects() && $this->value_type->copy_has_no_observable_effects();
	}

	public function lower_cpp_slot(string $receiver, string $key): string
	{
		return "$receiver.checked_slot($key)";
	}

	public function lower_cpp_iteration_reference(string $receiver, int $loop_start): string
	{
		return "$receiver.iteration_reference(loop_{$loop_start}_iteration_position)";
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function supports_instance_field(): bool
	{
		return true;
	}

	public function cpp_entry_key_type(): string
	{
		return $this->key_type->cpp_type();
	}

	public function write_type(): type_definition
	{
		return $this->value_type;
	}

	public function lower_cpp_key(ast_expression $syntax, string $key): string
	{
		return $this->key_type->lower_cpp_exact_argument($syntax, value_conversion::identity, $key);
	}

	public function publishes_entry_isset(): bool
	{
		return false;
	}

	public function php_isset_header(): string
	{
		return 'scpp/php/hash_isset.hpp';
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

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->value_type);
	}
}
