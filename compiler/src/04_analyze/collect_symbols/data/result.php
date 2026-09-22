<?php
declare(strict_types=1);

/*
 * Role: Private file declarations and completed symbol refresh.
 * Used by: Declaration_Collector; Symbol_Comparer; Compiler_Session
 * Flow: File_Declarations -> Symbol_Refresh -> analysis selection
 */

namespace collect_symbols;

/**
 * @compiler-api Read current and changes after collection/comparison returns. Collection builds
 * the current store; comparison completes pairs before incremental admission. Compile
 * retains the store separately; this per-update catalog is not a published generation.
 */
class Symbol_Refresh implements \compile\Step_Result
{
    public Symbol_Store $current;

    // Per-update descriptions. Removed rows retain only their previous symbol;
    // unchanged rows may be omitted unless they summarize changed contents.
    /** @var list<symbol_change> */
    public array $changes = [];

    /** @compiler-api Empty change view of a store whose inputs the coordinator has proved unchanged. */
    public static function unchanged(Symbol_Store $store): self
    {
        $result = new self();
        $result->current = $store;
        return $result;
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $changes = [];
        foreach ($this->changes as $change) {
            $changes[] = ['previous' => $change->previous?->to_array(), 'current' => $change->current?->to_array(),
                'own_status' => $change->own_status->name, 'children_changed' => $change->children_changed];
        }
        return '{"current":' . $this->current->to_json()
            . ',"changes":' . json_encode($changes, JSON_THROW_ON_ERROR) . '}';
    }
}

/**
 * @compiler-api Read-only file-worker output: frontend plus ordered declarations. Passed by the
 * coordinator to collection join; consumers must wait for project identity assignment.
 */
class File_Declarations
{
    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<declaration> $declarations
     */
    public function __construct(
        public readonly \parse\File_Frontend $frontend,
        public readonly array $declarations
    )
    {
    }
}
