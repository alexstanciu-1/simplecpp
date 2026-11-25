<?php
declare(strict_types=1);
namespace analyze_lifetimes;
final class Ownership_Child {
    public function __construct(public readonly int $ordinal, public readonly string $dependency) {
        if (($ordinal < 0) || ($dependency === '')) { throw new \InvalidArgumentException('Invalid ownership child'); }
    }
}
/** Complete lifecycle subject; child order is selected once by preparation. */
final class Ownership_Lifecycle {
    private array $child_rows /** vector<Ownership_Child> */ = [];
    public function __construct(public readonly int $type_id, public readonly \type_model\Named_Definition $definition,
        public readonly int $kind, array $children /** vector<Ownership_Child> */, public readonly ?int $body_id) {
        \type_model\Lifecycle_Roles::require_role($kind);
        if ($type_id < 1) { throw new \InvalidArgumentException('Invalid ownership lifecycle type'); }
        if ($body_id !== null) { if ($body_id < 1) { throw new \InvalidArgumentException('Invalid ownership lifecycle body'); } }
        $seen /** hash<bool,int> */ = [];
        foreach ($children as $child) {
            $ordinal = $child->ordinal; if (isset($seen[$ordinal])) { throw new \InvalidArgumentException('Duplicate ownership child'); }
            $seen[$ordinal] = true; $this->child_rows[] = $child;
        }
    }
    public function children(): array /** vector<Ownership_Child> */ { return $this->child_rows; }
    public function order(): \type_model\Lifecycle_Order { return \type_model\Lifecycle_Roles::composition($this->kind,$this->body_id !== null); }
}
/** Exactly one subject; nullable handles replace the PHP union without weakening its alternatives. */
final class Ownership_Task {
    private array $inputs /** hash<Ownership_Summary> */ = [];
    public function __construct(public readonly string $key, private readonly ?\check_bodies\Checked_Body $body_subject,
        private readonly ?Ownership_Lifecycle $lifecycle_subject, array $dependencies /** hash<Ownership_Summary> */) {
        if (($key === '') || (($body_subject === null) === ($lifecycle_subject === null))) { throw new \InvalidArgumentException('Ownership task requires exactly one subject'); }
        foreach ($dependencies as $name => $summary) { $this->inputs[$name] = $summary; }
    }
    public function body(): ?\check_bodies\Checked_Body { return $this->body_subject; }
    public function lifecycle(): ?Ownership_Lifecycle { return $this->lifecycle_subject; }
    public function dependencies(): array /** hash<Ownership_Summary> */ { return $this->inputs; }
    public function dependency(string $key): Ownership_Summary {
        if (!isset($this->inputs[$key])) { throw new \LogicException('Missing ownership dependency'); }
        return $this->inputs[$key];
    }
}
/** Private result retains the exact producing task and body allocation evidence. */
final class Ownership_Result {
    public function __construct(public readonly Ownership_Task $task, public readonly Ownership_Summary $summary,
        public readonly ?Allocation_Analysis $allocations) {
        if ($allocations !== null) { if ($task->body() !== $allocations->body) { throw new \LogicException('Ownership allocation provenance mismatch'); } }
    }
}
