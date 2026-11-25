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
    private static function name_key(string $name, int $kind, int $owner, string $namespace_name): string {
        return '' . $owner . ':' . $kind . ':' . string_byte_len($namespace_name) . ':' . $namespace_name . $name;
    }
    public function find_symbol(string $name, int $kind, int $owner, string $namespace_name): int {
        $key = Symbol_Store::name_key($name, $kind, $owner, $namespace_name);
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
    public function conflict(string $name, int $kind, int $owner, string $namespace_name): int {
        $found = $this->find_symbol($name, $kind, $owner, $namespace_name);
        if ($found !== 0) { return $found; }
        $other = 0;
        if ($kind === \collect_symbols\SYMBOL_FUNCTION) { $other = \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
        else if ($kind === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) { $other = \collect_symbols\SYMBOL_FUNCTION; }
        else if ($kind === \collect_symbols\SYMBOL_STRUCT) { $other = \collect_symbols\SYMBOL_TEMPLATE_STRUCT; }
        else if ($kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) { $other = \collect_symbols\SYMBOL_STRUCT; }
        if ($other === 0) { return 0; }
        return $this->find_symbol($name, $other, $owner, $namespace_name);
    }
    /** Producer-only append. Validate every association before touching any index. */
    public function add(Symbol_Record $record): void {
        $id = $record->symbol_id; $owner = $record->owner_symbol_id; $kind = $record->kind();
        if (($id < 1) || ($id >= $this->next_id) || ($owner < 0)
            || ($kind < \collect_symbols\SYMBOL_FUNCTION) || ($kind > \collect_symbols\SYMBOL_CONSTANT)) { throw new \LogicException('Invalid symbol identity'); }
        if ($this->contains($id)) { throw new \LogicException('Duplicate symbol ID'); }
        $path = '';
        if ($record->is_source()) {
            $this->require_source($record);
            $path = $record->source_frontend()->tokens->source->path;
        } else { $this->require_provider($record); }
        if ($kind !== \collect_symbols\SYMBOL_FILE_ENTRY) {
            if ($record->name === '') { throw new \LogicException('Invalid named declaration'); }
            if ($this->conflict($record->name,$kind,$owner,$record->namespace_name) !== 0) { throw new \LogicException('Duplicate symbol name'); }
        }
        $this->by_id[$id] = q_count($this->rows); $this->rows[] = $record;
        if ($kind === \collect_symbols\SYMBOL_FILE_ENTRY) { $this->entries[$path] = $id; }
        else { $this->by_name[Symbol_Store::name_key($record->name,$kind,$owner,$record->namespace_name)] = $id; }
        if ($record->is_source()) {
            if (!isset($this->by_file[$path])) { $empty_file /** vector<int> */ = []; $this->by_file[$path] = $empty_file; }
            $this->by_file[$path][] = $id;
        }
        if (!isset($this->by_owner[$owner])) { $empty_owner /** vector<int> */ = []; $this->by_owner[$owner] = $empty_owner; }
        $this->by_owner[$owner][] = $id;
    }
    private function require_source(Symbol_Record $record): void {
        $fact = $record->source_fact(); $owner = $record->owner_symbol_id; $kind = $record->kind();
        $frontend = $record->source_frontend(); $path = $frontend->tokens->source->path;
        if (($path === '') || ($record->namespace_name !== '')) { throw new \LogicException('Invalid source origin'); }
        if ($owner !== 0) {
            $parent = $this->symbol_by_id($owner);
            if (!$parent->is_source()) { throw new \LogicException('Source method requires a source owner'); }
            $parent_kind = $parent->kind();
            if (($parent_kind !== \collect_symbols\SYMBOL_STRUCT) && ($parent_kind !== \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) { throw new \LogicException('Invalid method owner'); }
            if ($parent->source_frontend() !== $frontend) { throw new \LogicException('Stale method owner'); }
            if ((int)$fact->owner_declaration_node_id !== (int)$parent->source_fact()->declaration_node_id) { throw new \LogicException('Wrong method owner syntax'); }
        }
        if ($kind === \collect_symbols\SYMBOL_FILE_ENTRY) {
            if (($record->name !== '') || ($owner !== 0) || ((int)$fact->declaration_node_id !== 0)
                || ((int)$fact->body_node_id !== $frontend->entry) || isset($this->entries[$path])) { throw new \LogicException('Invalid file entry'); }
        } elseif ((int)$fact->declaration_node_id === 0) { throw new \LogicException('Invalid source declaration syntax'); }
    }
    private function require_provider(Symbol_Record $record): void {
        $external = $record->provider(); $owner = $record->owner_symbol_id;
        if ($external->kind() !== \collect_symbols\PROVIDER_METHOD) {
            if ($owner !== 0) { throw new \LogicException('Top-level provider declaration has an owner'); }
            return;
        }
        if ($owner === 0) { throw new \LogicException('Provider method requires its declared family owner'); }
        $parent = $this->symbol_by_id($owner);
        if ($parent->is_source()) { throw new \LogicException('Provider method requires a provider owner'); }
        $parent_provider = $parent->provider();
        if ($parent_provider->kind() !== \collect_symbols\PROVIDER_FAMILY) { throw new \LogicException('Provider method requires a generic family owner'); }
        if ($parent_provider->family() !== $external->method()->family) { throw new \LogicException('Wrong provider method owner'); }
        $is_const = $external->receiver_const();
    }
}
