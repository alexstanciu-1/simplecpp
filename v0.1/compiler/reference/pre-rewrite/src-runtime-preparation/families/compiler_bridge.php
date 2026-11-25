<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation as native;
use load_runtime as imported;

/** Explicit runtime preparation context; this is not part of the compiler's semantic type model. */
final class compiler_provider {
    public function __construct(public readonly family_catalog $catalog, public readonly string $output_root,
        public readonly string $scope, public readonly array $configuration)
    {
    }
}

/** One accepted package owns a canonical opaque type, regardless of how it was prepared. */
final class compiler_type_binding {
    public function __construct(public readonly imported\Runtime_Package $package, public readonly imported\runtime_type $type)
    {
    }
}

/** Optional compiler coordinator bridge; load after both compiler and preparation bootstraps. */
final class Compiler_Bridge implements imported\Family_Preparer
{
    /** @param list<compiler_provider> $providers Fixed native catalogs/configurations. */
    public function __construct(private readonly array $providers)
    {
    }

    /** Select every request before native execution; accept a complete private batch before publication/import. */
    public function prepare(array $tasks, \type_model\Type_Catalog $catalog, array $previous, array $packages = []): array
    {
        $selected = [];
        $results = [];
        try
        {
            // Read accepted dependency recipes before reserving any outputs in this frontier.
            $requests = [];
            $imports = [];
            $native_arguments = [];
            $source_exports = [];
            $projects = [];
            $prepared = $this->type_bindings($packages, $previous);
            foreach ($tasks as $index => $task)
            {
                $source = $this->provider($task->context->definition->external);
                $source_exports[$index] = $this->exports($task, $prepared);
                $projects[$index] = $this->project($source_exports[$index]);
                [$arguments, $imports[$index], $hashes] = $this->arguments($task, $source, $catalog, $prepared, $native_arguments);
                $requests[] = new specialization_request($source->catalog, $task->context->definition->external->id,
                    $arguments, $task->operations, $projects[$index]?->project_key ?? $source->scope, $source->configuration, $hashes);
            }

            // Selection reserves separate outputs before any native worker executes.
            foreach ($requests as $index => $request)
            {
                $source = $this->provider($tasks[$index]->context->definition->external);
                $preparation = new Preparation(new Store($projects[$index]?->output_root ?? $source->output_root));
                $selected[] = $preparation->select($request);
            }
            foreach ($selected as $task) {
                $results[] = Preparation::execute($task);
            }
            $accepted = (new Join($selected))->join($results);

            // Compiler bindings are private metadata associations, not edits to shared native packages.
            $output = [];
            foreach ($selected as $index => $native_task)
            {
                $accepted[$native_task->key]->candidate->publish();
                $project = $this->project_binding($native_task, $source_exports[$index]);
                // Capture the receipt under the exclusive reservation; import revalidates it under a shared reservation.
                $accepted[$native_task->key]->candidate->release();
                $task = $tasks[$index];
                $context = $task->context;
                $source = $this->provider($context->definition->external);
                $id = Requests::instance_id($source->catalog);
                $old = $previous[$context->instance_id]->package ?? null;
                $operations = $old?->bindings?->callables ?? [];
                foreach ($task->operations as $operation) {
                    $operations[$id . '.' . $operation] = imported\Family_Operations::binding($context, $operation);
                }
                $references = [];
                $source_references = [];
                foreach ($imports[$index] as $position => $import) {
                    $references[$native_task->arguments[$position]->definition['id']] = $import;
                }
                foreach ($task->sources as $position => $export) {
                    $source_references[$native_task->arguments[$position]->definition['id']] = $export;
                }
                $binding = new imported\package_bindings([$id => new \type_model\named_type_reference(
                    $context->type_name(), $context->type_namespace())], $operations, $references, $source_references);
                $lease = imported\Package_Adapter::open($native_task->reservation->output, $catalog, $old, $binding, $project);
                try
                {
                    $calls = [];
                    foreach ($lease->package->callables() as $callable) {
                        $operation = substr($callable->id, strlen($id) + 1);
                        $calls[$operation] = $callable;
                    }
                    $output[] = new imported\family_preparation_result($task, $lease->package, $id, $calls);
                }
                finally {
                    $lease->release();
                }
            }
            return $output;
        }
        finally
        {
            foreach ($results as $result) {
                $result->candidate->release();
            }
            foreach ($selected as $task) {
                $task->reservation->release();
            }
        }
    }

