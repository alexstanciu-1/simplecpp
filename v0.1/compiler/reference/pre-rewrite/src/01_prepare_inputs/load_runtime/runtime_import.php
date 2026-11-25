<?php
declare(strict_types=1);

/*
 * Role: Select fixed package reads and own reservations until composition succeeds.
 * Call map: Runtime_Import::open() -> select(); read() [each task]; Input_Join::join()
 *           read() -> Package_Adapter::open(); failures release every acquired lease
 *           reserve() -> Package_Adapter::open() [exact snapshots]; Input_Join::join()
 * Output: Runtime_Input_Lease; no preparation tools or compiler type allocation
 */
namespace load_runtime;

final class Runtime_Import
{
    /** All packages are revalidated, including reused snapshots, while readers exclude replacement.
     * @param list<string> $paths */
    public static function open(array $paths, \type_model\Type_Catalog $catalog, ?Runtime_Input_Set $previous = null): Runtime_Input_Lease
    {
        $tasks = self::select($paths, $catalog, $previous);
        $results = [];
        try {
            foreach ($tasks as $task) {
                $results[] = self::read($task);
            }
            $inputs = (new Input_Join($catalog, $previous, $tasks))->join($results);
            return new Runtime_Input_Lease($inputs, array_map(static fn($result) => $result->lease, $results));
        }
        catch (\Throwable $error) {
            foreach ($results as $result) {
                $result->lease->release();
            }
            throw $error;
        }
    }

    /** Acquire final leases and require the exact accepted snapshots before artifacts can be used.
     * @param list<Runtime_Package> $packages Selected ordinary and demanded packages. */
    public static function reserve(array $packages, ?Runtime_Input_Set $previous = null): Runtime_Input_Lease
    {
        if ($packages === []) {
            throw new \LogicException('Final runtime reservation requires packages');
        }
        $catalog = $packages[0]->base_catalog;
        $tasks = [];
        $results = [];
        try
        {
            foreach ($packages as $package)
            {
                $task = new runtime_package_task($package->directory, $catalog, $package);
                $lease = Package_Adapter::open($package->directory, $catalog, $package, $package->bindings, $package->project);
                $results[] = new runtime_package_result($task, $lease);
                if ($lease->package !== $package) {
                    throw new \RuntimeException('Prepared runtime package changed before final reservation');
                }
                $tasks[] = $task;
            }
            $inputs = (new Input_Join($catalog, $previous, $tasks))->join($results);
            return new Runtime_Input_Lease($inputs, array_map(static fn($result) => $result->lease, $results));
        }
        catch (\Throwable $error) {
            foreach ($results as $result) {
                $result->lease->release();
            }
            throw $error;
        }
    }

    /** Canonical paths deduplicate aliases; all readers see the same base language definitions.
     * @param list<string> $paths @return list<runtime_package_task> */
    public static function select(array $paths, \type_model\Type_Catalog $catalog, ?Runtime_Input_Set $previous = null): array
    {
        if (!array_is_list($paths) || ($paths === [])) {
            throw new \InvalidArgumentException('Runtime inputs require a nonempty package path list');
        }
        $directories = [];
        foreach ($paths as $path) {
            $directory = is_string($path) ? realpath($path) : false;
            if (($directory === false) || !is_dir($directory)) {
                throw new \RuntimeException('Missing prepared runtime directory');
            }
            $directories[$directory] = true;
        }
        ksort($directories);
        $retained = [];
        foreach ($previous?->packages() ?? [] as $package) {
            $retained[$package->directory] = $package;
        }
        $tasks = [];
        foreach ($directories as $directory => $_) {
            $tasks[] = new runtime_package_task($directory, $catalog, $retained[$directory] ?? null);
        }
        return $tasks;
    }

    public static function read(runtime_package_task $task): runtime_package_result
    {
        return new runtime_package_result($task, Package_Adapter::open($task->directory, $task->catalog, $task->previous));
    }
}
