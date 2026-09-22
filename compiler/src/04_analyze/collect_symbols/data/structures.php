<?php
declare(strict_types=1);
namespace collect_symbols;

const SYMBOL_FUNCTION = 1;
const SYMBOL_FILE_ENTRY = 2;
const SYMBOL_STRUCT = 3;
const SYMBOL_TEMPLATE_STRUCT = 4;
const SYMBOL_TEMPLATE_FUNCTION = 5;
const SYMBOL_CONSTANT = 6;
const MAX_SYMBOL_ID = 4294967295;
const CHANGE_ADDED = 1;
const CHANGE_REMOVED = 3;
const CHANGE_UNCOMPARED = 4;

/** File-local syntax facts. Names remain in the retained source until indexing. */
/** @scpp-struct */
final class Declaration_Fact {
    public int $kind /** uint32 */ = 0;
    public int $name_node_id /** uint32 */ = 0;
    public int $declaration_node_id /** uint32 */ = 0;
    public int $body_node_id /** uint32 */ = 0;
    public int $owner_declaration_node_id /** uint32 */ = 0;
    public int $template_parameters_node_id /** uint32 */ = 0;
    public bool $receiver_const = false;
}

/** Source-backed semantic identity; published records and their frontend are immutable. */
final class Symbol_Record {
    public function __construct(
        public readonly int $symbol_id,
        public readonly int $owner_symbol_id,
        public readonly string $name,
        public readonly \parse\Parse_Result $frontend,
        public readonly Declaration_Fact $declaration,
    ) {}
    public function is_template(): bool {
        return ((int)$this->declaration->kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT)
            || ((int)$this->declaration->kind === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION);
    }
    public function has_executable_body(): bool {
        if ($this->is_template()) { return false; }
        return (int)$this->declaration->body_node_id !== 0;
    }
}

/** Status selects presence in the refresh's previous/current stores; no comparison yet. */
/** @scpp-struct */
final class Symbol_Change {
    public int $symbol_id /** uint32 */ = 0;
    public int $status /** uint32 */ = 0;
}
