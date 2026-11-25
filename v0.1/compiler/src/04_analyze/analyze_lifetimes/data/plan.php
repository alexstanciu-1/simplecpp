<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Private lexicographic destruction rank, replacing PHP tuple comparison. */
final class Cleanup_Rank {
    public function __construct(public readonly int $block, public readonly int $boundary,
        public readonly int $phase, public readonly int $order) {}
    public function precedes(Cleanup_Rank $other): bool {
        if ($this->block !== $other->block) { return $this->block < $other->block; }
        if ($this->boundary !== $other->boundary) { return $this->boundary < $other->boundary; }
        if ($this->phase !== $other->phase) { return $this->phase < $other->phase; }
        return $this->order < $other->order;
    }
}
/** Consumption/cleanup facts only. A complete analyzed body must additionally accept resource ownership. */
final class Lifetime_Plan {
    private array $value_rows /** vector<Value_Lifetime> */ = [];
    private array $local_rows /** vector<Local_Lifetime> */ = [];
    private array $block_ids /** vector<int> */ = [];
    private array $cleanup_rows /** vector<Cleanup_Obligation> */ = [];
    private array $by_local /** hash<int,int> */ = [];
    public function __construct(public readonly \check_bodies\Checked_Body $body,
        array $values /** vector<Value_Lifetime> */, public readonly int $reachable_statement_count,
        public readonly bool $falls_through, array $locals /** vector<Local_Lifetime> */,
        array $blocks /** vector<int> */, array $cleanups /** vector<Cleanup_Obligation> */) {
        foreach ($values as $row) { $this->value_rows[] = $row; }
        foreach ($blocks as $id) { $this->block_ids[] = $id; }
        foreach ($cleanups as $row) { $this->cleanup_rows[] = $row; }
        $exits /** hash<bool> */ = [];
        foreach ($locals as $local) {
            $id = $local->local_id; $key = Lifetime_Plan::local_key($id,$local->block_id,$local->end_after_statement);
            if (($id < 1) || isset($exits[$key]) || ($local->initialized_statement_id < 0)
                || ($local->initialized_statement_id > $local->end_after_statement) || ($local->end_after_statement > $body->statement_count())) { throw new \LogicException('Invalid or duplicate analyzed local lifetime'); }
            $binding = $body->names->local_for($id);
            if (($local->initialized_statement_id === 0) !== ($id < $body->entry_parameter_count()+1)) { throw new \LogicException('Invalid local entry initialization'); }
            if ($local->initialized_statement_id === 0) { if ((int)$binding->scope_id !== 1) { throw new \LogicException('Invalid local entry initialization'); } }
            if (isset($this->by_local[$id])) {
                if ($this->local_rows[$this->by_local[$id]]->initialized_statement_id !== $local->initialized_statement_id) { throw new \LogicException('Inconsistent analyzed local initialization'); }
            } else { $this->by_local[$id] = q_count($this->local_rows); }
            $exits[$key] = true; $this->local_rows[] = $local;
        }
        $this->validate_cleanups();
    }
    private static function local_key(int $id, int $block, int $boundary): string { return 'l:'.$id.':'.$block.':'.$boundary; }
    private static function temporary_key(int $id, int $boundary): string { return 't:'.$id.':'.$boundary; }
    private function managed(int $type): bool {
        $life = $this->body->definition_for($type)->lifetime;
        if ($life === null) { return false; }
        return (int)$life->policy()->cleanup === \type_model\CLEANUP_DESTROY;
    }
    private function validate_cleanups(): void {
        $expected /** hash<Cleanup_Rank> */ = [];
        foreach ($this->local_rows as $local) {
            if (!\type_model\Semantic_Modes::is_borrow($this->body->local_passing($local->local_id))) {
                if ($this->managed($this->body->local_type_for($local->local_id))) {
                    $key = Lifetime_Plan::local_key($local->local_id,$local->block_id,$local->end_after_statement);
                    $expected[$key] = new Cleanup_Rank(0,0,1,-$local->initialized_statement_id);
                }
            }
        }
        foreach ($this->value_rows as $lifetime) {
            $value = $this->body->value_for($lifetime->value_id);
            $constructed = ($value->kind === \check_bodies\VALUE_CALL_RESULT) || ($value->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT);
            if (!$constructed) { continue; }
            if ($lifetime->end === \analyze_lifetimes\END_LOCAL_CONSTRUCT) { continue; }
            if ($lifetime->end === \analyze_lifetimes\END_RETURN_CONSTRUCT) {
                if ($this->body->statement_at($lifetime->statement_id-1)->return_mode === \check_bodies\RETURN_DIRECT_CONSTRUCT) { continue; }
            }
            if ($this->managed($value->type_id)) {
                $key = Lifetime_Plan::temporary_key($lifetime->value_id,$lifetime->statement_id);
                $expected[$key] = new Cleanup_Rank(0,0,0,-$lifetime->value_id);
            }
        }
        $ranks /** hash<int,int> */ = [];
        for ($i = 0; $i < q_count($this->block_ids); $i++) { $id = $this->block_ids[$i]; $ranks[$id] = $i; }
        $previous = new Cleanup_Rank(-1,-1,-1,0);
        foreach ($this->cleanup_rows as $cleanup) {
            $id = $cleanup->block_id;
            $key = Lifetime_Plan::temporary_key($cleanup->subject_id,$cleanup->after_statement);
            if ($cleanup->subject === \analyze_lifetimes\CLEANUP_LOCAL) { $key = Lifetime_Plan::local_key($cleanup->subject_id,$id,$cleanup->after_statement); }
            if (!isset($expected[$key]) || !isset($ranks[$id]) || ($id < 1) || ($id > $this->body->block_count())) { throw new \LogicException('Invalid or duplicate cleanup obligation'); }
            $block = $this->body->block_at($id-1);
            if (($cleanup->after_statement < $block->statement_start) || ($cleanup->after_statement > $block->statement_start+$block->statement_count)) { throw new \LogicException('Invalid or duplicate cleanup obligation'); }
            $rank = new Cleanup_Rank($ranks[$id],$cleanup->after_statement,$expected[$key]->phase,$expected[$key]->order);
            if ($rank->precedes($previous)) { throw new \LogicException('Invalid cleanup destruction order'); }
            unset($expected[$key]); $previous = $rank;
        }
        if (q_count($expected) !== 0) { throw new \LogicException('Missing owned-object cleanup obligations'); }
    }
    public function values(): array /** vector<Value_Lifetime> */ { return $this->value_rows; }
    public function locals(): array /** vector<Local_Lifetime> */ { return $this->local_rows; }
    public function blocks(): array /** vector<int> */ { return $this->block_ids; }
    public function cleanups(): array /** vector<Cleanup_Obligation> */ { return $this->cleanup_rows; }
    public function local_for(int $id): ?Local_Lifetime {
        if (isset($this->by_local[$id])) { return $this->local_rows[$this->by_local[$id]]; }
        $this->body->names->local_for($id); return null;
    }
}
