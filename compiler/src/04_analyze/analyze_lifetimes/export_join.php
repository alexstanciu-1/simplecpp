<?php
declare(strict_types=1);
namespace analyze_lifetimes;
final class Export_Join {
    private array $current /** hash<Export_Task> */ = [];
    private array $selected /** hash<Export_Task> */ = [];
    private array $previous /** hash<Export_Verification> */ = [];
    public function __construct(array $current /** hash<Export_Task> */, array $selected /** hash<Export_Task> */, array $previous /** hash<Export_Verification> */) {
        foreach ($current as $key => $task) {
            if ($key !== $task->operation->link_name) { throw new \LogicException('Source export task key mismatch'); }
            $this->current[$key] = $task;
        }
        foreach ($selected as $key => $task) {
            if (!isset($this->current[$key])) { throw new \LogicException('Stale selected source export check'); }
            if ($this->current[$key] !== $task) { throw new \LogicException('Stale selected source export check'); }
            $this->selected[$key] = $task;
        }
        foreach ($previous as $key => $result) { $this->previous[$key] = $result; }
    }
    public function join(array $results /** vector<Export_Verification> */): array /** hash<Export_Verification> */ {
        $accepted /** hash<Export_Verification> */ = [];
        foreach ($results as $result) {
            $key = $result->task->operation->link_name;
            if (!isset($this->selected[$key])) { throw new \LogicException('Unexpected, duplicate or stale source export verification'); }
            if (($this->selected[$key] !== $result->task) || isset($accepted[$key])) { throw new \LogicException('Unexpected, duplicate or stale source export verification'); }
            $accepted[$key] = $result;
        }
        if (q_count($accepted) !== q_count($this->selected)) { throw new \LogicException('Incomplete source export verification'); }
        $complete /** hash<Export_Verification> */ = [];
        foreach ($this->current as $key => $task) {
            if (!isset($accepted[$key])) { if (isset($this->previous[$key])) { $accepted[$key] = $this->previous[$key]; } }
            if (!isset($accepted[$key])) { throw new \LogicException('Missing or stale source export verification'); }
            if (!Export_Join::current($accepted[$key],$task)) { throw new \LogicException('Missing or stale source export verification'); }
            $complete[$key] = $accepted[$key];
        }
        return $complete;
    }
    public static function current(Export_Verification $result, Export_Task $task): bool {
        $old = $result->task;
        if (($old->operation !== $task->operation) || ($old->definition !== $task->definition) || ($old->ownership !== $task->ownership)) { return false; }
        $bodies = $task->bodies(); $previous = $old->bodies();
        if (q_count($bodies) !== q_count($previous)) { return false; }
        foreach ($bodies as $id => $body) {
            if (!isset($previous[$id])) { return false; }
            if ($previous[$id] !== $body) { return false; }
        }
        return true;
    }
}
