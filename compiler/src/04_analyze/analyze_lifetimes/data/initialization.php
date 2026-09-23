<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Dense fact rows preserve initialization order independently of hash iteration order. */
/** @scpp-struct */
final class Initialization_Fact {
    public int $local_id /** uint32 */ = 0;
    public int $statement_id /** uint32 */ = 0;
}
final class Initialization_Rows {
    public static function make(int $local, int $statement): Initialization_Fact {
        if (($local < 1) || ($local > 4294967295) || ($statement < 0) || ($statement > 4294967295)) { throw new \LogicException('Invalid initialization fact'); }
        $row = new Initialization_Fact(); $row->local_id = $local; $row->statement_id = $statement; return $row;
    }
    public static function copy(Initialization_Fact $row): Initialization_Fact {
        return Initialization_Rows::make((int)$row->local_id,(int)$row->statement_id);
    }
}
/** Fixed ordered local facts at one reachable block entry. Statement zero means parameter entry. */
final class Initialization_State {
    private array $rows /** vector<Initialization_Fact> */ = [];
    private array $positions /** hash<int,int> */ = [];
    public function __construct(array $rows /** vector<Initialization_Fact> */) {
        foreach ($rows as $row) {
            $local = (int)$row->local_id;
            if (isset($this->positions[$local])) { throw new \LogicException('Duplicate initialization fact'); }
            $this->positions[$local] = q_count($this->rows); $this->rows[] = Initialization_Rows::copy($row);
        }
    }
    public function size(): int { return q_count($this->rows); }
    public function has(int $local): bool { return isset($this->positions[$local]); }
    public function initialization(int $local): ?int {
        if (!isset($this->positions[$local])) { return null; }
        return (int)$this->rows[$this->positions[$local]]->statement_id;
    }
    public function facts(): array /** vector<Initialization_Fact> */ {
        $copy /** vector<Initialization_Fact> */ = [];
        foreach ($this->rows as $row) { $copy[] = Initialization_Rows::copy($row); }
        return $copy;
    }
    /** Intersection preserves this predecessor's values/order, as the original key intersection does. */
    public function intersect(Initialization_State $other): Initialization_State {
        $rows /** vector<Initialization_Fact> */ = [];
        foreach ($this->rows as $row) { if ($other->has((int)$row->local_id)) { $rows[] = $row; } }
        return new Initialization_State($rows);
    }
}
/** Complete fixed-point entry facts, tied to the exact checked body; unreachable blocks are absent. */
final class Initialization_Entries {
    private array $states /** hash<Initialization_State,int> */ = [];
    public function __construct(public readonly \check_bodies\Checked_Body $body, array $states /** hash<Initialization_State,int> */) {
        if (!isset($states[1])) { throw new \LogicException('Missing entry initialization state'); }
        foreach ($states as $id => $state) {
            if (($id < 1) || ($id > $body->block_count())) { throw new \LogicException('Invalid initialization block'); }
            $this->states[$id] = $state;
        }
    }
    public function size(): int { return q_count($this->states); }
    public function for_block(int $id): ?Initialization_State {
        if (!isset($this->states[$id])) { return null; }
        return $this->states[$id];
    }
}
