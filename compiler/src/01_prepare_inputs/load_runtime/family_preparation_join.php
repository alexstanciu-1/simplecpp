<?php
declare(strict_types=1);

/*
 * Role: Accept measured storage and operations for selected semantic family instances.
 * Call map: Family_Preparation_Join::join() -> Family_Operations::validate() [each imported method]
 * Output: immutable package/type/callable associations; canonical allocation remains in resolve_types
 */
namespace load_runtime;

final class Family_Preparation_Join implements \compile\Join
{
    /** @param list<family_preparation_task> $tasks Fixed semantic instances without guessed storage. */
    public function __construct(private readonly array $tasks, private readonly \type_model\Type_Catalog $catalog)
    {
    }

    /** Accept complete private outputs before any result can make a dependent type ready. */
    public function join(array $results): array
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            $id = $task->context->instance_id;
            if (($id === 0) || isset($selected[$id]) || !($task->context->definition->external instanceof \type_model\family_declaration)) {
                throw new \LogicException('Invalid or duplicate family type task');
            }
            $selected[$id] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $context = $result->task->context;
            $id = $context->instance_id;
            if (($result->task !== ($selected[$id] ?? null)) || isset($accepted[$id]) || ($result->package->base_catalog !== $this->catalog)) {
                throw new \LogicException('Unexpected, duplicate or stale family type result');
            }
            $type = $result->package->type_for($result->type_id);
            $definition = $type->language_type;
            if (($type->storage->kind !== runtime_storage_kind::opaque_inline)
                || ($definition?->name !== $context->type_name()) || ($definition?->namespace_name !== $context->type_namespace())
                || ($definition?->lifetime === null)) {
                throw new \LogicException('Prepared family result lost its exact type binding');
            }
            $imported = [];
            foreach ($result->package->bindings?->imports ?? [] as $import)
            {
                $matches = false;
                foreach ($context->arguments as $argument) {
                    if (($argument->value === null) && ($argument->type === $import->type->language_type)) {
                        $matches = true;
                        break;
                    }
                }
                if (!$matches) {
                    throw new \LogicException('Prepared family import is not an exact argument type');
                }
                $imported[] = $import->type->language_type;
            }
            foreach ($result->package->bindings?->sources ?? [] as $export) {
                if (!in_array($export, $result->task->sources, true)) {
                    throw new \LogicException('Prepared family source is not a selected argument export');
                }
                $imported[] = $export->task->layout->definition;
            }
            foreach ($context->arguments as $argument)
            {
                $argument_type = $argument->type;
                if (($argument_type !== $this->catalog->find_type($argument_type->name, $argument_type->namespace_name))
                    && !in_array($argument_type, $imported, true)) {
                    throw new \LogicException('Prepared family result is missing an exact argument import');
                }
            }
            foreach ($result->task->operations as $operation) {
                if (!isset($result->operations[$operation])) {
                    throw new \LogicException('Incomplete family operation coverage');
                }
            }
            $calls = [];
            foreach ($result->package->callables() as $callable) {
                $calls[$callable->id] = $callable;
            }
            foreach ($result->operations as $operation => $callable) {
                if (($calls[$callable->id] ?? null) !== $callable) {
                    throw new \LogicException('Prepared family operation is not owned by its package');
                }
                Family_Operations::validate($result, $operation, $callable);
            }
            $accepted[$id] = $result;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete family type results');
        }
        $ordered = [];
        foreach ($selected as $id => $_) {
            $ordered[$id] = $accepted[$id];
        }
        return $ordered;
    }
}
