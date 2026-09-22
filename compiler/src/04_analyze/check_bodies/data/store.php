<?php
declare(strict_types=1);

/*
 * Role: Checked bodies indexed by callable.
 * Used by: Body_Join; Lifetime_Analyzer
 * Flow: Checked_Body -> Body_Set -> lifetime selection
 */

namespace check_bodies;

// Project-owned checked bodies, indexed by callable identity.
/**
 * @compiler-api Joined checked callable set; consumers use callable-ID lookup/iteration, not index
 * storage. Objects/rows are shared read-only; removed owners are absent.
 */
class Body_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<int, Checked_Body> */
    private array $by_callable = [];

    /**
     * @compiler-api Create an empty baseline; nonempty assembly belongs to the producing join.
     * Construction checks local invariants, not completeness of a compiler phase.
     * @param list<Checked_Body> $bodies
     */
    public function __construct(array $bodies = [])
    {
        foreach ($bodies as $body) {
            $id = $body->callable_id;
            if (isset($this->by_callable[$id])) {
                throw new \LogicException('Duplicate checked body');
            }
            $this->by_callable[$id] = $body;
        }
    }

    /**
     * @compiler-api Read an ordinary callable by source symbol ID; null means absent in this set.
     * The returned object and its rows must remain unchanged.
     */
    public function for_symbol(int $id): ?Checked_Body
    {
        return $id <= \collect_symbols\MAX_SYMBOL_ID ? $this->for_callable($id) : null;
    }

    public function for_callable(int $id): ?Checked_Body
    {
        return $this->by_callable[$id] ?? null;
    }

    /**
     * @compiler-api Read current shared results in coordinator order; no copied body records.
     * @return list<Checked_Body> Current bodies in coordinator order.
     */
    public function bodies(): array
    {
        return array_values($this->by_callable);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        return json_encode(array_values(array_map(static fn($body) => $body->to_array(), $this->by_callable)), JSON_THROW_ON_ERROR);
    }
}
