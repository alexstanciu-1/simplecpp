<?php
declare(strict_types=1);

/*
 * Role: Extract one supported declaration.
 * Used by: File_Collector (private trait methods on this owner)
 * Call map:
 *   collect_declaration()
 *     -> collect_function() / Syntax_Access::struct_parts()
 */

namespace collect_symbols;

use parse\File_Frontend;
use parse\syntax_kind;
use parse\Syntax_Access;

/**
 * @compiler-internal File_Collector's stateless declaration handlers.
 * Read one definition from a fixed frontend and return facts awaiting join;
 * handlers never assign project IDs or write symbol indexes.
 */
trait Declaration_Collection
{
    /** Extract source declaration identity while keeping record definitions separate from executable bodies. */
    private static function collect_declaration(File_Frontend $file, int $id): declaration
    {
        $node = $file->syntax->nodes[$id - 1];
        if ($node->kind === syntax_kind::template_declaration)
        {
            $inner = Syntax_Access::underlying_declaration($file->syntax, $id);
            $declaration = self::collect_declaration($file, $inner);
            $kind = $declaration->kind === symbol_kind::struct_symbol
                ? symbol_kind::template_struct : symbol_kind::template_function;
            return new declaration($kind, $declaration->name, $id, $declaration->body_node_id,
                template_parameters_node_id: Syntax_Access::template_parts($file->syntax, $id)->parameters_id);
        }
        if ($node->kind === syntax_kind::constant_declaration) {
            $parts = Syntax_Access::constant_parts($file->syntax, $id);
            return new declaration(symbol_kind::constant_symbol,
                Declaration_Syntax::name_text($file, $parts->name_id), $id, 0);
        }
        if ($node->kind === syntax_kind::function_declaration) {
            return self::collect_function($file, $id);
        }
        if ($node->kind === syntax_kind::struct_declaration) {
            $parts = Syntax_Access::struct_parts($file->syntax, $id);
            return new declaration(symbol_kind::struct_symbol,
                Declaration_Syntax::name_text($file, $parts->name_id), $id, 0);
        }
        $source = $file->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path,
            $node->start, $node->length, 'Unsupported declaration kind: ' . $node->kind->name);
    }

    private static function collect_function(File_Frontend $file, int $id): declaration
    {
        $parts = Syntax_Access::function_parts($file->syntax, $id);
        return new declaration(symbol_kind::function_symbol,
            Declaration_Syntax::name_text($file, $parts->name_id), $id, $parts->body_id);
    }
}
