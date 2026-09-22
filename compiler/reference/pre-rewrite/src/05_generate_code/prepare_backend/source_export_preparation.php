<?php
declare(strict_types=1);

/*
 * Role: Select fixed source export contracts and prepare private lifecycle import ABIs.
 * Call map: export coordinator -> Source_Export_Preparation::capture(); select()
 *   [each selected task] prepare() -> Callable_Preparer::prepare_lifecycle()
 *   Source_Export_Join -> current(); validate()
 * Output: explicit six-role contracts; no native adapters, emission or filesystem publication.
 */
namespace prepare_backend;

final class Source_Export_Preparation
{
    /** Capture selected current records only; identity projection stays in resolution, layout in its existing owner.
     * @param list<int> $roots Explicit demands, never an automatic export of every source type.
     * @return array<int, source_export_task> Fixed current membership, keyed within the supplied lineage. */
    public static function capture(\compile\native_project $project, \resolve_types\Source_Identities $identities,
        \type_model\Type_Store $types, array $roots, array $layouts, backend_configuration $configuration): array
    {
        $input = Layout_Preparation::capture($types, $roots);
        $keys = [];
        foreach ($input->dependencies as $id => $dependency)
        {
            if (!$identities->accepts($project, $types, $id)) {
                throw new \LogicException('Stale source export identity lineage or definition');
            }
            $keys[$id] = $identities->for_type($id);
        }
        $current = [];
        foreach ($input->roots as $id)
        {
            $layout = $layouts[$id] ?? null;
            if (!Layout_Preparation::current($layout, $input, $id, $configuration)) {
                throw new \LogicException('Source export requires current accepted layout/lineage/target');
            }
            if (!$keys[$id]->source || ($layout->definition->representation->kind !== \type_model\representation_kind::structure)) {
                throw new \RuntimeException('Source export requires a source-defined record identity');
            }
            $dependencies = Layout_Preparation::subset($input, [$id])->dependencies;
            $capabilities = [];
            foreach (source_export_role::cases() as $role)
            {
                $kind = $role->implemented_kind();
                $operation = $kind === null ? null : \resolve_types\Lifecycle_Composition::complete_operation($types, $id, $kind);
                $capabilities[$role->value] = new source_export_capability($role,
                    $operation === null ? source_export_availability::unsupported : source_export_availability::available,
                    $operation === null ? ($kind === null ? 'Native source movement is deferred' : 'Complete operation is not supported by the accepted type contract') : '',
                    $operation);
            }
            $current[$id] = new source_export_task($project, $keys[$id], $layout,
                array_intersect_key($keys, $dependencies), $capabilities);
        }
        return $current;
    }

    /** The same selection rule handles a full build and an incremental attempt; removed demands are excluded. */
    public static function select(array $current, array $previous, bool $full_rebuild): array
    {
        $selected = [];
        foreach ($current as $id => $task) {
            if ($full_rebuild || !self::current($previous[$id] ?? null, $task)) {
                $selected[$id] = $task;
            }
        }
        return $selected;
    }

    /** Compare retained provenance, not just symbols; compatible rebuilds prepare new associations with stable keys. */
    public static function current(?source_type_export $previous, source_export_task $task): bool
    {
        return ($previous !== null) && ($previous->task->project == $task->project)
            && ($previous->task->identity == $task->identity) && ($previous->task->layout === $task->layout)
            && ($previous->task->identities == $task->identities) && ($previous->task->capabilities == $task->capabilities);
    }

    /** Pure worker: a stable external entry refers to the existing complete plan, without copying field logic. */
    public static function prepare(source_export_task $task): source_type_export
    {
        $operations = [];
        foreach ($task->capabilities as $key => $capability)
        {
            $implementation = null;
            $import = null;
            if ($capability->operation !== null)
            {
                $implementation = Callable_Preparer::prepare_lifecycle(new lifecycle_preparation_task(
                    $capability->operation, $task->layout->configuration))->target;
                $import = new abi_target(self::symbol($task->identity, $capability->role), 'ccc', 'void', $implementation->parameters);
            }
            $operations[$key] = new source_operation_export($capability, $implementation, $import);
        }
        return new source_type_export($task, $operations);
    }

    /** Full operation identity uses the source profile, tagged type key, role and exact initial signature. */
    public static function symbol(\resolve_types\export_type_identity $identity, source_export_role $role): string
    {
        $kind = $role->implemented_kind() ?? throw new \LogicException('Unsupported export role has no link symbol');
        $signature = $kind->has_source() ? 'ccc:void(ptr,ptr)' : 'ccc:void(ptr)';
        $parts = [source_type_export::PROFILE, $identity->key, $role->value, $signature];
        $encoded = array_map(static fn($part) => preg_replace_callback('/[^A-Za-z0-9]/',
            static fn($match) => $match[0] === '_' ? '__' : sprintf('_x%02X_', ord($match[0])), $part), $parts);
        return 'scpp_source_' . implode('_X_', $encoded);
    }

    /** Reject changed role coverage, semantic association or pointer ABI before adopting private output. */
    public static function validate(source_type_export $result): void
    {
        $task = $result->task;
        $roles = array_map(static fn($role) => $role->value, source_export_role::cases());
        if ((array_keys($task->capabilities) !== $roles) || (array_keys($result->operations) !== $roles)) {
            throw new \LogicException('Incomplete source export capability table');
        }
        foreach ($task->capabilities as $key => $capability)
        {
            $row = $result->operations[$key];
            if (($row->capability !== $capability) || ($capability->role->value !== $key)) {
                throw new \LogicException('Stale source export capability association');
            }
            if ($capability->state !== source_export_availability::available)
            {
                if (($row->implementation !== null) || ($row->import !== null)) {
                    throw new \LogicException('Unavailable source capability acquired an ABI');
                }
                continue;
            }
            $implementation = $row->implementation;
            $import = $row->import;
            if (($implementation === null) || ($import === null)
                || ($implementation->lifecycle_operation !== $capability->operation)
                || !Callable_Contract::lifecycle_matches($implementation, $task->layout->configuration)
                || ($import->link_name !== self::symbol($task->identity, $capability->role))
                || ($import->calling_convention !== 'ccc') || ($import->return_type !== 'void')
                || ($import->return_extension !== \type_model\abi_extension::none)
                || ($import->lifecycle_operation !== null) || ($import->parameters != $implementation->parameters)) {
                throw new \LogicException('Stale source export ABI association');
            }
        }
    }
}
