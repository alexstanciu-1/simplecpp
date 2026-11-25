<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Complete per-body facts: consumption/cleanup plan plus accepted resource analysis. */
final class Analyzed_Body {
    public function __construct(public readonly \check_bodies\Checked_Body $body,
        public readonly Lifetime_Plan $plan, public readonly ?Allocation_Analysis $allocations,
        public readonly ?Ownership_Result $ownership) {
        if ($plan->body !== $body) { throw new \LogicException('Lifetime plan belongs to another checked body'); }
        if ($allocations !== null) { if ($allocations->body !== $body) { throw new \LogicException('Allocation facts belong to another checked body'); } }
        if ($ownership !== null) {
            if (($ownership->task->body() !== $body) || ($ownership->allocations !== $allocations)) { throw new \LogicException('Stale ownership input for lifetime result'); }
        }
        $owners = Resource_Locations::locals($body);
        if (((q_count($owners) !== 0) || ($ownership !== null)) !== ($allocations !== null)) { throw new \LogicException('Missing or unexpected allocation analysis'); }
        if ($allocations !== null) {
            $entries = $allocations->entries(); $blocks = $plan->blocks();
            if (q_count($entries) !== q_count($blocks)) { throw new \LogicException('Allocation analysis must cover reachable blocks'); }
            foreach ($blocks as $id) { if (!isset($entries[$id])) { throw new \LogicException('Allocation analysis must cover reachable blocks'); } }
            foreach ($entries as $entry) {
                foreach ($entry->states() as $key => $state) {
                    if (!isset($owners[$key]) || ($state < 1) || ($state > 15)) { throw new \LogicException('Invalid allocation entry state'); }
                }
            }
        }
    }
    public function values(): array /** vector<Value_Lifetime> */ { return $this->plan->values(); }
    public function locals(): array /** vector<Local_Lifetime> */ { return $this->plan->locals(); }
    public function blocks(): array /** vector<int> */ { return $this->plan->blocks(); }
    public function cleanups(): array /** vector<Cleanup_Obligation> */ { return $this->plan->cleanups(); }
    public function local_for(int $id): ?Local_Lifetime { return $this->plan->local_for($id); }
}
