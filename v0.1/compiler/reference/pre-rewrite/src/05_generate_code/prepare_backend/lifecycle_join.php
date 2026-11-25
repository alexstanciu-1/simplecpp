<?php
declare(strict_types=1);

/*
 * Role: Accept selected lifecycle results and reuse current implicit ABI targets.
 * Used by: LLVM_Backend::finalize(), before Backend_Join
 * Call map: Lifecycle_Join::join()
 *   -> Callable_Contract::lifecycle_matches(); lifecycle_is_current()
 * Output: complete targets in current imported/source order; private tasks/results are not retained.
 */

namespace prepare_backend;

/** @compiler-internal Join lifecycle workers against fixed imported/source membership and backend configuration. */
final class Lifecycle_Join implements \compile\Join
{
    /**
     * Capture current membership, previous targets and selected work without validation or mutation.
     * @param list<lifecycle_preparation_task> $tasks
     */
    public function __construct(
        private readonly ?\load_runtime\Runtime_Input_Set $runtime,
        private readonly backend_configuration $configuration,
        private readonly ?Backend_Context $previous,
        private readonly array $tasks,
        private readonly array $source_operations = [],
    )
    {
    }

    /**
     * Accept exactly one current result per selected operation, in any completion order.
     * Reuse unselected current targets and omit removed operations; never mutate prior inputs.
     * @param list<lifecycle_preparation_result> $results
     * @return list<abi_target>
     */
    public function join(array $results): array
    {
        // Membership comes from accepted imported/source contracts, independently of selected work.
        $current = [];
        foreach ([...($this->runtime?->lifecycle_operations() ?? []), ...$this->source_operations] as $operation) {
            if (isset($current[$operation->link_name])) {
                throw new \LogicException('Duplicate lifecycle operation in current package');
            }
            $current[$operation->link_name] = $operation;
        }

        // Each task must name a current operation and this phase's fixed configuration.
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $link = $task->operation->link_name;
            if (isset($selected[$link]) || (($current[$link] ?? null) !== $task->operation)
                || ($task->configuration !== $this->configuration)) {
                throw new \LogicException('Duplicate or stale lifecycle preparation task');
            }
            $selected[$link] = $task;
        }

        // Validate provenance and ABI shape before accepting any replacements.
        $replacements = [];
        foreach ($results as $result)
        {
            $link = $result->task->operation->link_name;
            if (($result->task !== ($selected[$link] ?? null)) || isset($replacements[$link])
                || ($result->target->lifecycle_operation !== $result->task->operation)
                || !Callable_Contract::lifecycle_matches($result->target, $this->configuration)) {
                throw new \LogicException('Unexpected, duplicate or stale lifecycle preparation result');
            }
            $replacements[$link] = $result->target;
        }
        if (count($selected) !== count($replacements)) {
            throw new \LogicException('Incomplete lifecycle preparation results');
        }

        // Reconstruct contract order so worker completion order cannot affect exports or consumers.
        $targets = [];
        foreach ($current as $link => $operation)
        {
            if (isset($replacements[$link])) {
                $targets[] = $replacements[$link];
            }
            else {
                if (!Callable_Contract::lifecycle_is_current($this->previous, $operation, $this->configuration)) {
                    throw new \LogicException('Missing or stale unselected lifecycle target');
                }
                $targets[] = $this->previous->abi_for($link);
            }
        }
        return $targets;
    }
}
