<?php
declare(strict_types=1);

/*
 * Role: Classify supported concrete result ownership independently of physical ABI.
 * Call map: Type_Store::intern_signature() -> Result_Contracts::production()
 * Output: shared semantic result mode for source and imported call consumers.
 */
namespace type_model;

final class Result_Contracts
{
    /** Objects produce a new owned destination; scalar values and void retain direct semantics. */
    public static function production(representation_kind $kind): result_production
    {
        return match ($kind) {
            representation_kind::void_type => result_production::none,
            representation_kind::structure, representation_kind::opaque_inline => result_production::owned,
            default => result_production::value,
        };
    }
}
