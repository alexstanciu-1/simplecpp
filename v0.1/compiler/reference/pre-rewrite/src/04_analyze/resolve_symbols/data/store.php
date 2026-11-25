<?php
declare(strict_types=1);

/*
 * Role: Name-resolution results indexed by source owner.
 * Used by: Resolution_Join; type/body checking
 * Flow: Symbol_Resolution -> Resolution_Set -> consumers
 */

namespace resolve_symbols;

// Project-owned results indexed by project symbol identity. Unchanged results are shared.
/**
 * @compiler-api Joined name results queried by project symbol ID; records/ASTs are shared read-only.
 * Current membership comes from the symbol join; no private index contract.
 */
class Resolution_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<int, Symbol_Resolution> */
    private array $by_symbol = [];

    /**
     * @compiler-api Create an empty baseline; nonempty assembly belongs to the producing join.
     * Construction checks local invariants, not completeness of a compiler phase.
     * @param list<Symbol_Resolution> $results
     */
    public function __construct(array $results = [], private readonly ?\collect_symbols\Symbol_Store $symbols = null)
    {
        foreach ($results as $result) {
            if (($result->symbol_id <= 0) || (isset($this->by_symbol[$result->symbol_id]))) {
                throw new \LogicException('Invalid or duplicate resolution owner');
            }
            $this->by_symbol[$result->symbol_id] = $result;
        }
    }

    /**
     * @compiler-api Read a shared result by project symbol ID; null means absent in this set.
     * The returned object and its rows must remain unchanged.
     */
    public function for_symbol(int $id): ?Symbol_Resolution
    {
        return $this->by_symbol[$id] ?? null;
    }

    /** Resolve a bound project declaration ID in this result's fixed project snapshot. */
    public function declaration_for(int $id): \collect_symbols\symbol_record
    {
        if ($this->symbols === null) {
            throw new \LogicException('Declaration bindings have no project context');
        }
        return $this->symbols->symbol_by_id($id);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $rows = [];
        foreach ($this->by_symbol as $result) {
            $rows[] = $result->to_array();
        }
        return json_encode($rows, JSON_THROW_ON_ERROR);
    }
}
