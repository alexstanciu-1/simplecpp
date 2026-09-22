<?php
declare(strict_types=1);

/*
 * Role: Prepare candidate type caches and materialize requested definitions.
 * Used by: Compiler_Session; type joins
 * Call map: Type_Cache::requires_rebuild(); prepare(); materialize()
 */

namespace resolve_types;

use type_model\Type_Store;
use type_model\representation_kind;
use type_model\type_context;

// Candidate cache preparation and coordinator-only materialization.
/**
 * @compiler-api Named-type cache preparation for compile and materialization for type joins.
 * Workers only request definitions; candidate mutation is completed before downstream
 * semantic readers start. Canonical IDs belong to a cache lineage, not globally.
 */
class Type_Cache
{
    /**
     * @compiler-api Read-only cache validity query before frontend selection.
     * The caller owns the global rebuild decision; this process never edits it.
     */
    public static function requires_rebuild(?Type_Store $previous, type_context $context): bool
    {
        return ($previous === null) || (!self::same_context($previous->context, $context));
    }

    private static function same_context(type_context $left, type_context $right): bool
    {
        return ($left->configuration_key === $right->configuration_key)
            && ($left->provider_key === $right->provider_key)
            && ($left->target_key === $right->target_key);
    }

    /**
     * @compiler-api Prepare a private candidate after the coordinator fixes full_rebuild.
     * Return an empty lineage on full or clone the valid previous owner. Reject a
     * selective request if its cache requires a rebuild; never alter caller control state.
     */
    public static function prepare(?Type_Store $previous, type_context $context,
        bool $full_rebuild): Type_Store
    {
        // Empty cache means every encountered type needs work through the same
        // resolution path. No per-row marking or separate full-build resolver.
        // IDs from the previous lineage must not be reused in this new cache.
        if ($full_rebuild) {
            return new Type_Store($context);
        }
        if (self::requires_rebuild($previous, $context)) {
            throw new \LogicException('Type cache requires a full rebuild');
        }
        return clone $previous;
    }

    // Coordinator-only materialization. A known type is completed once; repeated
    // annotations use its ID without reconstructing the representation.
    /**
     * @compiler-api Coordinator-only: complete/share a named definition in the private candidate and
     * return its canonical type ID. Repeated encounters reuse it. May mutate before
     * throwing on unsupported contracts; discard failed candidates, never use from workers.
     */
    public static function materialize(Type_Store $types, \type_model\named_type_definition|\type_model\array_type_definition $definition): int
    {
        if ($definition instanceof \type_model\array_type_definition) {
            return self::array_type($types, $definition);
        }
        $id = $types->reference_type($definition->name, $definition->namespace_name);
        if (!$types->is_declared($id)) {
            $types->declare_type($definition->name, $definition->namespace_name);
        }
        $types->bind_definition($id, $definition);
        if ($types->needs_representation($id))
        {
            $shape = $definition->representation;
            $representation_id = match ($shape->kind)
            {
                representation_kind::void_type => $types->intern_void(),
                representation_kind::byte_span => $types->intern_byte_span(),
                representation_kind::integer => $types->intern_integer($shape->payload->bit_width),
                representation_kind::opaque_inline => $types->intern_opaque($shape->payload),
                representation_kind::floating_point => $types->intern_float($shape->payload->format),
                default => throw new \LogicException('Unsupported materialization contract'),
            };
            $types->set_representation($id, $representation_id);
        }
        return $id;
    }

    /** Intern an exact element/count identity in this candidate's lineage; never publish worker-local IDs. */
    private static function array_type(Type_Store $types, \type_model\array_type_definition $recipe): int
    {
        $element = self::materialize($types, $recipe->element);
        $name = 'array_' . $element . '_' . $recipe->count;
        $namespace = '@compiler/array';
        $known = $types->find_type($name, $namespace);
        if ($known !== 0) {
            return $known;
        }
        $shape = $types->intern_array($element, $recipe->count);
        $id = $types->declare_type($name, $namespace);
        $definition = new \type_model\named_type_definition($name, $namespace,
            $types->representation_by_id($shape), Lifecycle_Composition::derive($types, $id, [$element], $recipe->count), struct_field: $recipe->struct_field);
        $types->bind_definition($id, $definition);
        $types->set_representation($id, $shape);
        return $id;
    }

}
