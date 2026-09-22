<?php
declare(strict_types=1);
namespace resolve_types;

/** Selected source entry, before catalog return-type binding. Own the exact symbol snapshot. */
final class Entry_Selection {
    public bool $valid = false;
    public string $error_path = '';
    public int $error_start = 0;
    public int $error_length = 0;
    public string $error_reason = '';
    public function __construct(
        private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly int $selected_id,
    ) {
        if ($selected_id === 0) { return; }
        $entry = $symbols->symbol_by_id($selected_id);
        if ((int)$entry->declaration->kind !== \collect_symbols\SYMBOL_FILE_ENTRY) {
            throw new \LogicException('Selected entry must be a file entry');
        }
        $this->valid = true;
    }
    /** Failure cannot expose a partially selected callable. */
    public function symbol(): \collect_symbols\Symbol_Record {
        if (!$this->valid) { throw new \LogicException('Entry selection failed'); }
        return $this->symbols->symbol_by_id($this->selected_id);
    }
}
