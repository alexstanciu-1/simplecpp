<?php
declare(strict_types=1);
namespace check_templates;
/** Fixed source-definition selection; publication follows current declaration order. */
final class Template_Plan {
    private array $tasks /** vector<Definition_Task> */ = [];
    private array $indices /** hash<int,int> */ = [];
    public function __construct(public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names, public readonly \type_model\Type_Catalog $catalog,
        public readonly Template_Set $previous, bool $full) {
        for ($position = 0; $position < $symbols->size(); $position++) {
            $owner = $symbols->record_at($position);
            if (!$owner->is_source()) { continue; }
            if (!$owner->is_template()) { continue; }
            $bindings = $names->for_symbol($owner->symbol_id);
            if ($bindings === null) { throw new \LogicException('Template selection requires current bindings'); }
            if ($bindings->owner !== $owner) { throw new \LogicException('Template selection has stale bindings'); }
            $reuse = false;
            if (!$full) {
                $old = $previous->for_definition($owner->symbol_id);
                if ($old !== null) { $reuse = $old->current($owner,$names,$catalog); }
            }
            if (!$reuse) {
                $this->indices[$owner->symbol_id] = q_count($this->tasks);
                $this->tasks[] = new Definition_Task($owner,$bindings);
            }
        }
    }
    public function task_count(): int { return q_count($this->tasks); }
    public function task_at(int $position): Definition_Task {
        if (($position < 0) || ($position >= q_count($this->tasks))) { throw new \InvalidArgumentException('Invalid template task position'); }
        return $this->tasks[$position];
    }
    public function selected_task(int $id): ?Definition_Task {
        if (!isset($this->indices[$id])) { return null; }
        return $this->tasks[$this->indices[$id]];
    }
}
