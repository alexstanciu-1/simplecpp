<?php

/* Observe ancestry through the owning scheduler, not registry caches or backend inference. */
namespace scpp\compiler;

final class Conversion_Relationships implements conversion_relationship_query
{
	public function __construct(private preparation_context $context)
	{
	}

	public function is_subtype(canonical_type_use $source, canonical_type_use $target): bool
	{
		$source_owner = Type_Queries::source_declaration($source);
		$target_owner = Type_Queries::source_declaration($target);
		if (!($source_owner instanceof collected_member_owner) || !($target_owner instanceof collected_member_owner)) {
			return false;
		}
		return $this->reaches(object_cast($source_owner, collected_member_owner::class),
			object_cast($target_owner, collected_member_owner::class));
	}

	/** Relationship completion rejects cycles; each visited edge becomes a consumer dependency. */
	private function reaches(collected_member_owner $source, collected_member_owner $target): bool
	{
		if ($source === $target) { return true; }
		$relations = $this->context->worker->require_relationships($source, $this->context);
		$base = weakref_get($relations->base);
		if ($base !== null) {
			if ($this->reaches($base, $target)) { return true; }
		}
		foreach ($relations->interfaces as $interface) {
			if ($this->reaches($interface, $target)) { return true; }
		}
		return false;
	}
}
