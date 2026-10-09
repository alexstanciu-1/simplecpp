<?php

/* Observe ancestry through the owning scheduler, not registry caches or backend inference. */
namespace scpp\compiler;

final class Conversion_Relationships implements conversion_relationship_query
{
	public function is_callable_compatible(canonical_type_use $source, callable_type_definition $target): bool
	{
		if (!(Type_Queries::canonical($source)->definition() instanceof closure_type_definition)) { return false; }
		$identity = Model::$type_catalog->closures()->identity($source);
		$this->context->worker->prepare_anonymous($identity, $this->context->owner);
		$provided = $identity->signature();
		$required = $target->signature();
		if (!$provided->return_type->matches($required->return_type)) { return false; }
		$inputs /** Storage<callable_parameter> */ = $provided->parameters;
		$expected /** Storage<callable_parameter> */ = $required->parameters;
		if (q_count($inputs) !== q_count($expected)) { return false; }
		foreach ($inputs as $index => $input) {
			// Erasure must not silently discard defaults, packing or reference contracts.
			if (($input->default_value !== null) || ($input->element_type !== null)) { return false; }
			if (!$input->type->matches($expected[$index]->type) || ($input->mode !== passing_mode::value)) { return false; }
		}
		Capability_Preparation::require_copy($source, $this->context);
		return true;
	}
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

	/** Observe the target contract: a later edit can remove safe base destruction. */
	public function has_virtual_destruction(canonical_type_use $type): bool
	{
		$record = Type_Queries::source_record($type);
		if ($record === null) { return false; }
		return $this->context->worker->require_type_contract($record, $this->context)->polymorphic;
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
