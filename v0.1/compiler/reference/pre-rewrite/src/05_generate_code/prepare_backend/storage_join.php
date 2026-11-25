<?php
declare(strict_types=1);

/*
 * Role: Accept selected native storage ABI results and reuse exact unchanged inputs.
 * Used by: LLVM_Backend::finalize()
 * Call map: Storage_Join::join() -> Storage_Preparation::matches()
 * Writes: private accepted targets; input snapshots remain unchanged.
 */
namespace prepare_backend;

final class Storage_Join implements \compile\Join
{
    /** @param list<storage_preparation_task> $tasks Fixed selected primitive/configuration inputs. */
    public function __construct(private readonly ?\load_runtime\Runtime_Input_Set $runtime,
        private readonly backend_configuration $configuration, private readonly ?Backend_Context $previous,
        private readonly array $tasks)
    {
    }

    /** Accept exactly the selected results, then reuse only exact current primitive/configuration pairs.
     * @param list<prepared_storage_primitive> $results
     * @return array<string, prepared_storage_primitive> */
    public function join(array $results): array
    {
        $current = Storage_Preparation::primitives($this->runtime);
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $link = $task->primitive->link_name;
            if (isset($selected[$link]) || (($current[$link] ?? null) !== $task->primitive)
                || ($task->configuration !== $this->configuration)) {
                throw new \LogicException('Duplicate or stale storage preparation task');
            }
            $selected[$link] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $link = $result->task->primitive->link_name;
            if (($result->task !== ($selected[$link] ?? null)) || isset($accepted[$link])
                || !Storage_Preparation::matches($result)) {
                throw new \LogicException('Unexpected, duplicate or stale storage preparation result');
            }
            $accepted[$link] = $result;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete storage preparation');
        }
        $output = [];
        foreach ($current as $link => $primitive) {
            $result = $accepted[$link] ?? $this->previous?->storage_targets[$link] ?? null;
            if (($result?->task->primitive !== $primitive) || ($result->task->configuration !== $this->configuration)) {
                throw new \LogicException('Missing or stale storage primitive');
            }
            $output[$link] = $result;
        }
        return $output;
    }
}
