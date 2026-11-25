<?php
declare(strict_types=1);

/*
 * Role: Build mutable control-flow blocks for one checked body.
 * Used by: Statement_Checking and Control_Statement_Checking on Body_Worker
 * Call map:
 *   Flow_Builder::reserve(); begin(); terminate(); complete()
 *     -> [action] connect and finish typed blocks
 */

namespace check_bodies;

/** @compiler-internal Builds typed block ranges during checking's existing syntax traversal. */
class Flow_Builder
{
    private array $blocks = [];
    private int $current = 0;
    private int $start = 0;
    private int $scope = 1;

    public function reserve(): int
    {
        $this->blocks[] = null;
        return count($this->blocks);
    }

    /** Open a reserved unused block; another block must not still be open. */
    public function begin(int $id, int $start, int $scope): void
    {
        if (($this->current !== 0) || !array_key_exists($id - 1, $this->blocks) || ($this->blocks[$id - 1] !== null)) {
            throw new \LogicException('Invalid checked block start');
        }
        $this->current = $id;
        $this->start = $start;
        $this->scope = $scope;
    }

    public function ensure(int $start, int $scope): void
    {
        if ($this->current === 0) {
            $this->begin($this->reserve(), $start, $scope);
        }
    }

    /** Close the current block with its explicit exit; ignore an already terminated path. */
    public function terminate(int $end, flow_end $kind, int $first = 0, int $second = 0): void
    {
        if ($this->current === 0) {
            return; // A return already closed this source path.
        }
        $this->blocks[$this->current - 1] = new typed_block($this->start, $end - $this->start,
            $this->scope, $kind, $first, $second);
        $this->current = 0;
    }

    /** Finish any reachable fallthrough and reject reserved blocks that were never filled. */
    public function complete(int $end): array
    {
        $this->terminate($end, flow_end::fallthrough);
        foreach ($this->blocks as $block) {
            if ($block === null) {
                throw new \LogicException('Incomplete checked flow');
            }
        }
        return $this->blocks;
    }
}
