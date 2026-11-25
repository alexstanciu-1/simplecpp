<?php
declare(strict_types=1);

/*
 * Role: Bind global type/value names without requiring concrete type preparation.
 * Used by: Resolution_Worker; Resolution_Validity
 * Call map: Declaration_Lookup::find() -> Symbol_Store / Type_Catalog lookups
 */
namespace resolve_symbols;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_kind;
use type_model\Type_Catalog;

/** Stateless lookup against fixed declaration inputs; local parameter/constant scopes precede this lookup. */
final class Declaration_Lookup
{
    /** Return an exact declaration reference, or null; no canonical IDs, layouts or shared writes. */
    public static function find(Symbol_Store $symbols, Type_Catalog $catalog, string $namespace,
        string $name, int $node, name_role $role): ?name_binding
    {
        if ($role === name_role::value) {
            $id = $symbols->find_symbol($name, $namespace, symbol_kind::constant_symbol);
            return $id === 0 ? null : new name_binding($node, $role, reference_kind::project_constant, $id);
        }
        if ($role === name_role::type_family) {
            $id = $symbols->find_symbol($name, $namespace, symbol_kind::template_struct);
            return $id === 0 ? null : new name_binding($node, $role, reference_kind::template_type, $id);
        }
        $provided = $catalog->find_type($name, $namespace);
        if ($provided !== null) {
            return new name_binding($node, $role, reference_kind::provided_type, $provided);
        }
        $record = $catalog->find_record($name, $namespace);
        if ($record !== null) {
            return new name_binding($node, $role, reference_kind::provided_record, $record);
        }
        $id = $symbols->find_symbol($name, $namespace, symbol_kind::struct_symbol);
        return $id === 0 ? null : new name_binding($node, $role, reference_kind::source_type, $id);
    }

    /** Compare the semantic target, retaining exact provider input identity rather than spelling alone. */
    public static function same(?name_binding $left, name_binding $right): bool
    {
        return ($left !== null) && ($left->role === $right->role)
            && ($left->kind === $right->kind) && ($left->target === $right->target);
    }
}
