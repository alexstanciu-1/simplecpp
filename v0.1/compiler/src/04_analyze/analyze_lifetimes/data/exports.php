<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Fixed source operation, accepted definition and reachable analyzed bodies. */
final class Export_Task {
    private array $body_rows /** hash<Analyzed_Body,int> */ = [];
    public function __construct(public readonly \type_model\Lifecycle_Operation $operation,
        public readonly \type_model\Named_Definition $definition, array $bodies /** hash<Analyzed_Body,int> */,
        public readonly ?Ownership_Result $ownership) {
        if ($operation->imported) { throw new \LogicException('Source export requires a source lifecycle operation'); }
        foreach ($bodies as $id => $body) {
            if ($id !== $body->body->callable_id) { throw new \LogicException('Source export body key mismatch'); }
            $this->body_rows[$id] = $body;
        }
    }
    public function bodies(): array /** hash<Analyzed_Body,int> */ { return $this->body_rows; }
}
/** Verification retains exact input identity; capture and joining own publication. */
final class Export_Verification {
    public function __construct(public readonly Export_Task $task) {}
}
