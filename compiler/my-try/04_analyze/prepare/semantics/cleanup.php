<?php

/* Whole-storage reset admission is independent of keyed removal and indexing capability. */
namespace scpp\compiler;

final class Cleanup_Preparation
{
	/** Only analysis selects resettable storage; the backend consumes its exact admitted type. */
	public static function prepare(cleanup_node $syntax, preparation_context $context): prepared_cleanup
	{
		if ($syntax instanceof unset_node) {
			if ($syntax->target instanceof index_node) {
				return new prepared_keyed_removal(Keyed_Path_Preparation::prepare($syntax->target, $context, true));
			}
		}
		if (!($syntax->target instanceof variable_reference_node)) {
			throw new \RuntimeException('S2S whole-storage cleanup requires a local or parameter variable');
		}
		$variable = object_cast($syntax->target, variable_reference_node::class);
		$value = Expression_Preparation::prepare($variable, $context);
		if (!$value->addressable()) {
			throw new \RuntimeException('S2S cleanup requires mutable storage');
		}
		if ($syntax instanceof unset_node)
		{
			// Read the named binding, not the flattened place: aliases deliberately share their owner's place.
			$locals = $context->locals;
			$bindings /** vector<prepared_storage> */ = $locals->named($variable->occurrence()->name);
			$storage = $bindings[0];
			if ($storage instanceof prepared_binding) {
				$binding = object_cast($storage, prepared_binding::class);
				if ($binding->alias_target !== null) {
					throw new \RuntimeException('S2S unset cannot detach a native reference alias');
				}
			}
			if ($storage instanceof prepared_parameter) {
				$parameter = object_cast($storage, prepared_parameter::class);
				if ($parameter->contract->mode === passing_mode::reference) {
					throw new \RuntimeException('S2S unset cannot detach a native reference parameter');
				}
			}
			if ((Nullable_Conversions::payload($value->type) === null) && (Ownership_Conversions::role($value->type) === null)) {
				throw new \RuntimeException('S2S whole-variable unset requires nullable storage or an ownership handle; use clean for values');
			}
		}
		elseif (!self::supports_clean($value->type)) {
			throw new \RuntimeException('S2S clean requires a supported scalar, vector, hash, mixed or dynamic storage type');
		}
		return new prepared_storage_reset($value->type);
	}

	/** Exact registered built-ins opt in; neither indexing nor arbitrary default construction grants cleanup. */
	private static function supports_clean(canonical_type_use $type): bool
	{
		if ($type->by_value()) {
			return false;
		}
		if (Conversion_Preparation::is_scalar($type) || Boxed_Conversions::is_boxed($type)) {
			return true;
		}
		$definition = Type_Queries::canonical($type)->definition();
		return ($definition === Model::$type_catalog->definition('vector'))
			|| ($definition === Model::$type_catalog->definition('hash'));
	}
}
