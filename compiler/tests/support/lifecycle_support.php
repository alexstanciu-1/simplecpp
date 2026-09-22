<?php
declare(strict_types=1);

use Body_Test_Stages as Check;

/** Exercise the common implicit lifecycle preparation protocol for any package. */
final class Lifecycle_Test
{
    /** Inspect real phase selection without exposing a second production scheduling API. */
    public static function lifecycle_tasks(?\load_runtime\Runtime_Input_Set $runtime, \prepare_backend\backend_configuration $configuration,
        ?\prepare_backend\Backend_Context $previous, bool $full): array
    {
        return (new \ReflectionMethod(\prepare_backend\LLVM_Backend::class, 'select_lifecycle'))
            ->invoke(null, $runtime, [], $configuration, $previous, $full);
    }

    /** Prove fixed lifecycle inputs, arbitrary completion order, join rejection and selection/reuse boundaries. */
    public static function preparation(\compile\Compile_Result $compiled): void
    {
        $backend = $compiled->backend;
        $runtime = $backend->runtime;
        $configuration = $backend->configuration;
        $before = serialize($compiled);

        // Full selection uses the same pure workers, even when every previous target is current.
        $tasks = self::lifecycle_tasks($runtime, $configuration, $backend, true);
        Check::check((count($tasks) === count($runtime->lifecycle_operations())) && (count($tasks) >= 2), 'Select all lifecycle work on a full rebuild');
        $inputs = serialize($tasks);
        $results = array_map(\prepare_backend\Callable_Preparer::prepare_lifecycle(...), array_reverse($tasks));
        $targets = (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, $tasks))->join($results);
        $expected = array_map(static fn($operation) => $backend->abi_for($operation->link_name), $runtime->lifecycle_operations());
        Check::check((json_encode($targets) === json_encode($expected)) && (serialize($tasks) === $inputs)
            && (serialize($compiled) === $before), 'Reversed lifecycle workers join in package order without changing inputs');
        foreach ($targets as $index => $target) {
            Check::check($target !== $expected[$index], 'Selected workers produce private targets');
        }
        // Imported lifecycle replacement retains independently prepared source operations and layouts.
        $source_targets = array_map(static fn($operation) => $backend->abi_for($operation->link_name),
            $compiled->types->types->lifecycle_operations());
        Check::check((new \prepare_backend\Backend_Join($compiled->types, $configuration, $backend, [], $runtime,
            [...$targets, ...$source_targets], $backend->layouts, $backend->storage_targets))->join([]) === $backend,
            'Accepted equivalent lifecycle targets preserve backend canonicalization');

        // Warm selection does no lifecycle computation; the join reuses the exact previous targets.
        Check::check(self::lifecycle_tasks($runtime, $configuration, $backend, false) === [], 'Unchanged lifecycle contracts select no work');
        Check::check((new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, []))->join([]) === $expected,
            'Unselected current lifecycle targets retain identity');
        $partial = (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, [$tasks[0]]))
            ->join([\prepare_backend\Callable_Preparer::prepare_lifecycle($tasks[0])]);
        Check::check(($partial[0] !== $expected[0]) && (array_slice($partial, 1) === array_slice($expected, 1)),
            'A selected replacement preserves every unselected lifecycle target');
        Check::check((new \prepare_backend\Lifecycle_Join(null, $configuration, $backend, []))->join([]) === [],
            'Removed lifecycle contributions are omitted independently of selection');

        // Reject missing, repeated and unselected work before creating any backend context.
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, $tasks))->join([]), 'Incomplete');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, $tasks))
            ->join([...$results, $results[0]]), 'duplicate');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, [$tasks[0], $tasks[0]]))->join([]), 'Duplicate');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, []))->join($results), 'Unexpected');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, null, []))->join([]), 'Missing');

        // Results from another task batch or with another physical ABI are not interchangeable.
        $foreign = \prepare_backend\Callable_Preparer::prepare_lifecycle(new \prepare_backend\lifecycle_preparation_task($tasks[0]->operation, $configuration));
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, [$tasks[0]]))->join([$foreign]), 'stale');
        $wrong = new \prepare_backend\abi_target($tasks[0]->operation->link_name, 'ccc', 'i64', [new \prepare_backend\abi_parameter('ptr')],
            \type_model\abi_extension::none, $tasks[0]->operation);
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $configuration, $backend, [$tasks[0]]))
            ->join([new \prepare_backend\lifecycle_preparation_result($tasks[0], $wrong)]), 'stale');

        // Changed backend facts require new work and cannot accept results prepared for the old phase.
        $settings = get_object_vars($configuration);
        $settings['abi_key'] .= ':changed-policy';
        $changed = new \prepare_backend\backend_configuration(...$settings);
        $changed_tasks = self::lifecycle_tasks($runtime, $changed, $backend, false);
        Check::check(count($changed_tasks) === count($tasks), 'Changed backend policy selects lifecycle work');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $changed, $backend, $tasks))->join($results), 'stale');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $changed, $backend, $changed_tasks))->join($results), 'stale');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($runtime, $changed, $backend, []))->join([]), 'stale');
        $changed_targets = (new \prepare_backend\Lifecycle_Join($runtime, $changed, $backend, $changed_tasks))
            ->join(array_map(\prepare_backend\Callable_Preparer::prepare_lifecycle(...), $changed_tasks));
        Check::check(count($changed_targets) === count($expected), 'Changed configuration accepts its own worker results');

        // A fresh adapter lineage keeps exact names but replaces the shared operation contracts.
        $lease = \load_runtime\Runtime_Import::open(array_map(static fn($package) => $package->directory,
            array_values($runtime->packages())), clone $runtime->base_catalog);
        $fresh = $lease->inputs;
        $lease->release();
        $fresh_tasks = self::lifecycle_tasks($fresh, $configuration, $backend, false);
        Check::check(count($fresh_tasks) === count($tasks), 'Changed provider contracts select lifecycle work despite equal link names');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($fresh, $configuration, $backend, $tasks))->join($results), 'stale');
        Check::rejects(static fn() => (new \prepare_backend\Lifecycle_Join($fresh, $configuration, $backend, []))->join([]), 'stale');
        Check::check(serialize($compiled) === $before, 'Rejected lifecycle batches preserve the retained compiler snapshot');
    }

}
