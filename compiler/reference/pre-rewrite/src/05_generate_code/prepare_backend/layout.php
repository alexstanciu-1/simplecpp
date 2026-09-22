<?php
declare(strict_types=1);

/*
 * Role: Select and execute target-layout work with private tool processes.
 * Call map: Layout_Coordinator::prepare() -> capture(); subset(); select(); prepare() [each task]
 * Flow: private results -> Layout_Join::join()
 * Inputs: canonical fields and fixed target/tool command; output: measured record layouts.
 */
namespace prepare_backend;

use tool_process\Tool_Process;

/** Workers own their probe processes; no mutable session tool/cache is shared with them. */
final class Layout_Preparation
{
    /** Capture only the selected roots and reachable storage rows before any worker executes. */
    public static function capture(\type_model\Type_Store $types, array $roots, array $known = []): Layout_Input
    {
        $roots = array_values(array_unique($roots));
        sort($roots);
        $dependencies = [];
        $active = [];
        $pending = array_map(static fn($id) => [$id, false], $roots);
        while ($pending !== [])
        {
            [$id, $ready] = array_pop($pending);
            if (isset($dependencies[$id])) {
                continue;
            }
            if ($ready)
            {
                [$definition, $fields, $children] = $active[$id];
                $links = [];
                foreach ($children as $child) {
                    $links[$child] = $dependencies[$child];
                }
                $old = $known[$id] ?? null;
                $dependencies[$id] = (($old?->definition === $definition) && ($old->fields === $fields) && ($old->children === $links))
                    ? $old : new layout_dependency($id, $definition, $fields, $links);
                unset($active[$id]);
                continue;
            }
            if (isset($active[$id])) {
                throw new \LogicException('Cyclic selected layout dependency');
            }
            $definition = $types->definition_for_type($id);
            $fields = [];
            $children = [];
            $shape = $definition->representation;
            if ($shape->kind === \type_model\representation_kind::structure)
            {
                for ($index = 0; $index < $shape->payload->count; ++$index) {
                    $field = $types->field_for($id, $index);
                    $fields[] = $field;
                    $children[] = $field->type_id;
                }
            }
            elseif ($shape->kind === \type_model\representation_kind::fixed_array) {
                $children[] = $shape->payload->element_type;
            }
            $active[$id] = [$definition, $fields, $children];
            $pending[] = [$id, true];
            foreach (array_reverse($children) as $child) {
                $pending[] = [$child, false];
            }
        }
        ksort($dependencies);
        return new Layout_Input($types->lineage, $roots, $dependencies);
    }

    /** Project ready roots onto the shared dependency graph without keeping unrelated rows in worker inputs. */
    public static function subset(Layout_Input $input, array $roots): Layout_Input
    {
        $nodes = [];
        $pending = $roots;
        while ($pending !== [])
        {
            $id = array_pop($pending);
            if (isset($nodes[$id])) {
                continue;
            }
            $node = $input->dependencies[$id];
            $nodes[$id] = $node;
            foreach ($node->children as $child) {
                $pending[] = $child->type_id;
            }
        }
        ksort($nodes);
        return new Layout_Input($input->lineage, $roots, $nodes);
    }

    /** Select changed definition/target contracts from one immutable dependency batch.
     * @param array<int, storage_layout> $previous
     * @return list<layout_task> Fixed record/target/tool inputs. */
    public static function select(Layout_Input $input, backend_configuration $configuration,
        array $previous, bool $full, array $command, string $launcher, array $native_command = []): array
    {
        $tasks = [];
        foreach ($input->roots as $id)
        {
            if ((!$full) && self::current($previous[$id] ?? null, $input, $id, $configuration)) {
                continue;
            }
            $fields = $input->fields_for($id);
            $field_types = array_map(static fn($field) => LLVM_Types::compound($input, $field->type_id), $fields);
            $tasks[] = new layout_task($id, $input->definition_for_type($id), $fields, $field_types,
                $configuration, $command, $launcher, $input, $native_command, Native_Layout::required($input, $id));
        }
        return $tasks;
    }

    /** Numeric IDs alone cannot authorize reuse across type-store lineages. */
    public static function current(?storage_layout $layout, Layout_Input $input, int $id, backend_configuration $configuration): bool
    {
        return ($layout?->lineage === $input->lineage) && ($layout->definition === $input->definition_for_type($id))
            && ($layout->configuration === $configuration)
            && self::dependencies_match($layout->dependency, $input->dependencies[$id]);
    }

    /** Compare exact constituent contracts iteratively, visiting shared child pairs only once. */
    public static function dependencies_match(layout_dependency $left, layout_dependency $right): bool
    {
        $pending = [[$left, $right]];
        $seen = [];
        while ($pending !== [])
        {
            [$left, $right] = array_pop($pending);
            if (($left === $right) || isset($seen[$left->type_id])) {
                continue;
            }
            if (($left->type_id !== $right->type_id) || ($left->definition !== $right->definition)
                || ($left->fields !== $right->fields) || (array_keys($left->children) !== array_keys($right->children))) {
                return false;
            }
            $seen[$left->type_id] = true;
            foreach ($left->children as $id => $child) {
                $pending[] = [$child, $right->children[$id]];
            }
        }
        return true;
    }

