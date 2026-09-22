<?php
declare(strict_types=1);

/*
 * Role: Select conversions by canonical types and semantic purpose.
 * Used by: Body_Worker implicit boundaries and named conversion calls
 * Call map: Conversion_Resolver::resolve()
 *   -> [action] select identity, an integer-family primitive or an exact provider operation
 */
namespace check_bodies;

use type_model\conversion_purpose;
use resolve_types\Type_Resolution;
use type_model\representation_kind;

/** @compiler-internal Single conversion policy owner; fixed inputs, no type materialization or mutable cache. */
final class Conversion_Resolver
{
    /** Select one supported contract; null means unsupported, never an invitation to search conversion chains. */
    public static function resolve(Type_Resolution $context, conversion_request $request): ?conversion_selection
    {
        $types = $context->types;
        $source = $types->definition_for_type($request->source_type);
        $destination = $types->definition_for_type($request->destination_type);
        if (($source->representation->kind === representation_kind::void_type)
            || ($destination->representation->kind === representation_kind::void_type)
            || ($request->purpose === conversion_purpose::condition)) {
            return null; // Condition truthiness keeps its existing owner until its contracts are implemented here.
        }
        if ($request->source_type === $request->destination_type) {
            return new conversion_selection(conversion_form::identity);
        }

        // Only implicit value boundaries currently grant the established lossless family rule.
        if ($request->purpose === conversion_purpose::implicit_boundary)
        {
            if (($source->integer_family !== null) && ($source->integer_family === $destination->integer_family)
                && ($source->signed === $destination->signed)
                && ($source->representation->payload->bit_width < $destination->representation->payload->bit_width)) {
                return new conversion_selection(conversion_form::primitive, conversion_kind::integer_widen);
            }
            return null;
        }

        // Explicit casts and text conversion are separate permissions even when they share a destination.
        $target = $context->conversion_callable($request->purpose, $request->source_type, $request->destination_type);
        return $target === 0 ? null : new conversion_selection(conversion_form::provider_call, callable_id: $target);
    }
}
