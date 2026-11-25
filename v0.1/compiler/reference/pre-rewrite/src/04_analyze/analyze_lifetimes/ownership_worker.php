<?php
declare(strict_types=1);

/*
 * Role: Prepare one fixed body or complete lifecycle ownership contract privately.
 * Used by: Ownership_Preparation ready batches
 * Call map: prepare() -> Allocation_Flow::analyze(); summary() [body task]
 *   prepare() -> lifecycle() -> consume() [complete lifecycle task]
 */
namespace analyze_lifetimes;

final class Ownership_Worker
{
    /** No type store, scheduler or retained result is writable from this task. */
    public static function prepare(ownership_task $task): ownership_result
    {
        if ($task->subject instanceof \check_bodies\Checked_Body) {
            $flow = new Allocation_Flow($task->subject, $task->dependencies);
            $allocations = $flow->analyze();
            return new ownership_result($task, $flow->summary(), $allocations);
        }
        return new ownership_result($task, self::lifecycle($task));
    }

    /** Follow executable lifecycle order while proving even fields with no destructor operation. */
    private static function lifecycle(ownership_task $task): ownership_summary
    {
        $subject = $task->subject;
        $construct = $subject->kind->creates_destination();
        $has_source = $subject->kind->has_source();
        $parameters = [0 => []];
        $distinct = [];
        foreach ($subject->definition->resource_paths as $path)
        {
            $key = Resource_Locations::path_key($path);
            $parameters[0][$key] = new resource_transition($construct ? Resource_States::EMPTY : Resource_States::EITHER,
                $construct ? Resource_States::EMPTY_VALUE : Resource_States::IDENTITY, false, false);
            if ($has_source) {
                $parameters[1][$key] = new resource_transition(Resource_States::EITHER, Resource_States::IDENTITY, false, false);
            }
        }
        $body = $subject->body_id === null ? null : $task->dependencies['body:' . $subject->body_id];
        if (($body !== null) && ($subject->order->body_before_members)) {
            self::consume($parameters, $distinct, $body, '', $subject);
        }
        foreach ($subject->children as $index => $dependency) {
            self::consume($parameters, $distinct, $task->dependencies[$dependency], (string)$index, $subject);
        }
        if (($body !== null) && (!$subject->order->body_before_members)) {
            self::consume($parameters, $distinct, $body, '', $subject);
        }
        foreach ($parameters[0] as $path => $transition)
        {
            $allowed = $transition->required & ($subject->kind === \type_model\lifecycle_operation_kind::destroy
                ? Resource_States::compatible($transition->result, Resource_States::EMPTY) : Resource_States::deterministic($transition->result));
            if ($allowed === 0) {
                throw new \RuntimeException('Complete lifecycle cannot discharge allocation field ' . $subject->definition->name . ':' . $path);
            }
            $parameters[0][$path] = new resource_transition($allowed, $transition->result, $transition->mutates, ($transition->accessed) || ($allowed !== Resource_States::EITHER));
        }
        ksort($distinct);
        return new ownership_summary($parameters, $distinct);
    }

    /** Compose field-local requirements; incoming states remain independent, never Cartesian products. */
    private static function consume(array &$parameters, array &$distinct, ownership_summary $summary, string $prefix, ownership_lifecycle $subject): void
    {
        foreach ($summary->distinct as $pair)
        {
            $mapped = [];
            foreach ($pair as $key) {
                [$position, $path] = Resource_Locations::parameter_parts($key);
                $mapped[] = Resource_Locations::parameter_key($position, Resource_Locations::prefix($prefix, $path));
            }
            $mapped = Resource_Locations::distinct_pair(...$mapped);
            $distinct[Resource_Locations::distinct_key($mapped)] = $mapped;
        }

        foreach ($summary->parameters as $position => $fields)
        {
            foreach ($fields as $path => $next)
            {
                $key = Resource_Locations::prefix($prefix, $path);
                $previous = $parameters[$position][$key] ?? throw new \LogicException('Lifecycle resource path mismatch');
                $required = $previous->required & Resource_States::compatible($previous->result, $next->required);
                if ($required === 0) {
                    throw new \RuntimeException('Unsatisfied complete lifecycle ownership requirement: ' . $subject->definition->name . ':' . $key);
                }
                $parameters[$position][$key] = new resource_transition($required, Resource_States::compose($previous->result, $next->result), ($previous->mutates) || ($next->mutates), ($previous->accessed) || ($next->accessed));
            }
        }
    }
}
