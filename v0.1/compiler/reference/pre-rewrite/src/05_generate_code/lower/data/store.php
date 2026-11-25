<?php
declare(strict_types=1);

/*
 * Role: Lowered bodies indexed by callable.
 * Used by: Lowering_Join; LLVM_Emitter
 * Flow: Lowered_Body -> Lowered_Set -> function emission
 */

namespace lower;

/**
 * @compiler-api Read-only stage result used by compile and LLVM emission.
 * Lowering_Join::join() assembles current membership; consumers use lookups/iteration,
 * never the storage index. Returned bodies are shared and must not be mutated.
 */
class Lowered_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<int, Lowered_Body> */
    private array $by_callable = [];

    /**
     * @compiler-api An empty set bootstraps a session; nonempty assembly belongs
     * to the lowering join. Construction checks uniqueness, not full plan validity.
     * @param list<Lowered_Body> $bodies
     */
    public function __construct(array $bodies = [])
    {
        foreach ($bodies as $body) {
            $id = $body->binding->callable_id;
            if (isset($this->by_callable[$id])) {
                throw new \LogicException('Duplicate lowered body');
            }
            $this->by_callable[$id] = $body;
        }
    }

    /** @compiler-api Shared ordinary plan for a source symbol ID, or null if absent. */
    public function for_symbol(int $id): ?Lowered_Body
    {
        return $id <= \collect_symbols\MAX_SYMBOL_ID ? $this->for_callable($id) : null;
    }

    public function for_callable(int $id): ?Lowered_Body
    {
        return $this->by_callable[$id] ?? null;
    }

    /**
     * @compiler-api Iterate plans in coordinator-supplied order; no copied bodies.
     * @return list<Lowered_Body>
     */
    public function bodies(): array
    {
        return array_values($this->by_callable);
    }

    /** @compiler-api Debug export on request; not a round-trip cache format. */
    public function to_json(): string
    {
        return json_encode(array_map(static fn($body) => $body->to_array(), $this->bodies()), JSON_THROW_ON_ERROR);
    }
}
