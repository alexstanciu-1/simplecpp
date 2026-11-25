<?php
declare(strict_types=1);

/*
 * Role: Retained runtime package dataset and queries; temporary reader reservation.
 * Used by: Package_Adapter; Compiler_Session; backend and native building
 * Flow: validated import -> Runtime_Package -> storage/callable/module queries and source link obligations
 */

namespace load_runtime;

use type_model\Type_Catalog;
use type_model\runtime_callable;

/** @compiler-api Fixed normalized package. Only the adapter reads its serialized format. */
final class Runtime_Package
{
    /** @param array<string, runtime_type> $types @param list<runtime_callable> $callables
     * @param array<string, string> $modules @param list<string> $protected_paths
     * @param array<string, \prepare_backend\source_operation_export> $source_imports Validated link obligations. */
    public function __construct(public readonly string $provider, public readonly string $directory,
        public readonly string $target_triple, public readonly string $data_layout,
        public readonly string $link_driver, public readonly array $link_arguments,
        private readonly array $types, private readonly array $callables,
        private readonly array $modules, private readonly array $protected_paths,
        private readonly string $manifest_source, public readonly Type_Catalog $base_catalog,
        public readonly Type_Catalog $catalog, public readonly array $storage_families = [], public readonly ?package_bindings $bindings = null,
        public readonly ?project_binding $project = null, public readonly array $source_imports = [])
    {
    }

    /** Validated package-local rows; composition reads them without copying definitions. */
    public function types(): array
    {
        return $this->types;
    }

    /** @compiler-api Query a provider-local type; no name-based implementation dispatch. */
    public function type_for(string $id): runtime_type
    {
        return $this->types[$id] ?? throw new \OutOfBoundsException('Unknown runtime type: ' . $id);
    }

    /** @compiler-api Storage facts are shared and valid only with this package's target. */
    public function storage_for(string $id): runtime_storage
    {
        return $this->type_for($id)->storage;
    }

    /** @compiler-api Available source callable declarations; no body work is associated with these. */
    public function callables(): array
    {
        return $this->callables;
    }

    /** @compiler-api Imported implicit lifecycle operations, in stable type/role order; no source declarations. */
    public function lifecycle_operations(): array
    {
        $operations = [];
        foreach ($this->types as $type)
        {
            if (isset($this->bindings?->imports[$type->id]) || isset($this->bindings?->sources[$type->id])) {
                continue;
            }
            $lifetime = $type->language_type?->lifetime;
            foreach ([$lifetime?->destructor, $lifetime?->copy_constructor, $lifetime?->move_constructor, $lifetime?->copy_assignment, $lifetime?->default_constructor] as $operation) {
                if ($operation !== null) {
                    $operations[] = $operation;
                }
            }
        }
        return $operations;
    }

    /** @compiler-api Select a prepared implementation without knowing package filenames. */
    public function module_for(runtime_module_kind $kind): string
    {
        return $this->modules[$kind->value] ?? throw new \RuntimeException('Runtime module variant is unavailable: ' . $kind->value);
    }

    /** @compiler-api Files a native output must never replace. */
    public function protected_paths(): array
    {
        return $this->protected_paths;
    }

    /** @compiler-internal Adapter canonicalization after verifying all current artifacts. */
    public function matches(string $directory, string $manifest_source, Type_Catalog $catalog, ?package_bindings $bindings = null, ?project_binding $project = null): bool
    {
        return ($this->directory === $directory) && ($this->manifest_source === $manifest_source) && ($this->base_catalog === $catalog) && ($this->bindings == $bindings) && ($this->project == $project);
    }

    /** @compiler-api Debug contracts, without exporting the provider's serialized shape as a consumer API. */
    public function to_array(): array
    {
        return ['provider' => $this->provider, 'directory' => $this->directory, 'target_triple' => $this->target_triple,
            'types' => array_values($this->types), 'callables' => $this->callables, 'modules' => $this->modules];
    }
}

/** @compiler-api Hold while compiling/linking with the package; only the coordinator releases it. */
final class Runtime_Lease
{
    public function __construct(private mixed $lock, public readonly Runtime_Package $package)
    {
    }

    public function active(): bool
    {
        return is_resource($this->lock);
    }

    public function release(): void
    {
        if ($this->lock !== null) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
            $this->lock = null;
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}
