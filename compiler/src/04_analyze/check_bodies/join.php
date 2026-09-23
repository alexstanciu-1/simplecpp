<?php
declare(strict_types=1);
namespace check_bodies;
/** All validation precedes publication; rejected batches leave the previous set intact. */
final class Body_Join {
    public function __construct(private readonly Body_Plan $plan) {}
    public function join(array $results /** vector<Checked_Body> */): Body_Set {
        $accepted /** hash<Checked_Body,int> */ = [];
        for ($i = 0; $i < $this->plan->task_count(); $i++) {
            $task = $this->plan->task_at($i); $this->plan->require_input($task->input);
            if (($task->types !== $this->plan->types) || ($task->names !== $this->plan->names->for_symbol($task->input->owner->symbol_id))) { throw new \LogicException('Stale body task'); }
        }
        foreach ($results as $result) {
            $id = $result->callable_id; $task = $this->plan->selected_task($id);
            if ($task === null) { throw new \LogicException('Unexpected body result'); }
            if (isset($accepted[$id])) { throw new \LogicException('Duplicate body result'); }
            if (!Body_Validity::is_current($result,$task->input,$this->plan->names,$this->plan->types)) { throw new \LogicException('Stale body result'); }
            $accepted[$id] = $result;
        }
        if (q_count($accepted) !== $this->plan->task_count()) { throw new \LogicException('Incomplete body phase'); }
        $bodies /** vector<Checked_Body> */ = [];
        for ($i = 0; $i < $this->plan->input_count(); $i++) {
            $input = $this->plan->input_at($i); $this->plan->require_input($input); $id = $input->callable_id;
            $body = $this->plan->previous->for_callable($id);
            if (isset($accepted[$id])) { $body = $accepted[$id]; }
            if ($body === null) { throw new \LogicException('Incomplete or stale body phase'); }
            if (!Body_Validity::is_current($body,$input,$this->plan->names,$this->plan->types)) { throw new \LogicException('Incomplete or stale body phase'); }
            $bodies[] = $body;
        }
        return new Body_Set($bodies);
    }
}
