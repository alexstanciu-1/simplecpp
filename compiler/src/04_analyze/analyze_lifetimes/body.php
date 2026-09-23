<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** One fixed body. Delegates retain attributed failures even when analysis throws. */
final class Lifetime_Worker {
    private bool $used = false;
    private ?Lifetime_Plan_Worker $planner = null;
    private ?Allocation_Flow $resources = null;
    public function __construct(private readonly \check_bodies\Checked_Body $body, private readonly ?Ownership_Result $ownership) {
        if ($ownership !== null) { if ($ownership->task->body() !== $body) { throw new \LogicException('Stale ownership input for lifetime worker'); } }
        $this->planner = new Lifetime_Plan_Worker($body);
        if ($ownership === null) {
            $empty /** hash<Ownership_Summary> */ = []; $this->resources = new Allocation_Flow($body,$empty);
        }
    }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic {
        $planner = $this->planner;
        if ($planner !== null) { $failure = $planner->diagnostic(); if ($failure !== null) { return $failure; } }
        $resources = $this->resources;
        if ($resources !== null) { return $resources->diagnostic(); }
        return null;
    }
    public function analyze(): Analyzed_Body {
        if ($this->used) { throw new \LogicException('Lifetime worker is one-shot'); } $this->used = true;
        $planner = $this->planner; if ($planner === null) { throw new \LogicException('Missing lifetime planning owner'); }
        $plan = $planner->analyze();
        return new Analyzed_Body($this->body,$plan,$this->resource_analysis(),$this->ownership);
    }
    private function resource_analysis(): ?Allocation_Analysis {
        $ownership = $this->ownership;
        if ($ownership !== null) { return $ownership->allocations; }
        $resources = $this->resources;
        if ($resources === null) { throw new \LogicException('Missing allocation analysis owner'); }
        return $resources->analyze();
    }
}
