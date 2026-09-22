<?php
declare(strict_types=1);
namespace collect_symbols;

/** Dense storage with separate stable IDs and explicit name/file/owner indexes. */
final class Symbol_Store {
    private array $rows /** vector<Symbol_Record> */ = [];
    private array $by_id /** hash<int,int> */ = [];
    private array $by_name /** hash<int> */ = [];
    private array $by_file /** hash<vector<int>> */ = [];
    private array $by_owner /** hash<vector<int>,int> */ = [];
    private array $entries /** hash<int> */ = [];
    private int $next_id = 1;
    public function __construct(int $next_id) {
        if (($next_id < 1) || ($next_id > \collect_symbols\MAX_SYMBOL_ID + 1)) { throw new \LogicException('Invalid symbol watermark'); }
        $this->next_id = $next_id;
    }
    public function next_symbol_id(): int { return $this->next_id; }
    public function allocate_id(): int {
        if ($this->next_id > \collect_symbols\MAX_SYMBOL_ID) { throw new \RuntimeException('Symbol IDs exhausted'); }
        $id = $this->next_id;
        ++$this->next_id;
        return $id;
    }
    public function size(): int { return q_count($this->rows); }
    public function record_at(int $position): Symbol_Record {
        if (($position < 0) || ($position >= q_count($this->rows))) { throw new \LogicException('Invalid symbol position'); }
        return $this->rows[$position];
    }
    public function contains(int $id): bool { return isset($this->by_id[$id]); }
    public function symbol_by_id(int $id): Symbol_Record {
        if (!isset($this->by_id[$id])) { throw new \LogicException('Unknown symbol ID'); }
        return $this->rows[$this->by_id[$id]];
    }
    private static function name_key(string $name, int $kind, int $owner): string {
        return '' . $owner . ':' . $kind . ':' . $name;
    }
    public function find_symbol(string $name, int $kind, int $owner): int {
        $key = Symbol_Store::name_key($name, $kind, $owner);
        if (!isset($this->by_name[$key])) { return 0; }
        return $this->by_name[$key];
    }
    public function entry_symbol_id(string $path): int {
        if (!isset($this->entries[$path])) { return 0; }
        return $this->entries[$path];
    }
    public function file_symbol_ids(string $path): array /** vector<int> */ {
        $empty /** vector<int> */ = [];
        if (!isset($this->by_file[$path])) { return $empty; }
        return $this->by_file[$path];
    }
    public function child_symbol_ids(int $owner): array /** vector<int> */ {
        $empty /** vector<int> */ = [];
        if (!isset($this->by_owner[$owner])) { return $empty; }
        return $this->by_owner[$owner];
    }
    /** Function/template and struct/template counterparts occupy the same declaration category. */
    public function conflict(string $name, int $kind, int $owner): int {
        $found = $this->find_symbol($name, $kind, $owner);
        if ($found !== 0) { return $found; }
        $other = 0;
        if ($kind === \collect_symbols\SYMBOL_FUNCTION) { $other = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
        else if ($kind === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) { $other = \collect_symbols\SYMBOL_FUNCTION; }
        else if ($kind === \collect_symbols\SYMBOL_STRUCT) { $other = \collect_symbols\SYMBOL_TEMPLATE_STRUCT; }
        else if ($kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) { $other = \collect_symbols\SYMBOL_STRUCT; }
        if ($other === 0) { return 0; }
        return $this->find_symbol($name, $other, $owner);
    }
    /** Producer-only append; callers publish this store read-only after the candidate succeeds. */
    public function add(Symbol_Record $record): void {
        $id = $record->symbol_id;
        $owner = $record->owner_symbol_id;
        $fact = $record->declaration;
        $kind = (int)$fact->kind;
        $path = $record->frontend->tokens->source->path;
        if (($id < 1) || ($id >= $this->next_id) || ($path === '') || ($owner < 0)
            || ($kind < \collect_symbols\SYMBOL_FUNCTION) || ($kind > \collect_symbols\SYMBOL_CONSTANT)) { throw new \LogicException('Invalid source symbol'); }
        if ($this->contains($id)) { throw new \LogicException('Duplicate symbol ID'); }
        if ($owner !== 0) {
            $parent = $this->symbol_by_id($owner);
            $parent_kind = (int)$parent->declaration->kind;
            if (($parent_kind !== \collect_symbols\SYMBOL_STRUCT) && ($parent_kind !== \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) { throw new \LogicException('Invalid method owner'); }
            if ($parent->frontend !== $record->frontend) { throw new \LogicException('Stale method owner'); }
            if ((int)$fact->owner_declaration_node_id !== (int)$parent->declaration->declaration_node_id) { throw new \LogicException('Wrong method owner syntax'); }
        }
        if ($kind === \collect_symbols\SYMBOL_FILE_ENTRY) {
            if (($record->name !== '') || ($owner !== 0) || ((int)$fact->declaration_node_id !== 0)
                || ((int)$fact->body_node_id !== $record->frontend->entry) || isset($this->entries[$path])) { throw new \LogicException('Invalid file entry'); }
        } else {
            if (($record->name === '') || ((int)$fact->declaration_node_id === 0)) { throw new \LogicException('Invalid named declaration'); }
            if ($this->conflict($record->name, $kind, $owner) !== 0) { throw new \LogicException('Duplicate symbol name'); }
        }
        $this->by_id[$id] = q_count($this->rows);
        $this->rows[] = $record;
        if ($kind === \collect_symbols\SYMBOL_FILE_ENTRY) { $this->entries[$path] = $id; }
        else { $this->by_name[Symbol_Store::name_key($record->name, $kind, $owner)] = $id; }
        if (!isset($this->by_file[$path])) { $none /** vector<int> */ = []; $this->by_file[$path] = $none; }
        if (!isset($this->by_owner[$owner])) { $none /** vector<int> */ = []; $this->by_owner[$owner] = $none; }
        $this->by_file[$path][] = $id;
        $this->by_owner[$owner][] = $id;
    }
}
