<?php
declare(strict_types=1);
namespace check_bodies;
/** Single conversion policy owner; fixed inputs and no type materialization. */
final class Conversion_Resolver {
    public static function resolve(\resolve_types\Type_Resolution $context, Conversion_Request $request): ?Conversion_Selection {
        $source = $context->types->definition_for_type($request->source_type);
        $destination = $context->types->definition_for_type($request->destination_type);
        if (($source->representation->kind() === \type_model\REPRESENTATION_VOID)
            || ($destination->representation->kind() === \type_model\REPRESENTATION_VOID)
            || ($request->purpose === \type_model\CONVERSION_CONDITION)) { return null; }
        if ($request->source_type === $request->destination_type) { return new Conversion_Selection(\check_bodies\CONVERSION_IDENTITY); }
        if ($request->purpose === \type_model\CONVERSION_IMPLICIT) {
            if (($source->integer_family !== '') && ($source->integer_family === $destination->integer_family)
                && ($source->signed === $destination->signed)) {
                if ($source->representation->bit_width() < $destination->representation->bit_width()) {
                    return new Conversion_Selection(\check_bodies\CONVERSION_PRIMITIVE,\check_bodies\CONVERSION_INTEGER_WIDEN);
                }
            }
            return null;
        }
        $target = $context->conversion_callable($request->purpose,$request->source_type,$request->destination_type);
        if ($target === 0) { return null; }
        return new Conversion_Selection(\check_bodies\CONVERSION_PROVIDER_CALL,0,$target);
    }
}
