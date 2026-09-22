<?php
declare(strict_types=1);

/*
 * Role: Structural preparation inputs and outputs.
 * Used by: Layout_Preparation; Layout_Join; lowering and emission
 * Flow: captured shared dependency graph -> fixed tasks -> private results -> accepted layouts
 */
namespace prepare_backend;

/** Shared storage dependency node; child records form an immutable DAG without retained compiler stores. */
final class layout_dependency {
    /** @param list<\type_model\type_member> $fields @param array<int, layout_dependency> $children */
    public function __construct(public readonly int $type_id, public readonly \type_model\named_type_definition $definition,
        public readonly array $fields, public readonly array $children)
    {
    }
}

/** Fixed dependency rows for one batch; contains no mutable canonical store or AST. */
final class Layout_Input
{
    /** Captured rows are shared read-only; indexes and root membership belong to this batch.
     * @param list<int> $roots @param array<int, layout_dependency> $dependencies */
    public function __construct(public readonly \type_model\type_lineage $lineage, public readonly array $roots,
        public readonly array $dependencies)
    {
    }

    public function definition_for_type(int $id): \type_model\named_type_definition
    {
        return ($this->dependencies[$id] ?? throw new \LogicException('Missing selected layout dependency'))->definition;
    }

    public function representation_for_type(int $id): \type_model\representation_record
    {
        return $this->definition_for_type($id)->representation;
    }

    public function fields_for(int $id): array
    {
        return ($this->dependencies[$id] ?? throw new \LogicException('Missing selected layout dependency'))->fields;
    }

    public function field_for(int $id, int $index): \type_model\type_member
    {
        return $this->fields_for($id)[$index] ?? throw new \LogicException('Missing selected layout field');
    }
}

/** One selected canonical record, shared fields and immutable target/tool inputs. */
final class layout_task
{
    /** Fixed worker inputs; field rows are shared, physical spellings are selected once.
     * @param list<\type_model\type_member> $fields
     * @param list<string> $field_types
     * @param list<string> $command */
    public function __construct(public readonly int $type_id, public readonly \type_model\named_type_definition $definition,
        public readonly array $fields, public readonly array $field_types, public readonly backend_configuration $configuration,
        public readonly array $command, public readonly string $launcher,
        public readonly Layout_Input $input, public readonly array $native_command = [],
        public readonly bool $aligned = false)
    {
    }
}

/** Accepted physical storage facts, independent of source/provider declaration syntax. */
final class storage_layout
{
    /** Validate the compact accepted layout; retain dependency provenance, never selected tools/tasks.
     * @param list<\type_model\type_member> $fields Shared semantic field rows.
     * @param list<int> $offsets Target byte offsets in declaration order. */
    public function __construct(public readonly \type_model\named_type_definition $definition, public readonly backend_configuration $configuration,
        public readonly array $fields, public readonly string $llvm_type,
        public readonly int $size, public readonly int $alignment, public readonly array $offsets,
        public readonly \type_model\type_lineage $lineage, public readonly layout_dependency $dependency)
    {
        if (($size <= 0) || ($alignment <= 0) || (($alignment & ($alignment - 1)) !== 0)
            || (($size % $alignment) !== 0) || !array_is_list($offsets) || !array_is_list($fields)
            || (count($offsets) !== count($fields))) {
            throw new \LogicException('Invalid measured record layout');
        }
        $last = -1;
        foreach ($offsets as $offset) {
            if (!is_int($offset) || ($offset < 0) || ($offset <= $last) || ($offset >= $size)) {
                throw new \LogicException('Invalid measured field offset');
            }
            $last = $offset;
        }
    }
}

/** Private output retains exact selection provenance until join acceptance. */
final class layout_result {
    public function __construct(public readonly layout_task $task, public readonly storage_layout $layout)
    {
    }
}