    /** Union direct and transitive source obligations by exact source identity before selecting native work. */
    private function exports(imported\family_preparation_task $task, array $prepared): array
    {
        $exports = [];
        $pending = $task->sources;
        foreach ($task->context->arguments as $argument) {
            $key = json_encode([$argument->type->namespace_name, $argument->type->name], JSON_THROW_ON_ERROR);
            array_push($pending, ...array_values($prepared[$key]->package->project?->exports ?? []));
        }
        foreach ($pending as $export) {
            $key = $export->task->identity->key;
            if (isset($exports[$key]) && ($exports[$key] != $export)) {
                throw new \RuntimeException('Conflicting transitive source exports');
            }
            $exports[$key] = $export;
        }
        ksort($exports);
        return $exports;
    }

    /** Project scope follows source dependencies; distinct output storage cannot be inferred from a shared provider. */
    private function project(array $exports): ?\compile\native_project
    {
        $project = null;
        foreach ($exports as $export) {
            if (($project !== null) && ($project != $export->task->project)) {
                throw new \RuntimeException('Family arguments cross native project scopes');
            }
            $project = $export->task->project;
        }
        return $project;
    }

    /** Bind the already accepted portable receipt to exactly the compiler exports used for this request. */
    private function project_binding(preparation_task $native_task, array $exports): ?imported\project_binding
    {
        if ($native_task->project === null) {
            return null;
        }
        ksort($exports);
        if (array_keys($exports) !== array_keys($native_task->project->sources)) {
            throw new \RuntimeException('Project family result requires its complete compiler export closure');
        }
        // Exact portable provenance is checked at the adapter; compiler consumers retain typed exports only.
        foreach ($exports as $key => $export) {
            $source = \runtime_preparation\project\Source_Adapter::argument($export)->sources[$key];
            if ($source != $native_task->project->sources[$key]) {
                throw new \RuntimeException('Project result changed source contract provenance');
            }
        }
        $receipt = native\Files::read($native_task->reservation->output . '/package/project.json');
        $binding = new imported\project_binding($receipt, $exports);
        $source = reset($exports);
        imported\Project_Import::validate($binding, $receipt, ['triple' => $source->task->layout->configuration->target_triple,
            'data_layout' => $source->task->layout->configuration->data_layout]);
        return $binding;
    }

    /** Require the exact semantic definition snapshot accepted by the source adapter. */
    private function provider(\type_model\family_declaration $family): compiler_provider
    {
        $match = null;
        foreach ($this->providers as $provider)
        {
            if (($provider->catalog->provider === $family->provider)
                && (($provider->catalog->families[$family->id]->semantic ?? null) === $family->definition)) {
                if ($match !== null) {
                    throw new \RuntimeException('Ambiguous native family preparation provider');
                }
                $match = $provider;
            }
        }
        return $match ?? throw new \RuntimeException('Missing current native family preparation provider');
    }

    /** Index only owned opaque definitions; imported rows never establish another native owner. */
    private function type_bindings(array $packages, array $previous): array
    {
        foreach ($previous as $result) {
            $packages[$result->package->provider] = $result->package;
        }
        $bindings = [];
        foreach ($packages as $package)
        {
            foreach ($package->types() as $id => $type)
            {
                $definition = $type->language_type;
                if (($definition === null) || ($type->storage->kind !== imported\runtime_storage_kind::opaque_inline)
                    || isset($package->bindings?->imports[$id])) {
                    continue;
                }
                $key = json_encode([$definition->namespace_name, $definition->name], JSON_THROW_ON_ERROR);
                if (isset($bindings[$key])) {
                    throw new \RuntimeException('Ambiguous accepted native type owner');
                }
                $bindings[$key] = new compiler_type_binding($package, $type);
            }
        }
        return $bindings;
    }

