<?php
declare(strict_types=1);

/*
 * Role: Fixed package import requests, composed runtime inputs and temporary leases.
 * Used by: Runtime_Import; Input_Join; collection, backend and native building
 * Flow: selected package snapshots -> private results -> one immutable input set
 */
namespace load_runtime;

final class runtime_package_task {
    public function __construct(public readonly string $directory, public readonly \type_model\Type_Catalog $catalog,
        public readonly ?Runtime_Package $previous)
    {
    }
}

final class runtime_package_result {
    public function __construct(public readonly runtime_package_task $task, public readonly Runtime_Lease $lease)
    {
    }
}

/** Accepted packages share one target and one composed semantic catalog; identities stay provider-scoped. */
final class Runtime_Input_Set
{
    /** Fixed indexes are supplied by Input_Join; this owner only answers read-only queries.
     * @param array<string, Runtime_Package> $packages Indexed by exact provider namespace.
     * @param list<\type_model\runtime_callable> $callables
     * @param array<string, \type_model\runtime_callable> $operations Exact provider/operation keys.
     * @param list<\type_model\runtime_lifecycle_operation> $lifecycle
     * @param list<\type_model\storage_family> $storage_families */
    public function __construct(private readonly array $packages, public readonly \type_model\Type_Catalog $base_catalog,
        public readonly \type_model\Type_Catalog $catalog, public readonly string $target_triple,
        public readonly string $data_layout, public readonly string $link_driver, public readonly array $link_arguments,
        private readonly array $callables, private readonly array $operations, private readonly array $lifecycle,
        public readonly array $storage_families)
    {
    }

    public function packages(): array
    {
        return $this->packages;
    }

    public function package_for(string $provider): Runtime_Package
    {
        return $this->packages[$provider] ?? throw new \OutOfBoundsException('Unknown runtime provider: ' . $provider);
    }

    public function callable_for(string $provider, string $id): ?\type_model\runtime_callable
    {
        return $this->operations[json_encode([$provider, $id], JSON_THROW_ON_ERROR)] ?? null;
    }

    public function callables(): array
    {
        return $this->callables;
    }

    public function lifecycle_operations(): array
    {
        return $this->lifecycle;
    }

    /** Select one verified module per package; source call count does not multiply link inputs. */
    public function modules_for(runtime_module_kind $kind): array
    {
        return array_map(static fn($package) => $package->module_for($kind), array_values($this->packages));
    }

    /** Every accepted input remains protected from native output replacement. */
    public function protected_paths(): array
    {
        $paths = [];
        foreach ($this->packages as $package) {
            foreach ($package->protected_paths() as $path) {
                $paths[$path] = $path;
            }
        }
        return array_values($paths);
    }

    /** Export normalized package membership without inventing a single combined provider identity. */
    public function to_array(): array
    {
        return ['packages' => array_map(static fn($package) => $package->to_array(), array_values($this->packages))];
    }
}

/** Coordinator-owned reader reservations; never retained in a compiler snapshot. */
final class Runtime_Input_Lease
{
    /** @param list<Runtime_Lease> $leases */
    public function __construct(public readonly Runtime_Input_Set $inputs, private array $leases)
    {
    }

    public function release(): void
    {
        foreach ($this->leases as $lease) {
            $lease->release();
        }
        $this->leases = [];
    }

    public function __destruct()
    {
        $this->release();
    }
}
