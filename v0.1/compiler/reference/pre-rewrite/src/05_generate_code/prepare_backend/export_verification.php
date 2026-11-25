<?php
declare(strict_types=1);

/*
 * Role: Bind declared exports to current analysis and schedule their verification.
 * Call map: Project_Exports -> Export_Verification::prepare() -> capture() -> bodies()
 *   prepare() -> Export_Worker::prepare(); Export_Join::join()
 * Output: fixed evidence for all advertised complete operations, before source emission.
 */
namespace prepare_backend;

use analyze_lifetimes\Export_Join;
use analyze_lifetimes\Export_Worker;
use analyze_lifetimes\export_task;

final class Export_Verification
{
    /** Select against fixed current analyses, then accept private checks without mutating native contracts. */
    public static function prepare(array $operations, \type_model\Type_Store $types,
        ?\check_bodies\Body_Set $bodies, ?\analyze_lifetimes\Lifetime_Set $lifetimes, array $previous, bool $full): array
    {
        $current = self::capture($operations, $types, $bodies, $lifetimes);
        $selected = [];
        foreach ($current as $key => $task) {
            if ($full || !Export_Join::current($previous[$key] ?? null, $task)) {
                $selected[$key] = $task;
            }
        }
        return (new Export_Join($current, $selected, $previous))->join(array_map(Export_Worker::prepare(...), $selected));
    }

    /** Capture reachable custom bodies and the complete ownership summary once per advertised operation. */
    public static function capture(array $operations, \type_model\Type_Store $types,
        ?\check_bodies\Body_Set $bodies, ?\analyze_lifetimes\Lifetime_Set $lifetimes): array
    {
        if ($operations === []) {
            return [];
        }
        // Member declarations are identified with their concrete receiver; names
        // and favorable layout equality cannot bind an implementation.
        $members = [];
        foreach ($bodies?->bodies() ?? [] as $body) {
            if (($body->owner->owner_symbol_id !== 0) && ($body->entry_parameter_count() > 0)) {
                $members[$body->local_type_for(1)][$body->owner->symbol_id] = $body;
            }
        }

        $tasks = [];
        foreach ($operations as $operation)
        {
            $definition = $types->definition_for_type($operation->type_id);
            $analyses = self::bodies($operation, $types, $members, $lifetimes);
            $ownership = null;
            // Complete summaries already include custom-body and field effects in
            // execution order. The export worker checks this result, not the body again.
            if ($definition->resource_paths !== [])
            {
                $key = \analyze_lifetimes\Ownership_Preparation::lifecycle_key($operation->kind, $operation->type_id);
                $ownership = $lifetimes?->ownership_results[$key] ?? null;
                $subject = $ownership?->task->subject;
                if (!($subject instanceof \analyze_lifetimes\ownership_lifecycle) || ($subject->definition !== $definition)
                    || ($subject->type_id !== $operation->type_id) || ($subject->kind !== $operation->kind)) {
                    throw new \LogicException('Missing or stale source export ownership analysis');
                }
            }
            $tasks[$operation->link_name] = new export_task($operation, $definition, $analyses, $ownership);
        }
        return $tasks;
    }

    /** Complete plans may contain nested custom operations; each must have its exact current lifetime result. */
    private static function bodies(\type_model\source_lifecycle_operation $root, \type_model\Type_Store $types,
        array $members, ?\analyze_lifetimes\Lifetime_Set $lifetimes): array
    {
        $pending = [$root];
        $seen = [];
        $analyses = [];
        while ($pending !== [])
        {
            $operation = array_pop($pending);
            if (isset($seen[$operation->link_name])) {
                continue;
            }
            $seen[$operation->link_name] = true;
            if ($operation->body_symbol_id !== 0)
            {
                $body = $members[$operation->type_id][$operation->body_symbol_id] ?? null;
                $analysis = $body === null ? null : $lifetimes?->for_callable($body->callable_id);
                if (($body === null) || ($analysis?->body !== $body)
                    || ($body->definition_for($operation->type_id) !== $types->definition_for_type($operation->type_id))) {
                    throw new \LogicException('Missing or stale source export body analysis');
                }
                $analyses[$body->callable_id] = $analysis;
            }
            foreach ($operation->members as $member) {
                if ($member->operation instanceof \type_model\source_lifecycle_operation) {
                    $pending[] = $member->operation;
                }
            }
        }
        ksort($analyses);
        return $analyses;
    }
}
