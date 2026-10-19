<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Validate a complete selected batch before exposing any current lifetime set. */
final class Lifetime_Join {
    private array $selected /** hash<\check_bodies\Checked_Body,int> */ = [];
    public function __construct(private readonly \check_bodies\Body_Set $bodies, private readonly Lifetime_Set $previous,
        array $tasks /** vector<\check_bodies\Checked_Body> */, private readonly Lifetime_Inputs $ownership) {
        foreach ($tasks as $task) {
            $id = $task->callable_id;
            if (isset($this->selected[$id]) || ($bodies->for_callable($id) !== $task)) { throw new \LogicException('Duplicate or stale lifetime task'); }
            $this->selected[$id] = $task;
        }
    }
    public function join(array $results /** vector<Analyzed_Body> */): Lifetime_Set {
        $replacements /** hash<Analyzed_Body,int> */ = [];
        foreach ($results as $result) {
            $id = $result->body->callable_id;
            if (!isset($this->selected[$id])) { throw new \LogicException('Unexpected, duplicate or stale lifetime result'); }
            if (($this->selected[$id] !== $result->body) || isset($replacements[$id])) { throw new \LogicException('Unexpected, duplicate or stale lifetime result'); }
            $replacements[$id] = $result;
        }
        if (q_count($this->selected) !== q_count($replacements)) { throw new \LogicException('Incomplete lifetime phase'); }
        $current /** vector<Analyzed_Body> */ = [];
        foreach ($this->bodies->bodies() as $body) {
            $id = $body->callable_id; $result = $this->previous->for_callable($id);
            if (isset($replacements[$id])) { $result = $replacements[$id]; }
            if ($result === null) { throw new \LogicException('Incomplete or stale lifetime phase'); }
            if (($result->body !== $body) || ($result->ownership !== $this->ownership->for_body($id))) { throw new \LogicException('Incomplete or stale lifetime phase'); }
            $current[] = $result;
        }
        return new Lifetime_Set($current,$this->ownership->results());
    }
}
