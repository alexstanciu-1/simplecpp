<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Check accepted source effects against the existing native lifecycle profile. */
final class Export_Worker {
    public static function prepare(Export_Task $task): Export_Verification {
        $resource = $task->definition->ownership;
        if ($resource !== null) { if ($resource->path_count() !== 0) { Export_Worker::ownership($task); } }
        return new Export_Verification($task);
    }
    private static function ownership(Export_Task $task): void {
        $ownership = $task->ownership;
        if ($ownership === null) { throw new \LogicException('Missing source export ownership evidence'); }
        $summary = $ownership->summary; $role = $task->operation->kind;
        foreach ($summary->parameters() as $position => $parameter) {
            $incoming = \analyze_lifetimes\RESOURCE_EITHER;
            if (($position === 0) && \type_model\Lifecycle_Roles::creates_destination($role)) { $incoming = \analyze_lifetimes\RESOURCE_EMPTY; }
            foreach ($parameter->fields() as $transition) {
                if (Resource_States::intersect($transition->required,$incoming) !== $incoming) { throw new \RuntimeException('Source export has stronger ownership preconditions than the native lifecycle contract'); }
                if ($position === 1) {
                    if ($transition->mutates || ($transition->result !== \analyze_lifetimes\RESOURCE_IDENTITY)) { throw new \RuntimeException('Source export must preserve its const copy source'); }
                }
                $post = Resource_States::deterministic($transition->result);
                if ($role === \type_model\LIFECYCLE_DESTROY) { $post = Resource_States::compatible($transition->result,\analyze_lifetimes\RESOURCE_EMPTY); }
                if (Resource_States::intersect($post,$incoming) !== $incoming) { throw new \RuntimeException('Source export does not establish its lifecycle postcondition'); }
            }
        }
        foreach ($summary->distinct() as $pair) {
            $left = Resource_Locations::parameter_parts($pair->left); $right = Resource_Locations::parameter_parts($pair->right);
            if (!\type_model\Lifecycle_Roles::creates_destination($role) || ($left->position === $right->position)) { throw new \RuntimeException('Source export requires alias exclusions absent from the native lifecycle contract'); }
        }
    }
}
