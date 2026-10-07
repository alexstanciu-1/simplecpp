<?php

/* Resolve capabilities through completed declarations, without teaching the registry scheduling. */
namespace scpp\compiler;

final class Capability_Preparation implements type_capability_query
{
	public function __construct(private preparation_context $context)
	{
	}

	public function has_capability(canonical_type_use $type_use, generic_contract $capability): bool
	{
		// Wrapper identity is registered independently of the payload's completed copy contract.
		$payload = Nullable_Conversions::payload($type_use);
		if (($payload !== null) && ($capability === generic_contract::copyable_value)) {
			return $this->has_capability($payload, $capability);
		}
		// Handle copying/storage is independent of the pointee's fields or completeness.
		if (Type_Queries::is_class_handle($type_use)) {
			return ($capability === generic_contract::copyable_value)
				|| ($capability === generic_contract::value_storable);
		}
		$declaration = Type_Queries::source_declaration($type_use);
		if ($declaration === null) {
			return Model::$type_catalog->registry()->has_capability($type_use, $capability);
		}
		$this->context->worker->require_declaration($this->context->owner, $declaration);
		if ($declaration instanceof collected_record) {
			return object_cast($declaration, collected_record::class)->syntax()->require_preparation()->has_capability($capability);
		}
		if ($declaration instanceof collected_enum) {
			return object_cast($declaration, collected_enum::class)->enum_syntax()->require_preparation()->has_capability($capability);
		}
		throw new \RuntimeException('S2S source type capabilities are not implemented');
	}

	/** Ordinary values require copy capability; explicit temporary consumption is a separate boundary. */
	public static function require_copy(canonical_type_use $type_use, preparation_context $context): void
	{
		$query = new Capability_Preparation($context);
		if (!$query->has_capability($type_use, generic_contract::copyable_value)) {
			throw new \RuntimeException('S2S value copy requires copyable_value');
		}
	}
}
