<?php
declare(strict_types=1);

/*
 * Role: Accept selected generated lifecycle definitions and reuse unchanged ones.
 * Call map: LLVM_Emitter::finalize() -> Lifecycle_Emission_Join::join()
 * Output: immutable operation definitions in canonical type/role order.
 */
namespace emit_llvm;

/** Accept selected generated definitions independently of source-function body emission. */
final class Lifecycle_Emission_Join implements \compile\Join
{
    /** @param array<string, emitted_lifecycle> $previous
     * @param list<lifecycle_emission_task> $tasks Selected fixed inputs. */
    public function __construct(private readonly \prepare_backend\Backend_Context $backend,
        private readonly array $previous, private readonly array $tasks)
    {
    }

    /** Validate membership, provenance and completeness before reusing or replacing private outputs.
     * @param list<emitted_lifecycle> $results
     * @return array<string, emitted_lifecycle> Accepted definitions keyed by exact link name. */
    public function join(array $results): array
    {
        // Membership comes from accepted type contracts, independently of selected work.
        $current = [];
        foreach ($this->backend->source_operations as $operation) {
            $current[$operation->link_name] = $operation;
        }
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $key = $task->operation->link_name;
            if (($task->backend !== $this->backend) || (($current[$key] ?? null) !== $task->operation) || isset($selected[$key])) {
                throw new \LogicException('Duplicate or stale source lifecycle task');
            }
            $selected[$key] = $task;
        }

        // Each private result must belong to the exact selected task and ABI snapshot.
        $accepted = [];
        foreach ($results as $result)
        {
            $key = $result->task->operation->link_name;
            if (($result->task !== ($selected[$key] ?? null)) || isset($accepted[$key])) {
                throw new \LogicException('Unexpected or duplicate source lifecycle result');
            }
            if ($result->references !== Lifecycle_Emission::references($result->task)) {
                throw new \LogicException('Missing or stale source lifecycle references');
            }
            $accepted[$key] = $result;
        }
        if (count($selected) !== count($accepted)) {
            throw new \LogicException('Incomplete source lifecycle emission');
        }

        // Restore canonical order and share valid unselected definitions without re-emission.
        $out = [];
        foreach ($current as $key => $operation)
        {
            $result = $accepted[$key] ?? $this->previous[$key] ?? null;
            if (($result?->task->operation !== $operation) || ($result->task->backend !== $this->backend)) {
                throw new \LogicException('Missing or stale source lifecycle definition');
            }
            $out[$key] = $result;
        }
        return $out;
    }
}
