<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Fixed plan; tasks are current source owners, retained results come only from accepted sets. */
final class Resolution_Plan {
    private array $tasks /** vector<\collect_symbols\Symbol_Record> */ = [];
    private array $selected_ids /** hash<bool,int> */ = [];
    public function __construct(public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly Resolution_Set $previous, public readonly \type_model\Type_Catalog $catalog, bool $full) {
        for ($position = 0; $position < $symbols->size(); $position++) {
            $owner = $symbols->record_at($position); $retained = false;
            if (!$full) {
                $old = $previous->for_symbol($owner->symbol_id);
                if ($old !== null) { $retained = Resolution_Validity::is_current($old,$owner,$symbols,$catalog); }
            }
            if (!$retained) { $this->tasks[] = $owner; $this->selected_ids[$owner->symbol_id] = true; }
        }
    }
    public function task_count(): int { return q_count($this->tasks); }
    public function task_at(int $position): \collect_symbols\Symbol_Record {
        if (($position < 0) || ($position >= q_count($this->tasks))) { throw new \InvalidArgumentException('Invalid resolution task position'); }
        return $this->tasks[$position];
    }
    public function selected(int $id): bool { return isset($this->selected_ids[$id]); }
}
