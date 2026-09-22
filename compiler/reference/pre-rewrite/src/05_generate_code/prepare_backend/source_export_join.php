<?php
declare(strict_types=1);

/*
 * Role: Accept a complete selected source export batch into a private candidate.
 * Call map: export coordinator -> Source_Export_Join::join()
 *   -> Source_Export_Preparation::validate(); current()
 * Output: shared current exports only; no native publication or retained mutable owners.
 */
namespace prepare_backend;

final class Source_Export_Join implements \compile\Join
{
    /** Current membership and selected work are fixed before workers run. */
    public function __construct(private readonly array $current, private readonly array $previous,
        private readonly array $selected)
    {
    }

    /** Accept arbitrary completion order, rejecting missing, duplicate, foreign and stale associations. */
    public function join(array $results): array
    {
        foreach ($this->selected as $id => $task) {
            if (($this->current[$id] ?? null) !== $task) {
                throw new \LogicException('Stale source export selection');
            }
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $id = $result->task->layout->dependency->type_id;
            if (($result->task !== ($this->selected[$id] ?? null)) || isset($accepted[$id])) {
                throw new \LogicException('Unexpected, duplicate or stale source export result');
            }
            Source_Export_Preparation::validate($result);
            $accepted[$id] = $result;
        }
        if (count($accepted) !== count($this->selected)) {
            throw new \LogicException('Incomplete source export batch');
        }
        $candidate = [];
        $keys = [];
        foreach ($this->current as $id => $task)
        {
            $result = $accepted[$id] ?? $this->previous[$id] ?? null;
            if (($id !== $task->layout->dependency->type_id) || isset($keys[$task->identity->key])
                || !Source_Export_Preparation::current($result, $task)) {
                throw new \LogicException('Missing, duplicate or stale source export contract');
            }
            $keys[$task->identity->key] = true;
            $candidate[$id] = $result;
        }
        return $candidate;
    }
}
