<?php
declare(strict_types=1);

/*
 * Role: Check whether retained bindings still apply.
 * Used by: Symbol_Resolver::select(); Resolution_Join::join()
 * Call map:
 *   Resolution_Validity::is_current()
 *     -> Function_Lookup::find(); Declaration_Lookup::find(); [action] compare formal schemas
 */

namespace resolve_symbols;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;
use parse\syntax_kind;

/** @compiler-internal Shared name-binding validity for resolution selection and joining. */
class Resolution_Validity
{
    /**
     * Validate retained name bindings against current ownership and exact symbol lookup results.
     * This establishes no type, ABI or body reuse; current project function keys have no overload sets.
     */
    public static function is_current(?Symbol_Resolution $result, symbol_record $symbol, Symbol_Store $symbols, \type_model\Type_Catalog $catalog): bool
    {
        if (($result === null) || ($result->symbol_id !== $symbol->symbol_id) || ($result->syntax !== $symbol->frontend->syntax)) {
            return false;
        }
        if ((($result->scopes[0] ?? null)?->block_node_id ?? 0) !== $symbol->body_node_id) {
            return false;
        }

        // The immutable result already guarantees unique, positive binding IDs.
        // Reuse only needs to recheck its dependencies against the current index.
        foreach ($result->bindings as $binding) {
            $node = $result->syntax->nodes[$binding->use_node_id - 1] ?? null;
            if (($node?->kind !== syntax_kind::name)
                || (Function_Lookup::find($symbol, $binding->use_node_id, $symbols) !== $binding->target_symbol_id)) {
                return false;
            }
        }
        foreach ($result->applications as $application)
        {
            $definition = $application->definition;
            if (!$symbols->contains($definition->symbol_id)) {
                return false;
            }
            $current = $symbols->symbol_by_id($definition->symbol_id);
            if (($current->kind !== $definition->kind) || !$current->is_template()) {
                return false;
            }
            // Application rows retain their definition snapshot, not just its formal schema.
            // Rebind when that snapshot changes so instantiation never sees old AST anchors.
            if ($current !== $definition) {
                return false;
            }
        }
        return self::declarations_current($result, $symbol, $symbols, $catalog);
    }

    /** Recheck parameter identities and exact declaration dependencies against fixed inputs. */
    private static function declarations_current(Symbol_Resolution $result, symbol_record $owner,
        Symbol_Store $symbols, \type_model\Type_Catalog $catalog): bool
    {
        $tree = $result->syntax;
        $parameter = 0;
        if ($owner->is_template()) {
            $list = $owner->template_parameters_node_id;
            $parameter = $tree->nodes[$list - 1]->first_child_id;
        }
        foreach ($result->template_parameters as $row) {
            if ($row->declaration_node_id !== $parameter) {
                return false;
            }
            $parameter = $tree->nodes[$parameter - 1]->next_sibling_id;
        }
        if ($parameter !== 0) {
            return false;
        }
        foreach ($result->name_bindings as $binding)
        {
            $node = $tree->nodes[$binding->use_node_id - 1];
            $text = substr($owner->frontend->tokens->source->content, $node->start, $node->length);
            if ($binding->kind === reference_kind::template_parameter)
            {
                $parameter = $result->template_parameters[$binding->target] ?? null;
                if ($parameter === null) {
                    return false;
                }
                $name = $tree->nodes[$parameter->name_node_id - 1];
                if ((substr($owner->frontend->tokens->source->content, $name->start, $name->length) !== $text)
                    || (($binding->role === name_role::type) !== ($parameter->type_syntax_id === 0))) {
                    return false;
                }
            }
            elseif ($binding->kind === reference_kind::local_constant)
            {
                $constant = $tree->nodes[$binding->target - 1] ?? null;
                if ($constant?->kind !== syntax_kind::constant_declaration) {
                    return false;
                }
                $name = $tree->nodes[$constant->first_child_id - 1];
                if (($binding->role !== name_role::value)
                    || (substr($owner->frontend->tokens->source->content, $name->start, $name->length) !== $text)) {
                    return false;
                }
            }
            elseif (!Declaration_Lookup::same(Declaration_Lookup::find($symbols, $catalog,
                $owner->namespace_name, $text, $binding->use_node_id, $binding->role), $binding)) {
                return false;
            }
        }
        return true;
    }
}
