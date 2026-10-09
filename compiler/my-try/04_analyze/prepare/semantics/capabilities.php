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
		$definition = Type_Queries::canonical($type_use)->definition();
		if ($definition instanceof closure_type_definition) {
			$identity = Model::$type_catalog->closures()->identity($type_use);
			$this->context->worker->require_contract($this->context->owner, $identity->signature_work);
			// Value fields are copyable; borrowed fields copy bindings, not payloads.
			// Destination lifetimes and erased-boundary admission are separate checks.
			return $definition->declares_capability($capability);
		}
		// Wrapper identity is registered independently of the payload's completed copy contract.
		$payload = Nullable_Conversions::payload($type_use);
		if (($payload !== null) && ($capability === generic_contract::copyable_value)) {
			return $this->has_capability($payload, $capability);
		}
		// Owned aggregate copies depend on their contents, not template registration alone.
		if ($capability === generic_contract::copyable_value)
		{
			if ($definition instanceof result_semantics)
			{
				$role = object_cast($definition, result_semantics::class);
				$application = object_cast(Type_Queries::canonical($type_use), applied_template_type::class);
				return $this->has_capability($role->payload_type($application), $capability);
			}
			if (($definition === Model::$type_catalog->definition('vector'))
				|| ($definition === Model::$type_catalog->definition('hash')))
			{
				$application = object_cast(Type_Queries::canonical($type_use), applied_template_type::class);
				foreach ($application->arguments() as $argument) {
					if (!$this->has_capability($argument, $capability)) {
						return false;
					}
				}
				return true;
			}
		}
		// Handle copying/storage is independent of the pointee's fields or completeness.
		if (Type_Queries::is_nominal_handle($type_use)) {
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
		self::require_concrete_value($type_use, $context);
		$query = new Capability_Preparation($context);
		if (!$query->has_capability($type_use, generic_contract::copyable_value)) {
			throw new \RuntimeException('S2S value copy requires copyable_value');
		}
	}

	/** Abstract subobjects may be copied, but cannot be materialized as complete inline values. */
	public static function require_concrete_value(canonical_type_use $type_use, preparation_context $context): void
	{
		Type_Lifecycle_Preparation::require_storage($type_use, $context);
		if (Type_Queries::is_nominal_handle($type_use)) { return; }
		$record = Type_Queries::source_record($type_use);
		if ($record === null) { return; }
		if ($context->worker->require_relationships($record, $context)->abstract_type) {
			throw new \RuntimeException('Abstract type cannot be stored or returned as an inline value');
		}
	}
}
