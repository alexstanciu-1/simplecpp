<?php
declare(strict_types=1);

/*
 * Role: Collect declarations from one fixed frontend.
 * Used by: Declaration_Collector::run()
 * Call map: File_Collector::collect_file() -> [action] collect declarations with Declaration_Collection
 */

namespace collect_symbols;

use parse\File_Frontend;
use parse\Frontend_Set;
use read_sources\Source_Set;
use read_sources\file_change;

/**
 * @compiler-api Project declaration process used by compile. File workers extract syntax facts;
 * the coordinator alone matches/allocates project IDs and creates a candidate index.
 * Collection returns uncompared matched pairs; Symbol_Comparer completes their facts.
 */
class File_Collector
{
    use Declaration_Collection;

    /**
     * @compiler-api Read one File_Frontend and return private declaration facts awaiting join.
     * Includes the implicit file entry; unsupported definitions throw Source_Error.
     */
    public static function collect_file(File_Frontend $file): File_Declarations
    {
        $rows = [new declaration(symbol_kind::file_entry, '', 0, $file->entry_body_id)];
        foreach ($file->defined_entities as $id)
        {
            $declaration = self::collect_declaration($file, $id);
            $rows[] = $declaration;
            if (in_array($declaration->kind, [symbol_kind::struct_symbol, symbol_kind::template_struct], true))
            {
                $member_cursor = \parse\Syntax_Access::struct_members($file->syntax, $id, \parse\syntax_kind::method_declaration);
                while ($member_cursor->advance())
                {
                    $member = $member_cursor->current();
                    $method = self::collect_declaration($file, \parse\Syntax_Access::underlying_declaration($file->syntax, $member));
                    $rows[] = new declaration($declaration->kind === symbol_kind::template_struct ? symbol_kind::template_function : symbol_kind::function_symbol,
                        $method->name, $member, $method->body_node_id, $id, $declaration->template_parameters_node_id,
                        \parse\Syntax_Access::const_receiver($file->syntax, $member));
                }
            }
        }
        return new File_Declarations($file, $rows);
    }
}
