<?php
declare(strict_types=1);

/*
 * Role: Check complete source effects against the native payload lifecycle profile.
 * Call map: Export_Verification -> Export_Worker::prepare() -> ownership()
 * Output: private evidence; no body analysis, native preparation or state mutation.
 */
namespace analyze_lifetimes;

final class Export_Worker
{
    /** Current bodies were accepted by lifetime analysis; resource summaries must also fit native callers. */
    public static function prepare(export_task $task): export_verification
    {
        if ($task->definition->resource_paths !== []) {
            self::ownership($task);
        }
        return new export_verification($task);
    }

    /** Native callers know live objects, not source-local allocation preconditions or alias exclusions. */
    private static function ownership(export_task $task): void
    {
        $summary = $task->ownership?->summary ?? throw new \LogicException('Missing source export ownership evidence');
        $role = $task->operation->kind;
        foreach ($summary->parameters as $position => $fields)
        {
            $incoming = ($position === 0) && $role->creates_destination() ? Resource_States::EMPTY : Resource_States::EITHER;
            foreach ($fields as $transition)
            {
                if (($transition->required & $incoming) !== $incoming) {
                    throw new \RuntimeException('Source export has stronger ownership preconditions than the native lifecycle contract');
                }
                if (($position === 1) && (($transition->mutates) || ($transition->result !== Resource_States::IDENTITY))) {
                    throw new \RuntimeException('Source export must preserve its const copy source');
                }
                $post = $role === \type_model\lifecycle_operation_kind::destroy
                    ? Resource_States::compatible($transition->result, Resource_States::EMPTY)
                    : Resource_States::deterministic($transition->result);
                if (($post & $incoming) !== $incoming) {
                    throw new \RuntimeException('Source export does not establish its lifecycle postcondition');
                }
            }
        }

        // Construction promises disjoint operands. Assignment also permits self-assignment,
        // so an inferred cross-operand exclusion cannot be exported for that role.
        foreach ($summary->distinct as [$left, $right]) {
            [$a] = Resource_Locations::parameter_parts($left);
            [$b] = Resource_Locations::parameter_parts($right);
            if ((!$role->creates_destination()) || ($a === $b)) {
                throw new \RuntimeException('Source export requires alias exclusions absent from the native lifecycle contract');
            }
        }
    }
}
