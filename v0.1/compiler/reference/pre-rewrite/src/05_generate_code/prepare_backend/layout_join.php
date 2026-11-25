<?php
declare(strict_types=1);

/*
 * Role: Accept selected structural preparation results into a private candidate.
 * Call map: preparation coordinator -> Layout_Join::join()
 * Output: validated canonical contracts for downstream workers.
 */
namespace prepare_backend;

/** Accept exactly the selected layout batch and retain current unselected layouts. */
final class Layout_Join implements \compile\Join
{
    /** @param list<layout_task> $tasks Fixed selected record/target inputs. */
    public function __construct(private readonly Layout_Input $input, private readonly backend_configuration $configuration,
        private readonly array $previous, private readonly array $tasks)
    {
    }

    /** Check task identity, completeness and current membership before returning a private candidate map.
     * @param list<layout_result> $results Private target measurements.
     * @return array<int, storage_layout> Accepted layouts keyed by canonical type ID. */
    public function join(array $results): array
    {
        $current = [];
        foreach ($this->input->roots as $id) {
            $current[$id] = $this->input->definition_for_type($id);
        }
        $selected = [];
        foreach ($this->tasks as $task)
        {
            if (isset($selected[$task->type_id]) || (($current[$task->type_id] ?? null) !== $task->definition)
                || ($task->configuration !== $this->configuration) || ($task->input !== $this->input)) {
                throw new \LogicException('Duplicate or stale layout task');
            }
            if (!array_is_list($task->fields) || !array_is_list($task->field_types)
                || (count($task->fields) !== ($task->definition->representation->kind === \type_model\representation_kind::structure ? $task->definition->representation->payload->count : 0))
                || (count($task->field_types) !== count($task->fields))) {
                throw new \LogicException('Invalid selected layout fields');
            }
            if ($task->aligned !== Native_Layout::required($this->input, $task->type_id)) {
                throw new \LogicException('Selected layout policy disagrees with field storage');
            }
            foreach ($task->fields as $index => $field) {
                if (($field !== $this->input->field_for($task->type_id, $index))
                    || ($task->field_types[$index] !== LLVM_Types::compound($this->input, $field->type_id))) {
                    throw new \LogicException('Stale selected layout field contract');
                }
            }
            $selected[$task->type_id] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $id = $result->task->type_id;
            if (($result->task !== ($selected[$id] ?? null)) || isset($accepted[$id])) {
                throw new \LogicException('Unexpected, duplicate or stale layout result');
            }
            if (($result->layout->definition !== $result->task->definition)
                || ($result->layout->configuration !== $this->configuration)
                || ($result->layout->lineage !== $this->input->lineage)
                || ($result->layout->dependency !== $this->input->dependencies[$id])
                || ($result->layout->fields !== $result->task->fields)
                || ($result->layout->llvm_type !== ($result->task->aligned
                    ? '[' . $result->layout->size . ' x i8]' : Layout_Preparation::spelling($result->task)))) {
                throw new \LogicException('Layout result changed its selected field or target contracts');
            }
            $accepted[$id] = $result->layout;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete layout batch');
        }
        $layouts = [];
        foreach ($current as $id => $definition)
        {
            $result = $accepted[$id] ?? $this->previous[$id] ?? null;
            if (!Layout_Preparation::current($result, $this->input, $id, $this->configuration)) {
                throw new \LogicException('Missing or stale record layout');
            }
            $native = $definition->native_layout;
            if (($native !== null) && (($native->target_triple !== $this->configuration->target_triple)
                || ($native->data_layout !== $this->configuration->data_layout)
                || ($native->size !== $result->size) || ($native->alignment !== $result->alignment)
                || ($native->offsets !== $result->offsets))) {
                throw new \RuntimeException('Native record layout is incompatible with generated value storage: ' . $definition->name);
            }
            $layouts[$id] = $result;
        }
        return $layouts;
    }
}
