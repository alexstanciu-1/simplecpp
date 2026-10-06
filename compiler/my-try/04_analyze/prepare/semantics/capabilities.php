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
		$record = Type_Queries::source_record($type_use);
		if ($record === null) {
			return Model::$type_catalog->registry()->has_capability($type_use, $capability);
		}
		$this->context->worker->require_declaration($this->context->owner, $record);
		return $record->syntax()->require_preparation()->has_capability($capability);
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