    /** Record layouts and demanded scalar element layouts use the same selected measurement path. */
    public static function definitions(\type_model\Type_Store $types): array
    {
        $definitions = $types->structures();
        foreach ($types->element_storages() as $storage) {
            $element = $storage->element_storage;
            $definitions[$element->element_type] = $element->element;
        }
        ksort($definitions);
        return $definitions;
    }

    public static function spelling(layout_task $task): string
    {
        return LLVM_Types::compound($task->input, $task->type_id);
    }

    /** Ask the configured target to fold layout constants; never execute target machine code. */
    public static function prepare(layout_task $task): layout_result
    {
        $type = self::spelling($task);
        $ir = 'target triple = ' . LLVM_Types::quote($task->configuration->target_triple) . "\n"
            . 'target datalayout = ' . LLVM_Types::quote($task->configuration->data_layout) . "\n";
        $expressions = ['size' => 'getelementptr (' . $type . ', ptr null, i32 1)',
            'alignment' => 'getelementptr ({ i8, ' . $type . ' }, ptr null, i32 0, i32 1)'];
        foreach ($task->fields as $index => $field) {
            $expressions['field_' . $index] = 'getelementptr (' . $type . ', ptr null, i32 0, i32 ' . $index . ')';
        }
        foreach ($expressions as $name => $expression) {
            $ir .= '@' . $name . ' = constant i64 ptrtoint (ptr ' . $expression . " to i64)\n";
        }

        // Opaque field alignment is not represented by its byte-array LLVM type.
        // Clang measures layout-only aligned shells; it owns no source lifecycle.
        $primitives = [];
        $native = !$task->aligned ? null : Native_Layout::source($task->input, $task->type_id, $primitives);
        if (($native !== null) && ($task->native_command === [])) {
            throw new \LogicException('Aligned layout requires its selected native tool command');
        }
        $output = self::measure($task, $native ?? $ir, $native === null ? $task->command : $task->native_command);
        if ($native !== null) {
            self::verify_primitives($task, $primitives, $output);
        }
        $facts = [];
        foreach ($expressions as $name => $_)
        {
            if (!preg_match('/^@' . $name . ' = [^\n]*constant i64 ([0-9]+)\b/m', $output, $match)) {
                throw new \RuntimeException('Target did not resolve layout fact: ' . $name);
            }
            $integer = filter_var($match[1], FILTER_VALIDATE_INT);
            if ($integer === false) {
                throw new \RuntimeException('Target layout exceeds host index capacity');
            }
            $facts[$name] = $integer;
        }
        return new layout_result($task, new storage_layout($task->definition, $task->configuration, $task->fields,
            $native === null ? $type : '[' . $facts['size'] . ' x i8]', $facts['size'], $facts['alignment'], array_slice(array_values($facts), 2), $task->input->lineage, $task->input->dependencies[$task->type_id]));
    }

    /** Run the selected probe privately and require the exact configured target facts. */
    private static function measure(layout_task $task, string $source, array $command): string
    {
        $process = new Tool_Process($command, $source, $task->launcher);
        try {
            while (!$process->ready()) {
                usleep(1000);
            }
            $output = $process->result();
        }
        finally {
            $process->close();
        }
        if (!str_contains($output, 'target triple = ' . LLVM_Types::quote($task->configuration->target_triple))
            || !str_contains($output, 'target datalayout = ' . LLVM_Types::quote($task->configuration->data_layout))) {
            throw new \RuntimeException('Layout probe changed the selected target configuration');
        }
        return $output;
    }

    /** A C++ shell is usable only if every primitive has the same size/alignment as emitted LLVM storage. */
    private static function verify_primitives(layout_task $task, array $primitives, string $native): void
    {
        $ir = 'target triple = ' . LLVM_Types::quote($task->configuration->target_triple) . "\n"
            . 'target datalayout = ' . LLVM_Types::quote($task->configuration->data_layout) . "\n";
        $names = [];
        foreach ($primitives as $id => $type)
        {
            $queries = ['primitive_size_' . $id => 'getelementptr (' . $type . ', ptr null, i32 1)',
                'primitive_alignment_' . $id => 'getelementptr ({ i8, ' . $type . ' }, ptr null, i32 0, i32 1)'];
            foreach ($queries as $name => $query) {
                $names[] = $name;
                $ir .= '@' . $name . ' = constant i64 ptrtoint (ptr ' . $query . " to i64)\n";
            }
        }
        if ($names === []) {
            return;
        }
        $llvm = self::measure($task, $ir, $task->command);
        foreach ($names as $name) {
            $pattern = '/^@' . $name . ' = [^\n]*constant i64 ([0-9]+)\b/m';
            if (!preg_match($pattern, $llvm, $left) || !preg_match($pattern, $native, $right) || ($left[1] !== $right[1])) {
                throw new \RuntimeException('Native layout primitive disagrees with LLVM: ' . $name);
            }
        }
    }

}
