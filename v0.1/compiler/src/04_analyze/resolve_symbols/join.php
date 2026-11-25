<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Accept one result per selected owner in any arrival order, then publish atomically. */
final class Resolution_Join {
    public function __construct(private readonly Resolution_Plan $plan) {}
    public function join(array $results /** vector<Symbol_Resolution> */): Resolution_Set {
        $replacements /** hash<Symbol_Resolution,int> */ = [];
        foreach ($results as $result) {
            $id = $result->owner->symbol_id;
            if (!$this->plan->selected($id)) { throw new \LogicException('Unexpected resolution result'); }
            if (isset($replacements[$id])) { throw new \LogicException('Duplicate resolution result'); }
            $replacements[$id] = $result;
        }
        if (q_count($replacements) !== $this->plan->task_count()) { throw new \LogicException('Incomplete resolution task batch'); }
        $current /** vector<Symbol_Resolution> */ = [];
        for ($position = 0; $position < $this->plan->symbols->size(); $position++) {
            $owner = $this->plan->symbols->record_at($position);
            if (!$owner->is_source()) { continue; }
            $id = $owner->symbol_id;
            if (isset($replacements[$id])) { $current[] = $replacements[$id]; }
            else {
                $old = $this->plan->previous->for_symbol($id);
                if ($old === null) { throw new \LogicException('Missing retained resolution'); }
                $current[] = $old;
            }
        }
        return Resolution_Set::publish($this->plan->symbols,$this->plan->catalog,$this->plan->previous,$current);
    }
}
