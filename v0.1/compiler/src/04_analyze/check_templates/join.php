<?php
declare(strict_types=1);
namespace check_templates;
/** Validate a complete private batch before publishing permissions. */
final class Template_Join {
    public function __construct(private readonly Template_Plan $plan) {}
    public function join(array $results /** vector<Definition_Result> */): Template_Set {
        $accepted /** hash<Definition_Result,int> */ = [];
        foreach ($results as $result) {
            $task = $result->task; $id = $task->owner->symbol_id;
            if ($this->plan->selected_task($id) !== $task) { throw new \LogicException('Unexpected template result task'); }
            if (isset($accepted[$id])) { throw new \LogicException('Duplicate template result'); }
            if ($this->plan->symbols->symbol_by_id($id) !== $task->owner) { throw new \LogicException('Stale template result owner'); }
            if (!$result->current($task->owner,$this->plan->names,$this->plan->catalog)) { throw new \LogicException('Stale template result dependencies'); }
            if (!$result->has_owner_provenance()) { throw new \LogicException('Invalid template result provenance'); }
            $accepted[$id] = $result;
        }
        if (q_count($accepted) !== $this->plan->task_count()) { throw new \LogicException('Incomplete template definition batch'); }
        $output /** vector<Definition_Result> */ = [];
        for ($position = 0; $position < $this->plan->symbols->size(); $position++) {
            $owner = $this->plan->symbols->record_at($position);
            if (!$owner->is_source()) { continue; }
            if (!$owner->is_template()) { continue; }
            $id = $owner->symbol_id;
            $result = $this->plan->previous->for_definition($id);
            if (isset($accepted[$id])) { $result = $accepted[$id]; }
            if ($result === null) { throw new \LogicException('Missing current template result'); }
            if (!$result->current($owner,$this->plan->names,$this->plan->catalog)) { throw new \LogicException('Missing current template result'); }
            $output[] = $result;
        }
        return new Template_Set($output,$this->plan->task_count());
    }
}
