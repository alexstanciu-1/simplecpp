<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation\Files;

/** Accept a complete selected batch before any candidate publication. */
final class Join implements \compile\Join
{
    /** @param list<preparation_task> $tasks */
    public function __construct(private readonly array $tasks)
    {
    }

    /** Validate task selection, fixed inputs and sealed measured output as one private batch.
     * @param list<preparation_result> $results @return array<string, accepted_specialization> */
    public function join(array $results): array
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            if (isset($selected[$task->key])) {
                throw new \RuntimeException('Duplicate selected specialization');
            }
            $selected[$task->key] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $task = $selected[$result->task->key] ?? null;
            if (($task !== $result->task) || isset($accepted[$task->key])) {
                throw new \RuntimeException('Unselected, stale or duplicate family result');
            }
            if (Files::hashes(array_keys($task->input_hashes)) !== $task->input_hashes) {
                throw new \RuntimeException('Selected family inputs changed before acceptance');
            }
            $candidate = $result->candidate;
            if (($candidate->configuration !== $task->configuration) || ($candidate->request_contract !== $task->receipt) || ($candidate->project !== $task->project)
                || (Files::read($candidate->directory . '/request.json') !== $task->receipt)) {
                throw new \RuntimeException('Family candidate context or coverage mismatch');
            }
            $facts = $candidate->validate();
            $accepted[$task->key] = new accepted_specialization($task, $facts['types'], $facts['operations'], $facts['manifest'], $candidate);
        }
        if (count($accepted) !== count($selected)) {
            throw new \RuntimeException('Missing selected family result');
        }
        return $accepted;
    }
}
