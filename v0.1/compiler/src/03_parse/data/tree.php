<?php
declare(strict_types=1);
namespace parse;

/** Single builder owner; IDs are one-based and zero means no node. */
final class Syntax_Arena {
    private array $rows /** vector<Syntax_Row> */ = [];
    public function size(): int { return q_count($this->rows); }
    private function require_id(int $id): void {
        if ($id < 1) { throw new \InvalidArgumentException('Invalid syntax node ID'); }
        if ($id > q_count($this->rows)) { throw new \InvalidArgumentException('Invalid syntax node ID'); }
    }
    /** Explicit copy prevents PHP object identity from defining native record behavior. */
    private static function copy(Syntax_Row $row): Syntax_Row {
        $out = new Syntax_Row();
        $out->kind = $row->kind;
        $out->start = $row->start;
        $out->length = $row->length;
        $out->first_child = $row->first_child;
        $out->last_child = $row->last_child;
        $out->next_sibling = $row->next_sibling;
        return $out;
    }
    public function row(int $id): Syntax_Row {
        $this->require_id($id);
        return Syntax_Arena::copy($this->rows[$id - 1]);
    }
    public function add(int $kind, int $start, int $length): int {
        if (($kind < 1) || ($start < 0) || ($length < 0)) { throw new \InvalidArgumentException('Invalid syntax row'); }
        if (($kind > 4294967295) || ($start > 4294967295) || ($length > 4294967295)) { throw new \InvalidArgumentException('Syntax row exceeds uint32 capacity'); }
        if (q_count($this->rows) >= 4294967295) { throw new \RuntimeException('Syntax ID capacity exhausted'); }
        $row = new Syntax_Row();
        $row->kind = $kind;
        $row->start = $start;
        $row->length = $length;
        $this->rows[] = $row;
        return q_count($this->rows);
    }
    public function finish(int $id, int $end): void {
        $row = $this->row($id);
        if ($end < (int)$row->start) { throw new \InvalidArgumentException('Invalid syntax end'); }
        if ($end > 4294967295) { throw new \InvalidArgumentException('Syntax end exceeds uint32 capacity'); }
        $row->length = $end - (int)$row->start;
        $this->rows[$id - 1] = $row;
    }
    /** Builder-only: child must be an unattached subtree, with no ancestor link to parent. */
    public function child(int $parent, int $child): void {
        $owner = $this->row($parent);
        $node = $this->row($child);
        if ($parent === $child) { throw new \InvalidArgumentException('Self-linked syntax node'); }
        if ((int)$node->next_sibling !== 0) { throw new \InvalidArgumentException('Child already has siblings'); }
        if ((int)$owner->last_child === $child) { throw new \InvalidArgumentException('Duplicate syntax child'); }
        if ((int)$owner->last_child === 0) {
            $owner->first_child = $child;
        } else {
            $last_id = (int)$owner->last_child;
            $last = $this->row($last_id);
            $last->next_sibling = $child;
            $this->rows[$last_id - 1] = $last;
        }
        $owner->last_child = $child;
        $this->rows[$parent - 1] = $owner;
    }
}
