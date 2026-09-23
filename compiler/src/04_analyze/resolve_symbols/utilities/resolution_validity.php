<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Recheck dependency identity without walking an accepted body's syntax again. */
final class Resolution_Validity {
    public static function is_current(Symbol_Resolution $result, \collect_symbols\Symbol_Record $owner,
        \collect_symbols\Symbol_Store $symbols, \type_model\Type_Catalog $catalog): bool {
        if (!$owner->is_source()) { return false; }
        // Exact ownership also preserves current source metadata and diagnostic paths.
        if ($result->owner !== $owner) { return false; }
        if (!$symbols->contains($owner->symbol_id)) { return false; }
        if ($symbols->symbol_by_id($owner->symbol_id) !== $owner) { return false; }
        $kind = $owner->kind();
        if (($kind === \collect_symbols\SYMBOL_STRUCT) || ($kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) {
            if ($catalog->find_type($owner->name,'') !== null) { return false; }
        }
        for ($index = 0; $index < $result->calls_count(); $index++) {
            $binding = $result->calls_at($index); $node = (int)$binding->use_node_id;
            if (Function_Lookup::find($owner,$node,$symbols) !== (int)$binding->target_symbol_id) { return false; }
            // A newly added constant changes callability even when the function ID survives.
            $text = \collect_symbols\File_Collector::name_text($owner->source_frontend(),$node);
            if ($symbols->find_symbol($text,\collect_symbols\SYMBOL_CONSTANT,0,'') !== 0) { return false; }
        }
        for ($index = 0; $index < $result->applications_count(); $index++) {
            $definition = $result->applications_at($index)->definition;
            if (!$symbols->contains($definition->symbol_id)) { return false; }
            if ($symbols->symbol_by_id($definition->symbol_id) !== $definition) { return false; }
        }
        $tree = $owner->source_frontend()->tree; $parameter = 0;
        if ($owner->is_template()) { $parameter = (int)$tree->row((int)$owner->source_fact()->template_parameters_node_id)->first_child; }
        for ($index = 0; $index < $result->parameters_count(); $index++) {
            $row = $result->parameters_at($index);
            if ((int)$row->declaration_node_id !== $parameter) { return false; }
            $parameter = (int)$tree->row($parameter)->next_sibling;
        }
        if ($parameter !== 0) { return false; }
        for ($index = 0; $index < $result->names_count(); $index++) {
            $binding = $result->names_at($index);
            $text = \collect_symbols\File_Collector::name_text($owner->source_frontend(),$binding->use_node_id);
            if ($binding->kind === \resolve_symbols\REFERENCE_TEMPLATE_PARAMETER) {
                if ($binding->target_id >= $result->parameters_count()) { return false; }
                $row = $result->parameters_at($binding->target_id);
                if (\collect_symbols\File_Collector::name_text($owner->source_frontend(),(int)$row->name_node_id) !== $text) { return false; }
                if (($binding->role === \resolve_symbols\NAME_TYPE) !== ((int)$row->type_syntax_id === 0)) { return false; }
            } elseif ($binding->kind === \resolve_symbols\REFERENCE_LOCAL_CONSTANT) {
                if ($binding->target_id > $tree->size()) { return false; }
                $constant = $tree->row($binding->target_id);
                if ((int)$constant->kind !== \parse\SYNTAX_CONSTANT_DECLARATION) { return false; }
                if (\collect_symbols\File_Collector::name_text($owner->source_frontend(),(int)$constant->first_child) !== $text) { return false; }
            } else {
                if ($binding->kind === \resolve_symbols\REFERENCE_PROJECT_CONSTANT) { if ($binding->target_id === $owner->symbol_id) { return false; } }
                $current = Declaration_Lookup::find($symbols,$catalog,$text,$binding->use_node_id,$binding->role);
                if ($current === null) { return false; }
                if (!$current->same_target($binding)) { return false; }
            }
        }
        return true;
    }
}
