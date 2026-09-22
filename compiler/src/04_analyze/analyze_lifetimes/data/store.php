<?php
declare(strict_types=1);

/*
 * Role: Lifetime results indexed by callable.
 * Used by: Lifetime_Join; Lowerer
 * Flow: Analyzed_Body -> Lifetime_Set -> lowering selection
 */

namespace analyze_lifetimes;

/**
 * @compiler-api Joined current analyses; shared read-only results queried by concrete callable ID.
 * Object identity of each checked input participates in selection/reuse.
 */
class Lifetime_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<int, Analyzed_Body> */
    private array $by_callable = [];

    /**
     * @compiler-api Create an empty baseline; nonempty assembly belongs to the producing join.
     * Construction checks local invariants, not completeness of a compiler phase.
     * @param list<Analyzed_Body> $bodies
     */
    public function __construct(array $bodies = [], public readonly array $ownership_results = [])
    {
        foreach ($bodies as $body) {
            $id = $body->body->callable_id;
            if (isset($this->by_callable[$id])) {
                throw new \LogicException('Duplicate lifetime result');
            }
            $this->by_callable[$id] = $body;
        }
    }

    /**
     * @compiler-api Read an ordinary callable by source symbol ID; null means absent in this set.
     * The returned object and its rows must remain unchanged.
     */
    public function for_symbol(int $id): ?Analyzed_Body
    {
        return $id <= \collect_symbols\MAX_SYMBOL_ID ? $this->for_callable($id) : null;
    }

    public function for_callable(int $id): ?Analyzed_Body
    {
        return $this->by_callable[$id] ?? null;
    }

    /**
     * @compiler-api Read current shared results in coordinator order; no copied body records.
     * @return list<Analyzed_Body> Current analyses in coordinator order.
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
