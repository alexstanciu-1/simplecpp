<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** One-shot consumption and cleanup planning over a fixed checked body; no resource-safety authorization. */
final class Lifetime_Plan_Worker {
    use Statement_Lifetimes;
    use Value_Lifetimes;
    use Local_Lifetimes;
    private array $values /** vector<Value_Lifetime> */ = [];
    private array $consumed /** hash<bool,int> */ = [];
    private int $block_id = 0;
    private array $cleanups /** vector<Cleanup_Obligation> */ = [];
    private array $temporaries /** vector<int> */ = [];
    private int $temporary_depth = 0;
    private array $live_values /** hash<bool,int> */ = [];
    private array $locals /** vector<Local_Lifetime> */ = [];
    private array $active /** vector<Active_Local> */ = [];
    private int $active_depth = 0;
    private array $live /** hash<Active_Local,int> */ = [];
    private bool $used = false;
    private ?\resolve_types\Annotation_Diagnostic $failure = null;
    public function __construct(private readonly \check_bodies\Checked_Body $body) {}
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic { return $this->failure; }
    public function analyze(): Lifetime_Plan {
        if ($this->used) { throw new \LogicException('Lifetime plan worker is one-shot'); }
        $this->used = true; $body = $this->body;
        $blocks = \check_bodies\Flow_Graph::reachable($body->flow_blocks()); $entries = Local_Flow::entries($body);
        $reachable = 0; $falls_through = false;
        foreach ($blocks as $id) {
            $this->block_id = $id; $block = $body->block_at($id-1); $this->active_depth = 0;
            $empty_live /** hash<Active_Local,int> */ = []; $this->live = $empty_live;
            $state = $entries->for_block($id); if ($state === null) { throw new \LogicException('Missing reachable initialization facts'); }
            foreach ($state->facts() as $fact) {
                $local = (int)$fact->local_id; $this->start_local($local,(int)$fact->statement_id);
                $this->contract($body->local_type_for($local),(int)$body->names->local_for($local)->declaration_node_id,false);
            }
            $end = $block->statement_start+$block->statement_count;
            for ($index = $block->statement_start; $index < $end; $index++) { $reachable++; $this->analyze_statement($body->statement_at($index),$index,$end,$block); }
            $successors = \check_bodies\Flow_Graph::successors($block);
            if (q_count($successors) === 0) {
                $returning = $block->end === \check_bodies\FLOW_RETURN;
                while ($this->active_depth > 0) { $this->end_local($end,$returning ? \analyze_lifetimes\LOCAL_RETURN_EXIT : \analyze_lifetimes\LOCAL_SCOPE_EXIT); }
                if (!$returning) { $falls_through = true; }
            } else {
                $this->exit_to_scope($body->block_at($successors[0]-1)->scope_id,$end);
                foreach ($successors as $target) {
                    $scope = $body->block_at($target-1)->scope_id;
                    foreach ($this->live as $local) {
                        if (!Local_Flow::contains($body,(int)$body->names->local_for($local->local_id)->scope_id,$scope)) { throw new \LogicException('Unsupported unequal local exits on one branch'); }
                    }
                }
            }
        }
        if ($falls_through !== $body->falls_through) { throw new \LogicException('Inconsistent checked body flow'); }
        return new Lifetime_Plan($body,$this->values,$reachable,$falls_through,$this->locals,$blocks,$this->cleanups);
    }
    private function life(int $type): \type_model\Lifetime_Policy {
        $life = $this->body->definition_for($type)->lifetime;
        if ($life === null) { throw new \LogicException('Value type has no lifetime contract'); }
        return $life->policy();
    }
    private function contract(int $type, int $node, bool $copy): void {
        $life = $this->body->definition_for($type)->lifetime;
        if ($life === null) { $this->fail($node,'Value type has no lifetime contract'); return; }
        $policy = $life->policy();
        if (((int)$policy->cleanup !== \type_model\CLEANUP_NONE) && ((int)$policy->cleanup !== \type_model\CLEANUP_DESTROY)) { $this->fail($node,'Unsupported lifetime cleanup contract'); }
        if ($copy) { if ((int)$policy->copy !== \type_model\COPY_VALUE) { $this->fail($node,'Unsupported lifetime value-copy contract'); } }
    }
    private function fail(int $node, string $reason): void {
        $frontend = $this->body->input->owner->source_frontend(); $syntax = $frontend->tree->row($node);
        $this->failure = new \resolve_types\Annotation_Diagnostic($frontend->tokens->source->path,(int)$syntax->start,(int)$syntax->length,$reason);
        throw new \RuntimeException($reason);
    }
}
