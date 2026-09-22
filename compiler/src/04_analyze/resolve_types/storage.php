<?php
declare(strict_types=1);

/*
 * Role: Materialize concrete storage from an accepted family and element argument.
 * Used by: instance acceptance and signature preparation
 * Call map: Storage_Definitions::materialize(); name(); signature()
 * Writes: only the accepting join's private canonical type store.
 */
namespace resolve_types;

final class Storage_Definitions
{
    /** Exact complete identity; only the containing type-store lineage interprets resulting canonical IDs. */
    public static function name(\type_model\storage_family $family, \type_model\named_type_definition $element): string
    {
        return json_encode([$family->provider, $family->id, $element->namespace_name, $element->name], JSON_THROW_ON_ERROR);
    }

    /** Reuse accepted types, or materialize a noncopyable descriptor with a static element dependency. */
    public static function materialize(\type_model\storage_family $family, \type_model\named_type_definition $element,
        \type_model\Type_Store $types): \type_model\named_type_definition
    {
        $name = self::name($family, $element);
        $namespace = "\0element_storage";
        $known = $types->find_type($name, $namespace);
        if ($known !== 0) {
            $definition = $types->definition_for_type($known);
            if (($definition->element_storage?->family !== $family) || ($definition->element_storage->element !== $element)) {
                throw new \LogicException('Stale concrete storage definition');
            }
            return $definition;
        }
        if (!self::eligible($element)) {
            throw new \LogicException('Unvalidated storage element reached type acceptance');
        }
        $element_id = Type_Cache::materialize($types, $element);
        $descriptor = $family->descriptor;
        $definition = new \type_model\named_type_definition($name, $namespace, $descriptor->representation,
            $descriptor->lifetime, struct_field: $descriptor->struct_field, resource: \type_model\resource_kind::allocation,
            element_storage: new \type_model\element_storage($family, $element_id, $element));
        Type_Cache::materialize($types, $definition);
        return $definition;
    }

    /** Admit implemented copies/cleanup, excluding dynamically indexed compiler-tracked allocation owners. */
    public static function eligible(\type_model\named_type_definition $element): bool
    {
        return ($element->resource === null) && ($element->resource_paths === [])
            && in_array($element->lifetime?->copy, [\type_model\copy_kind::value, \type_model\copy_kind::construct], true)
            && in_array($element->lifetime?->cleanup, [\type_model\cleanup_kind::none, \type_model\cleanup_kind::destroy], true)
            && in_array($element->representation->kind,
                [\type_model\representation_kind::integer, \type_model\representation_kind::structure, \type_model\representation_kind::opaque_inline], true);
    }

    /** Derive semantic signatures from the concrete element and declared operation role, without native ABI guesses. */
    public static function signature(\instantiate\instance_context $instance, \type_model\Type_Catalog|Definition_View $catalog): signature_request
    {
        $function = $instance->definition->external;
        $family = $function->family;
        $element = $instance->arguments[0]->type;
        $owner = $catalog->find_type(self::name($family, $element), "\0element_storage")
            ?? throw new \LogicException('Storage callable requires its prepared concrete owner');
        $role = $function->role;
        $parameters = [$owner];
        $passing = [$role === \type_model\storage_role::count
            ? \type_model\argument_passing::borrow_const : \type_model\argument_passing::borrow_mutable];
        if ($role === \type_model\storage_role::allocate) {
            $parameters[] = $family->counter;
            $passing[] = \type_model\argument_passing::value;
        }
        elseif ($role === \type_model\storage_role::push) {
            $parameters[] = $element;
            $passing[] = self::element_passing($element);
        }
        elseif ($role === \type_model\storage_role::transfer) {
            $parameters[] = $owner;
            $passing[] = \type_model\argument_passing::borrow_mutable;
        }
        return new signature_request($instance->definition, 0,
            $role === \type_model\storage_role::count ? $family->counter : $family->void,
            $parameters, $instance, $passing);
    }
    /** Validate exact accepted argument/owner provenance and the role contract without resolving a second request. */
    public static function matches(signature_request $request, \type_model\Type_Catalog|Definition_View $catalog): bool
    {
        $function = $request->symbol->external;
        $family = $function->family;
        $role = $function->role;
        $element = $request->instance->arguments[0]->type;
        $owner = $request->parameter_definitions[0] ?? null;
        if (($request->return_annotation_id !== 0) || ($owner?->element_storage?->family !== $family)
            || ($owner->element_storage->element !== $element)
            || ($catalog->find_type($owner->name, $owner->namespace_name) !== $owner)
            || ($request->definition !== ($role === \type_model\storage_role::count ? $family->counter : $family->void))) {
            return false;
        }
        $parameters = match ($role) {
            \type_model\storage_role::allocate => [$owner, $family->counter],
            \type_model\storage_role::push => [$owner, $element],
            \type_model\storage_role::transfer => [$owner, $owner],
            default => [$owner],
        };
        if (($request->parameter_definitions !== $parameters) || (count($request->parameter_passing) !== count($parameters))) {
            return false;
        }
        foreach ($parameters as $index => $parameter)
        {
            $expected = $parameter === $owner
                ? ($role === \type_model\storage_role::count ? \type_model\argument_passing::borrow_const : \type_model\argument_passing::borrow_mutable)
                : self::element_passing($parameter);
            if (($request->parameter_passing[$index] ?? null) !== $expected) {
                return false;
            }
        }
        return true;
    }

    /** Scalars travel directly; object inputs borrow live storage for the selected copy operation. */
    private static function element_passing(\type_model\named_type_definition $element): \type_model\argument_passing
    {
        return $element->representation->kind === \type_model\representation_kind::integer
            ? \type_model\argument_passing::value : \type_model\argument_passing::borrow_const;
    }
}
