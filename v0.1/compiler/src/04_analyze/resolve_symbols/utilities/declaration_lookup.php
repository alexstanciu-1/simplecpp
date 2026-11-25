<?php
declare(strict_types=1);
namespace resolve_symbols;

/** Global source grammar lookup; lexical parameters/constants must be checked first. */
final class Declaration_Lookup {
    public static function find(\collect_symbols\Symbol_Store $symbols, \type_model\Type_Catalog $catalog,
        string $name, int $node, int $role, string $namespace_name): ?Name_Binding {
        if ($node < 1) { throw new \InvalidArgumentException('Lookup requires a source occurrence'); }
        $id = 0; $kind = 0;
        if ($role === \resolve_symbols\NAME_VALUE) {
            $id = $symbols->find_symbol($name, \collect_symbols\SYMBOL_CONSTANT, 0,$namespace_name);
            $kind = \resolve_symbols\REFERENCE_PROJECT_CONSTANT;
        } elseif ($role === \resolve_symbols\NAME_TYPE_FAMILY) {
            $id = $symbols->find_symbol($name, \collect_symbols\SYMBOL_TEMPLATE_STRUCT, 0,$namespace_name);
            $kind = \resolve_symbols\REFERENCE_TEMPLATE_TYPE;
        } elseif ($role === \resolve_symbols\NAME_TYPE) {
            $provided = $catalog->find_type($name,$namespace_name);
            if ($provided !== null) { return new Name_Binding($node, $role, \resolve_symbols\REFERENCE_PROVIDED_TYPE, 0, $provided); }
            $record = $catalog->find_record($name,$namespace_name);
            if ($record !== null) { return new Name_Binding($node,$role,\resolve_symbols\REFERENCE_PROVIDED_RECORD,0,null,$record); }
            $id = $symbols->find_symbol($name, \collect_symbols\SYMBOL_STRUCT, 0,$namespace_name);
            $kind = \resolve_symbols\REFERENCE_SOURCE_TYPE;
        } else { throw new \InvalidArgumentException('Invalid name lookup role'); }
        if ($id === 0) { return null; }
        return new Name_Binding($node, $role, $kind, $id, null);
    }
}
