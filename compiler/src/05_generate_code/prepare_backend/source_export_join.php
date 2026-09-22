<?php
declare(strict_types=1);
namespace prepare_backend;

/** Publish complete selected work privately; completion order never changes current membership order. */
final class Source_Export_Join {
    public function __construct(private readonly array $current /** hash<Source_Export_Task,int> */,
        private readonly array $previous /** hash<Source_Type_Export,int> */, private readonly array $selected /** hash<Source_Export_Task,int> */) {}
    public function join(array $results /** vector<Source_Type_Export> */): array /** hash<Source_Type_Export,int> */ {
        foreach ($this->selected as $id => $task) {
            if (!isset($this->current[$id])) { throw new \LogicException('Stale source export selection'); }
            if ($this->current[$id] !== $task) { throw new \LogicException('Stale source export selection'); }
        }
        $accepted /** hash<Source_Type_Export,int> */ = [];
        foreach ($results as $result) {
            $id = $result->task->layout->dependency->type_id;
            if (!isset($this->selected[$id])) { throw new \LogicException('Unexpected source export result'); }
            if (($result->task !== $this->selected[$id]) || isset($accepted[$id])) { throw new \LogicException('Duplicate or stale source export result'); }
            Source_Export_Preparation::validate($result); $accepted[$id] = $result;
        }
        if (q_count($accepted) !== q_count($this->selected)) { throw new \LogicException('Incomplete source export batch'); }
        $candidate /** hash<Source_Type_Export,int> */ = []; $keys /** hash<bool> */ = [];
        foreach ($this->current as $id => $task) {
            $key = $task->identity->key();
            if (($id !== $task->layout->dependency->type_id) || isset($keys[$key])) { throw new \LogicException('Duplicate or stale source export identity'); }
            if (isset($accepted[$id])) { $candidate[$id] = $accepted[$id]; }
            elseif (isset($this->previous[$id])) { $candidate[$id] = $this->previous[$id]; }
            else { throw new \LogicException('Missing source export contract'); }
            if (!Source_Export_Work::current($candidate[$id],$task)) { throw new \LogicException('Stale source export contract'); }
            $keys[$key] = true;
        }
        return $candidate;
    }
}
