<?php
declare(strict_types=1);
namespace resolve_types;

/** Exact language entry/default definition pair; native exit width and ABI remain separate. */
final class Entry_Contract {
    public function __construct(public readonly \collect_symbols\Symbol_Record $symbol,
        public readonly \type_model\Named_Definition $return_type) {
        if ((int)$symbol->declaration->kind !== \collect_symbols\SYMBOL_FILE_ENTRY) { throw new \LogicException('Entry contract requires a file entry'); }
        if ($return_type->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { throw new \LogicException('Entry contract requires an integer definition'); }
    }
    public static function bind(Entry_Selection $selection, \type_model\Type_Catalog $catalog): Entry_Contract {
        return new Entry_Contract($selection->symbol(),$catalog->entry_return_type);
    }
}
