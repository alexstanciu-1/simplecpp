<?php
declare(strict_types=1);
namespace prepare_backend;

/** Accept exactly the selected batch into a private candidate; preserve current unselected owners. */
final class Layout_Join {
    public function __construct(private readonly Layout_Input $input, private readonly Backend_Configuration $configuration,
        private readonly array $previous /** hash<Storage_Layout,int> */, private readonly array $tasks /** vector<Layout_Task> */) {}
    private static function same_fields(array $left /** vector<\type_model\Type_Member> */, array $right /** vector<\type_model\Type_Member> */): bool {
        if (q_count($left) !== q_count($right)) { return false; }
        foreach ($left as $index => $field) { if ($field !== $right[$index]) { return false; } }
        return true;
    }
    public function join(array $results /** vector<Layout_Result> */): array /** hash<Storage_Layout,int> */ {
        $current /** hash<\type_model\Named_Definition,int> */ = [];
        foreach ($this->input->roots as $id) { $current[$id] = $this->input->definition_for_type($id); }
        $selected /** hash<Layout_Task,int> */ = [];
        foreach ($this->tasks as $task) {
            $id = $task->type_id;
            if (isset($selected[$id])) { throw new \LogicException('Duplicate or stale layout task'); }
            if (!isset($current[$id])) { throw new \LogicException('Duplicate or stale layout task'); }
            if (($current[$id] !== $task->definition) || ($task->configuration !== $this->configuration) || ($task->input !== $this->input)) {
                throw new \LogicException('Duplicate or stale layout task');
            }
            $count = 0;
            if ($task->definition->representation->kind() === \type_model\REPRESENTATION_STRUCTURE) { $count = $task->definition->representation->member_count(); }
            if ((q_count($task->fields) !== $count) || (q_count($task->field_types) !== $count)) { throw new \LogicException('Invalid selected layout fields'); }
            $ordinal = 0;
            foreach ($task->field_types as $index => $type) {
                if ($index !== $ordinal) { throw new \LogicException('Invalid selected layout fields'); } $ordinal = $ordinal+1;
            }
            $ordinal = 0;
            foreach ($task->fields as $index => $field) {
                if ($index !== $ordinal) { throw new \LogicException('Invalid selected layout fields'); } $ordinal = $ordinal+1;
                if (($field !== $this->input->field_for($id,$index)) || ($task->field_types[$index] !== LLVM_Storage::compound($this->input,$field->type_id))) {
                    throw new \LogicException('Stale selected layout field contract');
                }
            }
            if ($task->aligned !== LLVM_Storage::requires_native($this->input,$id)) { throw new \LogicException('Selected layout policy disagrees with field storage'); }
            $selected[$id] = $task;
        }
        $accepted /** hash<Storage_Layout,int> */ = [];
        foreach ($results as $result) {
            $id = $result->task->type_id;
            if (!isset($selected[$id])) { throw new \LogicException('Unexpected, duplicate or stale layout result'); }
            if (($result->task !== $selected[$id]) || isset($accepted[$id])) { throw new \LogicException('Unexpected, duplicate or stale layout result'); }
            $layout = $result->layout;
            $spelling = LLVM_Storage::compound($this->input,$id);
            if ($result->task->aligned) { $spelling = '[' . $layout->size . ' x i8]'; }
            if (($layout->definition !== $result->task->definition) || ($layout->configuration !== $this->configuration)
                || ($layout->lineage !== $this->input->lineage) || ($layout->dependency !== $this->input->dependencies[$id])
                || (!Layout_Join::same_fields($layout->fields,$result->task->fields)) || ($layout->llvm_type !== $spelling)) {
                throw new \LogicException('Layout result changed its selected field or target contracts');
            }
            $accepted[$id] = $layout;
        }
        if (q_count($accepted) !== q_count($selected)) { throw new \LogicException('Incomplete layout batch'); }
        $candidate /** hash<Storage_Layout,int> */ = [];
        foreach ($current as $id => $definition) {
            if (isset($accepted[$id])) { $candidate[$id] = $accepted[$id]; }
            elseif (isset($this->previous[$id])) { $candidate[$id] = $this->previous[$id]; }
            else { throw new \LogicException('Missing or stale record layout'); }
            $layout = $candidate[$id];
            if (!Layout_Capture::current($layout,$this->input,$id,$this->configuration)) { throw new \LogicException('Missing or stale record layout'); }
            $native = $definition->native_layout;
            if ($native !== null) {
                if (($native->target_triple !== $this->configuration->target_triple) || ($native->data_layout !== $this->configuration->data_layout)
                    || ($native->size !== $layout->size) || ($native->alignment !== $layout->alignment) || ($native->field_count() !== q_count($layout->offsets))) {
                    throw new \RuntimeException('Native record layout is incompatible with generated value storage: ' . $definition->name);
                }
                foreach ($layout->offsets as $index => $offset) {
                    if ($native->field_offset($index) !== $offset) { throw new \RuntimeException('Native record layout is incompatible with generated value storage: ' . $definition->name); }
                }
            }
        }
        return $candidate;
    }
}
