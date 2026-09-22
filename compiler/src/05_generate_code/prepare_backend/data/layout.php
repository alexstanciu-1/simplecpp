<?php
declare(strict_types=1);
namespace prepare_backend;

/** Shared immutable dependency DAG node; no AST or mutable canonical store is retained. */
final class Layout_Dependency {
    public function __construct(public readonly int $type_id, public readonly \type_model\Named_Definition $definition,
        public readonly array $fields /** vector<\type_model\Type_Member> */,
        public readonly array $children /** hash<Layout_Dependency,int> */) {}
}

/** Batch-owned membership, sharing exact accepted dependency rows and canonical lineage. */
final class Layout_Input {
    public function __construct(public readonly \type_model\Type_Lineage $lineage,
        public readonly array $roots /** vector<int> */, public readonly array $dependencies /** hash<Layout_Dependency,int> */) {}
    private function dependency(int $id): Layout_Dependency {
        if (!isset($this->dependencies[$id])) { throw new \LogicException('Missing selected layout dependency'); }
        return $this->dependencies[$id];
    }
    public function definition_for_type(int $id): \type_model\Named_Definition { return $this->dependency($id)->definition; }
    public function representation_for_type(int $id): \type_model\Representation { return $this->definition_for_type($id)->representation; }
    public function fields_for(int $id): array /** vector<\type_model\Type_Member> */ { return $this->dependency($id)->fields; }
    public function field_for(int $id, int $index): \type_model\Type_Member {
        $fields = $this->fields_for($id);
        if (($index < 0) || ($index >= q_count($fields))) { throw new \LogicException('Missing selected layout field'); }
        return $fields[$index];
    }
}

/** Selected task retains the exact input snapshot, configuration and tool arguments. */
final class Layout_Task {
    public function __construct(public readonly int $type_id, public readonly \type_model\Named_Definition $definition,
        public readonly array $fields /** vector<\type_model\Type_Member> */, public readonly array $field_types /** vector<string> */,
        public readonly Backend_Configuration $configuration, public readonly array $command /** vector<string> */,
        public readonly Layout_Input $input,
        public readonly array $native_command /** vector<string> */, public readonly bool $aligned = false) {}
}

/** Validated measured storage plus accepted semantic/dependency provenance. No tool task is retained. */
final class Storage_Layout {
    public function __construct(public readonly \type_model\Named_Definition $definition, public readonly Backend_Configuration $configuration,
        public readonly array $fields /** vector<\type_model\Type_Member> */, public readonly string $llvm_type,
        public readonly int $size, public readonly int $alignment, public readonly array $offsets /** vector<int> */,
        public readonly \type_model\Type_Lineage $lineage, public readonly Layout_Dependency $dependency) {
        if (($size < 1) || ($alignment < 1)) { throw new \LogicException('Invalid measured record layout'); }
        $power = 1;
        while ($power < $alignment) {
            if ($power > $alignment - $power) { throw new \LogicException('Invalid measured record layout'); }
            $power = $power * 2;
        }
        if (($size % $alignment) !== 0) { throw new \LogicException('Invalid measured record layout'); }
        if (q_count($offsets) !== q_count($fields)) { throw new \LogicException('Invalid measured record layout'); }
        $ordinal = 0;
        foreach ($fields as $index => $field) {
            if ($index !== $ordinal) { throw new \LogicException('Invalid measured record layout'); }
            $ordinal = $ordinal + 1;
        }
        $last = -1; $ordinal = 0;
        foreach ($offsets as $index => $offset) {
            if (!q_is_int($offset)) { throw new \LogicException('Invalid measured field offset'); }
            if ($index !== $ordinal) { throw new \LogicException('Invalid measured record layout'); }
            if (($offset < 0) || ($offset === $last) || ($offset < $last) || ($offset >= $size)) { throw new \LogicException('Invalid measured field offset'); }
            $last = $offset; $ordinal = $ordinal + 1;
        }
    }
}

/** Private result keeps the exact selection task until the layout join accepts it. */
final class Layout_Result {
    public function __construct(public readonly Layout_Task $task, public readonly Storage_Layout $layout) {}
}
