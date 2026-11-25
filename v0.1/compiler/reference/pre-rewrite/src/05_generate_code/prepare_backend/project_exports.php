<?php
declare(strict_types=1);

/*
 * Role: Close native source imports over current canonical source operation plans.
 * Call map: LLVM_Backend / Backend_Context -> Project_Exports::prepare()
 *   prepare() -> required() -> validate_layouts()
 *   prepare() -> Export_Verification::prepare(); operations() merges lifecycle roots
 * Output: exact export entries and common lifecycle emission roots; no duplicate source implementation.
 */
namespace prepare_backend;

final class Project_Exports
{
    /** Prepare fixed link obligations once per backend phase, shared by ABI and emission consumers. */
    public static function prepare(?\type_model\Type_Store $types, ?\load_runtime\Runtime_Input_Set $runtime,
        ?\check_bodies\Body_Set $bodies = null, ?\analyze_lifetimes\Lifetime_Set $lifetimes = null,
        array $previous = [], bool $full = false): source_linkage
    {
        $required = $types === null ? [] : self::required($runtime, $types);

        // Every advertised role belongs to one consistent adapter, even when current
        // native method coverage imports only some roles from that declaration.
        $declared = [];
        foreach ($runtime?->packages() ?? [] as $package)
        {
            foreach ($package->project?->exports ?? [] as $export) {
                foreach ($export->operations as $entry) {
                    if ($entry->capability->operation !== null) {
                        $declared[$entry->capability->operation->link_name] = $entry->capability->operation;
                    }
                }
            }
        }

        $verified = $types === null ? [] : Export_Verification::prepare($declared, $types, $bodies, $lifetimes, $previous, $full);
        return new source_linkage($types, $runtime, $required, self::operations($types, $required), $verified);
    }

    /** Merge normalized package obligations; check source dependencies against one current layout graph. */
    private static function required(?\load_runtime\Runtime_Input_Set $runtime, \type_model\Type_Store $types): array
    {
        $required = [];
        $layouts = [];
        $roots = [];
        foreach ($runtime?->packages() ?? [] as $package)
        {
            $project = $package->project;
            if ($project === null) {
                continue;
            }
            foreach ($project->exports as $export) {
                $layout = $export->task->layout;
                $layouts[] = $layout;
                $roots[$layout->dependency->type_id] = $layout->dependency->type_id;
            }
            foreach ($package->source_imports as $symbol => $operation) {
                if (isset($required[$symbol]) && ($required[$symbol] != $operation)) {
                    throw new \LogicException('Conflicting source import implementations');
                }
                $required[$symbol] = $operation;
            }
        }

        self::validate_layouts($types, $layouts, array_values($roots));
        ksort($required);
        return $required;
    }

    /** Shared exports need one check; distinct snapshots still prove their own lineage and dependencies.
     * @param list<storage_layout> $layouts @param list<int> $roots */
    private static function validate_layouts(\type_model\Type_Store $types, array $layouts, array $roots): void
    {
        if ($roots === []) {
            return;
        }
        $current = Layout_Preparation::capture($types, $roots);
        $validated = [];
        foreach ($layouts as $layout)
        {
            $id = $layout->dependency->type_id;
            if (($validated[$id] ?? null) === $layout) {
                continue;
            }
            if (!Layout_Preparation::current($layout, $current, $id, $layout->configuration)) {
                throw new \LogicException('Project export has stale canonical source dependencies or lineage');
            }
            $validated[$id] = $layout;
        }
    }

    /** Existing and exported operations share one plan graph; native demand only adds emission roots. */
    private static function operations(?\type_model\Type_Store $types, array $required): array
    {
        $operations = [];
        foreach ($types?->lifecycle_operations() ?? [] as $operation) {
            $operations[$operation->link_name] = $operation;
        }
        $pending = $types === null ? [] : array_map(static fn($export) => $export->capability->operation, $required);
        while ($pending !== [])
        {
            $operation = array_pop($pending);
            if (isset($operations[$operation->link_name])) {
                if ($operations[$operation->link_name] !== $operation) {
                    throw new \LogicException('Conflicting complete source lifecycle plans');
                }
                continue;
            }
            $operations[$operation->link_name] = $operation;
            foreach ($operation->members as $member) {
                if ($member->operation instanceof \type_model\source_lifecycle_operation) {
                    $pending[] = $member->operation;
                }
            }
        }
        return array_values($operations);
    }
}
