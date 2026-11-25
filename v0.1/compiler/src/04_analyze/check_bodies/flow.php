<?php
declare(strict_types=1);
namespace check_bodies;
/** Builds fixed checked blocks during the existing syntax traversal. */
final class Flow_Builder {
    private array $blocks /** vector<Flow_Slot> */ = [];
    private int $current = 0;
    private int $start = 0;
    private int $scope = 1;
    public function reserve(): int {
        $this->blocks[] = new Flow_Slot();
        return q_count($this->blocks);
    }
    public function begin(int $id, int $start, int $scope): void {
        if (($this->current !== 0) || ($id < 1) || ($id > q_count($this->blocks))) {
            throw new \LogicException('Invalid checked block start');
        }
        $slot = $this->blocks[$id - 1];
        if ($slot->value !== null) { throw new \LogicException('Invalid checked block start'); }
        $this->current = $id; $this->start = $start; $this->scope = $scope;
    }
    public function ensure(int $start, int $scope): void {
        if ($this->current === 0) { $this->begin($this->reserve(), $start, $scope); }
    }
    public function terminate(int $end, int $kind, int $first, int $second): void {
        if ($this->current === 0) { return; }
        $slot = $this->blocks[$this->current - 1];
        $slot->value = new Typed_Block($this->start, $end - $this->start, $this->scope, $kind, $first, $second);
        $this->current = 0;
    }
    public function complete(int $end): array /** vector<Typed_Block> */ {
        $this->terminate($end, \check_bodies\FLOW_FALLTHROUGH, 0, 0);
        $result /** vector<Typed_Block> */ = [];
        foreach ($this->blocks as $slot) {
            $block = $slot->value;
            if ($block === null) { throw new \LogicException('Incomplete checked flow'); }
            $result[] = $block;
        }
        return $result;
    }
}
