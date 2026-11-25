<?php
declare(strict_types=1);
namespace prepare_backend;

/** Fixed-task worker and selection/reuse; capture from type/layout stores remains a separate prerequisite. */
final class Source_Export_Work {
    public static function prepare(Source_Export_Task $task): Source_Type_Export {
        $operations /** hash<Source_Operation_Export> */ = [];
        foreach ($task->capabilities as $name => $capability) {
            $operation = $capability->operation;
            if ($operation === null) { $operations[$name] = new Source_Operation_Export($capability,null,null); }
            else {
                $prepared = Callable_Preparer::prepare_lifecycle(new Lifecycle_Preparation_Task($operation,$task->layout->configuration));
                $implementation = $prepared->target;
                $import = new Abi_Target(Source_Export_Preparation::symbol($task->identity,$capability->role),'ccc','void',$implementation->parameters);
                $operations[$name] = new Source_Operation_Export($capability,$implementation,$import);
            }
        }
        return new Source_Type_Export($task,$operations);
    }
    /** Same accepted layout/lineage; value equality for portable keys and complete capabilities. */
    public static function current(Source_Type_Export $previous, Source_Export_Task $task): bool {
        $old = $previous->task;
        if (($old->layout !== $task->layout) || ($old->project->project_key !== $task->project->project_key)
            || ($old->project->source_root !== $task->project->source_root) || ($old->project->output_root !== $task->project->output_root)
            || ($old->identity->key() !== $task->identity->key()) || ($old->identity->is_source() !== $task->identity->is_source())
            || (q_count($old->identities) !== q_count($task->identities)) || (q_count($old->capabilities) !== q_count($task->capabilities))) { return false; }
        foreach ($task->identities as $id => $identity) {
            if (!isset($old->identities[$id])) { return false; }
            $other = $old->identities[$id];
            if (($other->key() !== $identity->key()) || ($other->is_source() !== $identity->is_source())) { return false; }
        }
        foreach ($task->capabilities as $name => $capability) {
            if (!isset($old->capabilities[$name])) { return false; }
            $other = $old->capabilities[$name];
            if (($other->role !== $capability->role) || ($other->state !== $capability->state) || ($other->reason !== $capability->reason)) { return false; }
            $operation = $capability->operation; $old_operation = $other->operation;
            if ($operation === null) { if ($old_operation !== null) { return false; } }
            else {
                if ($old_operation === null) { return false; }
                if (!\type_model\Lifecycle_Contracts::operation($operation,$old_operation)) { return false; }
            }
        }
        return true;
    }
    public static function select(array $current /** hash<Source_Export_Task,int> */, array $previous /** hash<Source_Type_Export,int> */, bool $full): array /** hash<Source_Export_Task,int> */ {
        $selected /** hash<Source_Export_Task,int> */ = [];
        foreach ($current as $id => $task) {
            if ($full) { $selected[$id] = $task; continue; }
            $needed = false;
            if (!isset($previous[$id])) { $needed = true; }
            elseif (!Source_Export_Work::current($previous[$id],$task)) { $needed = true; }
            if ($needed) { $selected[$id] = $task; }
        }
        return $selected;
    }
}
