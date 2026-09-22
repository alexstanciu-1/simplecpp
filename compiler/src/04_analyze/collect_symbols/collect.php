<?php
declare(strict_types=1);
namespace collect_symbols;

/** Extract private facts in source order, without assigning project identities. */
final class File_Collector {
    public static function name_text(\parse\Parse_Result $file, int $id): string {
        if ($id === 0) { return ''; }
        $row = $file->tree->row($id);
        return string_byte_slice($file->tokens->source->content, (int)$row->start, (int)$row->length);
    }
    private static function declaration(\parse\Parse_Result $file, int $id): Declaration_Fact {
        $out = new Declaration_Fact();
        $out->declaration_node_id = $id;
        $kind = (int)$file->tree->row($id)->kind;
        if ($kind === \parse\SYNTAX_TEMPLATE_DECLARATION) {
            $inner = \parse\Syntax_Access::underlying_declaration($file->tree, $id);
            $base = File_Collector::declaration($file, $inner);
            if ((int)$base->kind === 0) { return $out; }
            $out->kind = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION;
            if ((int)$base->kind === \collect_symbols\SYMBOL_STRUCT) { $out->kind = \collect_symbols\SYMBOL_TEMPLATE_STRUCT; }
            $out->name_node_id = $base->name_node_id;
            $out->body_node_id = $base->body_node_id;
            $out->template_parameters_node_id = \parse\Syntax_Access::template_parts($file->tree, $id)->parameters_id;
        } else if ($kind === \parse\SYNTAX_FUNCTION_DECLARATION) {
            $parts = \parse\Syntax_Access::function_parts($file->tree, $id);
            $out->kind = \collect_symbols\SYMBOL_FUNCTION;
            $out->name_node_id = $parts->name_id;
            $out->body_node_id = $parts->body_id;
        } else if ($kind === \parse\SYNTAX_STRUCT_DECLARATION) {
            $parts = \parse\Syntax_Access::struct_parts($file->tree, $id);
            $out->kind = \collect_symbols\SYMBOL_STRUCT;
            $out->name_node_id = $parts->name_id;
        } else if ($kind === \parse\SYNTAX_CONSTANT_DECLARATION) {
            $parts = \parse\Syntax_Access::constant_parts($file->tree, $id);
            $out->kind = \collect_symbols\SYMBOL_CONSTANT;
            $out->name_node_id = $parts->name_id;
        }
        return $out;
    }
    public static function collect_file(\parse\Parse_Result $file): File_Declarations {
        \parse\Frontend_Set::require_file($file);
        $result = new File_Declarations($file);
        $entry = new Declaration_Fact();
        $entry->kind = \collect_symbols\SYMBOL_FILE_ENTRY;
        $entry->body_node_id = $file->entry;
        $result->rows[] = $entry;
        foreach ($file->definitions as $id) {
            $fact = File_Collector::declaration($file, $id);
            if ((int)$fact->kind === 0) {
                $node = $file->tree->row($id);
                $result->valid = false;
                $result->error_start = (int)$node->start;
                $result->error_length = (int)$node->length;
                $result->error_reason = 'Unsupported declaration kind: ' . (int)$node->kind;
                $empty /** vector<Declaration_Fact> */ = [];
                $result->rows = $empty;
                return $result;
            }
            $result->rows[] = $fact;
            if (((int)$fact->kind === \collect_symbols\SYMBOL_STRUCT) || ((int)$fact->kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) {
                $members = \parse\Syntax_Access::struct_members($file->tree, $id, \parse\SYNTAX_METHOD_DECLARATION);
                while ($members->advance()) {
                    $member = $members->current();
                    $method = File_Collector::declaration($file, \parse\Syntax_Access::underlying_declaration($file->tree, $member));
                    $method->declaration_node_id = $member;
                    $method->owner_declaration_node_id = $id;
                    $method->template_parameters_node_id = $fact->template_parameters_node_id;
                    $method->receiver_const = \parse\Syntax_Access::const_receiver($file->tree, $member);
                    if ((int)$fact->kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) { $method->kind = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
                    $result->rows[] = $method;
                }
            }
        }
        return $result;
    }
}
