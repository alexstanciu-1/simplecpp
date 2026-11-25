<?php
declare(strict_types=1);

/*
 * Role: Symbol dataset, identity allocation and lookup indexes.
 * Used by: Declaration_Join; comparison and analysis
 * Flow: accepted declarations -> Symbol_Store -> name lookup
 */

namespace collect_symbols;

/**
 * @compiler-api Project symbol dataset produced by Declaration_Join::join.
 * Read through lookup/iteration methods; returned symbol records and frontends are
 * shared read-only. Logical IDs are stable within this session lineage, not array
 * positions. Private name/file/owner indexes may change without changing queries.
 * Only collection constructs/populates a candidate; no consumer allocates or adds.
 */
class Symbol_Store implements \compile\Step_Store
{
    /** @var list<symbol_record> */
    private array $rows = [];

    /** @var array<string, int> */
    private array $by_qualified_name = [];

    /** @var array<int, list<int>> */
    private array $by_source_file = [];

    /** @var array<int, list<int>> */
    private array $by_owner = [];

    /** @var array<int, int> */
    private array $row_by_symbol_id = [];

    /** @var array<int, int> Unnamed implicit callable by source-file ID. */
    private array $entry_by_file = [];

    /** @compiler-api Create an empty baseline; nondefault allocation state is collection-owned candidate construction. */
    public function __construct(private int $next_id = 1)
    {
        if (($next_id < 1) || ($next_id > (MAX_SYMBOL_ID + 1))) {
            throw new \InvalidArgumentException('Invalid next symbol ID');
        }
    }

    /** @compiler-internal Collection allocation watermark for the next candidate; not a symbol lookup. */
    public function next_symbol_id(): int
    {
        return $this->next_id;
    }

    /** @compiler-internal Collection coordinator only: advance a private candidate allocator; throws on exhaustion. */
    public function allocate_id(): int
    {
        if ($this->next_id > MAX_SYMBOL_ID) {
            throw new \OverflowException('Symbol ID space exhausted');
        }
        return $this->next_id++;
    }

    /**
     * @compiler-api Exact name/namespace/kind/owner lookup; zero means missing.
     * owner_symbol_id zero means project scope; no overload or case-folding semantics.
     */
    public function find_symbol(string $name, string $namespace_name, symbol_kind $kind, int $owner_symbol_id = 0): int
    {
        return $this->by_qualified_name[self::name_key($name, $namespace_name, $kind, $owner_symbol_id)] ?? 0;
    }

    /** @compiler-api Implicit callable identity for a source file, or zero when absent; no fabricated name. */
    public function entry_symbol_id(int $source_file_id): int
    {
        return $this->entry_by_file[$source_file_id] ?? 0;
    }

    /**
     * @compiler-api Read logical children of an owner; empty means none. No record copies.
     * @return list<int>
     */
    public function child_symbol_ids(int $owner_symbol_id): array
    {
        return $this->by_owner[$owner_symbol_id] ?? [];
    }

    /**
     * @compiler-api Read current contribution IDs for a logical source file; empty means none.
     * @return list<int>
     */
    public function file_symbol_ids(int $source_file_id): array
    {
        return $this->by_source_file[$source_file_id] ?? [];
    }

    /**
     * @compiler-api Read shared current records in candidate source order; iteration positions are not identities.
     * @return list<symbol_record> Read-only phase view, in candidate source order.
     */
    public function records(): array
    {
        return $this->rows;
    }

    /** @compiler-api Executable source bodies; template definitions and provider declarations do not participate. */
    public function body_records(): array
    {
        return array_values(array_filter($this->rows, static fn(symbol_record $symbol): bool => $symbol->has_executable_body()));
    }

    /** Source declarations and entries participate in binding before concrete preparation. */
    public function resolution_records(): array
    {
        return array_values(array_filter($this->rows, static fn(symbol_record $symbol): bool => $symbol->frontend !== null));
    }

    /** @compiler-api Return the shared current record for a project symbol ID; throws OutOfBoundsException if absent. */
    public function symbol_by_id(int $symbol_id): symbol_record
    {
        $row = $this->row_by_symbol_id[$symbol_id] ?? null;
        if ($row === null) {
            throw new \OutOfBoundsException('Unknown symbol ID: ' . $symbol_id);
        }
        return $this->rows[$row];
    }

    /** @compiler-api Test current membership by logical symbol ID; no lookup side effects. */
    public function contains(int $symbol_id): bool
    {
        return isset($this->row_by_symbol_id[$symbol_id]);
    }

    /**
     * @compiler-internal Collection-only candidate mutation with index maintenance; caller owns a complete record.
     * Reject invalid/duplicate identity or declaration; never mutate a published store.
     */
    public function add(symbol_record $symbol): void
    {
        $id = $symbol->symbol_id;
        $file = $symbol->frontend?->source_file_id;
        if (($id >= $this->next_id) || $this->contains($id) || (($file !== null) && ($file <= 0))
            || ($symbol->owner_symbol_id < 0) || ($symbol->kind === symbol_kind::invalid)) {
            throw new \LogicException('Invalid or duplicate symbol record');
        }
        if (($symbol->external !== null) && (($symbol->kind !== match (true) {
                ($symbol->external instanceof \type_model\storage_family), ($symbol->external instanceof \type_model\family_declaration) => symbol_kind::template_struct,
                ($symbol->external instanceof \type_model\storage_function), ($symbol->external instanceof \type_model\family_method) => symbol_kind::template_function,
                default => symbol_kind::function_symbol,
            })
            || ($symbol->declaration_node_id !== 0) || ($symbol->body_node_id !== 0)
            || ($symbol->name !== $symbol->external->name) || ($symbol->namespace_name !== $symbol->external->namespace_name))) {
            throw new \LogicException('Invalid provider declaration');
        }
        if ($symbol->kind === symbol_kind::file_entry)
        {
            if (($symbol->name !== '') || ($symbol->namespace_name !== '') || ($symbol->owner_symbol_id !== 0)
                || ($symbol->declaration_node_id !== 0) || ($symbol->body_node_id !== $symbol->frontend->entry_body_id)
                || (isset($this->entry_by_file[$file]))) {
                throw new \LogicException('Invalid or duplicate implicit entry');
            }
            $this->entry_by_file[$file] = $id;
        }
        else {
            $key = self::name_key($symbol->name, $symbol->namespace_name, $symbol->kind, $symbol->owner_symbol_id);
            if (($symbol->name === '') || (isset($this->by_qualified_name[$key]))) {
                throw new \LogicException('Invalid or duplicate named symbol');
            }
            $this->by_qualified_name[$key] = $id;
        }
        $this->row_by_symbol_id[$id] = count($this->rows);
        $this->rows[] = $symbol;
        if ($file !== null) {
            $this->by_source_file[$file][] = $id;
        }
        $this->by_owner[$symbol->owner_symbol_id][] = $id;
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $rows = [];
        foreach ($this->rows as $row) {
            $rows[] = $row->to_array();
        }
        return json_encode(['next_symbol_id' => $this->next_id, 'rows' => $rows], JSON_THROW_ON_ERROR);
    }

    // Identifier spelling is exact in the current grammar. Length prefixes make
    // compound keys unambiguous without delimiter restrictions or case folding.
    private static function name_key(string $name, string $namespace_name, symbol_kind $kind, int $owner): string
    {
        return $owner . ':' . $kind->value . ':' . strlen($namespace_name) . ':' . $namespace_name . $name;
    }
}
