<?php
declare(strict_types=1);
namespace resolve_symbols;

/** Exact source spelling, global function namespace; member dispatch belongs to type checking. */
final class Function_Lookup {
    public static function find(\collect_symbols\Symbol_Record $owner, int $name_id, \collect_symbols\Symbol_Store $symbols): int {
        $row = $owner->frontend->tree->row($name_id);
        if ((int)$row->kind !== \parse\SYNTAX_NAME) { throw new \InvalidArgumentException('Function lookup requires a name node'); }
        $text = \collect_symbols\File_Collector::name_text($owner->frontend, $name_id);
        $id = $symbols->find_symbol($text, \collect_symbols\SYMBOL_FUNCTION, 0);
        if ($id !== 0) { return $id; }
        return $symbols->find_symbol($text, \collect_symbols\SYMBOL_TEMPLATE_FUNCTION, 0);
    }
}
