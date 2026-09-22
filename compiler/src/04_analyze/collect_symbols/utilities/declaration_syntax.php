<?php
declare(strict_types=1);

/*
 * Role: Expose declaration names and definition boundaries.
 * Used by: Collection and symbol comparison
 * Call map:
 *   Declaration_Syntax::definition_nodes()
 *     -> Syntax_Access::function_parts()
 */

namespace collect_symbols;

use parse\File_Frontend;
use parse\Syntax_Access;
use parse\syntax_node;

// Collection-owned names, diagnostic anchors and definition boundaries.
/**
 * @compiler-internal Collection-owned policy for definition boundaries; other steps use their own
 * semantic contracts or parse structural access, not this collection utility.
 */
class Declaration_Syntax
{
    /** @compiler-internal Read spelling from a name ID validated by the declaration's structural accessor. */
    public static function name_text(File_Frontend $file, int $name_id): string
    {
        $name = $file->syntax->nodes[$name_id - 1];
        return substr($file->tokens->source->content, $name->start, $name->length);
    }

    /** @compiler-internal Read the named declaration's diagnostic anchor; the returned syntax row is read-only. */
    public static function name_anchor(symbol_record $symbol): syntax_node
    {
        $id = Syntax_Access::underlying_declaration($symbol->frontend->syntax, $symbol->declaration_node_id);
        if ($symbol->kind === symbol_kind::constant_symbol) {
            $parts = Syntax_Access::constant_parts($symbol->frontend->syntax, $id);
            return $symbol->frontend->syntax->nodes[$parts->name_id - 1];
        }
        if (in_array($symbol->kind, [symbol_kind::struct_symbol, symbol_kind::template_struct], true)) {
            $parts = Syntax_Access::struct_parts($symbol->frontend->syntax, $id);
            return $symbol->frontend->syntax->nodes[$parts->name_id - 1];
        }
        if (in_array($symbol->kind, [symbol_kind::function_symbol, symbol_kind::template_function], true)) {
            $parts = Syntax_Access::function_parts($symbol->frontend->syntax, $id);
            return $symbol->frontend->syntax->nodes[$parts->name_id - 1];
        }
        throw new \LogicException('Unsupported named declaration anchor: ' . $symbol->kind->name);
    }

    /**
     * @compiler-internal Read definition-contributing AST roots for comparison, excluding executable body.
     * @return list<int> Syntax contributing to the symbol's own definition.
     */
    public static function definition_nodes(symbol_record $symbol): array
    {
        $tree = $symbol->frontend->syntax;
        if (in_array($symbol->kind, [symbol_kind::struct_symbol, symbol_kind::template_struct], true))
        {
            $parts = Syntax_Access::struct_parts($tree, Syntax_Access::underlying_declaration($tree, $symbol->declaration_node_id));
            $roots = $symbol->template_parameters_node_id === 0 ? [] : [$symbol->template_parameters_node_id];
            $roots[] = $parts->name_id;
            foreach (Syntax_Access::struct_members($tree, $symbol->declaration_node_id, \parse\syntax_kind::field_declaration) as $field) {
                $roots[] = $field;
            }
            return $roots;
        }
        if ($symbol->owner_symbol_id !== 0)
        {
            $function = Syntax_Access::underlying_declaration($tree, $symbol->declaration_node_id);
            $parts = Syntax_Access::function_parts($tree, $function);
            $roots = [$parts->name_id, $parts->parameters_id, $parts->return_type_id];
            if ($symbol->template_parameters_node_id !== 0) {
                $roots[] = $symbol->template_parameters_node_id;
            }
            if ($tree->nodes[$function - 1]->next_sibling_id !== 0) {
                $roots[] = $tree->nodes[$function - 1]->next_sibling_id;
            }
            return $roots;
        }
        if ($symbol->is_template() || in_array($symbol->kind, [symbol_kind::struct_symbol, symbol_kind::constant_symbol], true)) {
            return [$symbol->declaration_node_id];
        }
        if ($symbol->kind === symbol_kind::file_entry) {
            return [];
        }
        if ($symbol->kind !== symbol_kind::function_symbol) {
            throw new \LogicException('Unsupported definition comparison: ' . $symbol->kind->name);
        }
        $parts = Syntax_Access::function_parts($symbol->frontend->syntax, $symbol->declaration_node_id);
        if ($parts->body_id !== $symbol->body_node_id) {
            throw new \LogicException('Symbol body does not match declaration syntax');
        }
        return [$parts->name_id, $parts->parameters_id, $parts->return_type_id];
    }
}