    /** Match canonical arguments and share validated native reads only within this fixed coordinator batch.
     * The local memo holds no leases; each selected task retains and revalidates its own header snapshots. */
    private function arguments(imported\family_preparation_task $task, compiler_provider $source,
        \type_model\Type_Catalog $catalog, array $prepared, array &$native_arguments): array
    {
        $by_definition = [];
        foreach ($source->catalog->types as $id => $type)
        {
            $name = $type->definition['language_type'] ?? null;
            if ($name === null) {
                continue;
            }
            $definition = $catalog->find_type($name['name'], $name['namespace']);
            if ($definition !== null) {
                $by_definition[json_encode([$definition->namespace_name, $definition->name], JSON_THROW_ON_ERROR)][] = $id;
            }
        }
        $ids = [];
        $imports = [];
        $hashes = [];
        foreach ($task->context->arguments as $index => $argument)
        {
            $type = $argument->type;
            $key = json_encode([$type->namespace_name, $type->name], JSON_THROW_ON_ERROR);
            $dependency = $prepared[$key] ?? null;
            if (($argument->value === null) && ($dependency !== null)
                && ($dependency->type->language_type === $type))
            {
                $native_arguments[$key] ??= $this->native_argument($dependency, $catalog);
                [$ids[], $dependency_hashes] = $native_arguments[$key];
                foreach ($dependency_hashes as $path => $hash) {
                    if (isset($hashes[$path]) && ($hashes[$path] !== $hash)) {
                        throw new \RuntimeException('Conflicting native argument header snapshots');
                    }
                    $hashes[$path] = $hash;
                }
                $package = $dependency->package;
                $imports[$index] = new imported\runtime_type_import($package->provider, $dependency->type->id,
                    $dependency->type, $package->target_triple, $package->data_layout);
                continue;
            }
            if (isset($task->sources[$index]))
            {
                $export = $task->sources[$index];
                if ($export->task->layout->definition !== $type) {
                    throw new \RuntimeException('Source argument export differs from current canonical type');
                }
                $ids[] = \runtime_preparation\project\Source_Adapter::argument($export);
                continue;
            }
            $matches = $by_definition[$key] ?? [];
            if (($argument->value !== null) || (count($matches) !== 1)
                || ($catalog->find_type($type->name, $type->namespace_name) !== $type)) {
                throw new \RuntimeException('Family argument requires one exact runtime language mapping; source-dependent arguments require accepted exports');
            }
            $ids[] = $matches[0];
        }
        ksort($hashes);
        return [$ids, $imports, $hashes];
    }

    /** Revalidate the accepted package while reading its native recipe; no recursive preparation or mutable cache. */
    private function native_argument(compiler_type_binding $result, \type_model\Type_Catalog $catalog): array
    {
        $package = $result->package;
        $lease = imported\Package_Adapter::open($package->directory, $catalog, $package, $package->bindings, $package->project);
        try
        {
            if ($lease->package !== $package) {
                throw new \RuntimeException('Native argument package changed before selection');
            }
            $sources = [];
            foreach ($package->project?->exports ?? [] as $export) {
                $sources += \runtime_preparation\project\Source_Adapter::argument($export)->sources;
            }
            ksort($sources);
            $type = $package->project === null
                ? native\Native_Types::from_package($package->directory, $package->provider, $result->type->id)
                : native\Native_Types::from_project_package($package->directory, $package->provider, $result->type->id, $sources);

            // Discover type-only dependencies, so added method coverage does not invalidate outer recipes.
            // Comparing resolved paths also detects a newly shadowing include file.
            $source = '';
            foreach ($type->headers as $header) {
                $source .= '#include <' . $header . ">\n";
            }
            $source .= implode('', $type->declarations);
            $toolchain = new native\Clang_Toolchain($type->context, $package->directory);
            $hashes = native\Files::hashes($toolchain->dependencies($source));
            $manifest = native\Files::json($package->directory . '/package/manifest.json');
            foreach ($hashes as $path => $hash) {
                if (($manifest['inputs']['headers'][$path] ?? null) !== $hash) {
                    throw new \RuntimeException('Accepted native argument headers changed before selection');
                }
            }
            return [$type, $hashes];
        }
        finally {
            $lease->release();
        }
    }
}
