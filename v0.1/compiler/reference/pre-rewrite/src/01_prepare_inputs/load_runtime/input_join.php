<?php
declare(strict_types=1);

/*
 * Role: Accept selected package reads and compose one coherent runtime input set.
 * Call map: Input_Join::join() -> packages(); compose(); Callable_Bindings::validate()
 *           compose() -> named(); type_facts(); link_identity()
 * Output: fixed catalog and provider-scoped indexes; no input mutation or tool work
 */
namespace load_runtime;

final class Input_Join implements \compile\Join
{
    /** @param list<runtime_package_task> $tasks Fixed package reads against one base catalog. */
    public function __construct(private readonly \type_model\Type_Catalog $catalog,
        private readonly ?Runtime_Input_Set $previous, private readonly array $tasks)
    {
    }

    /** Accept a complete batch independently of completion order; callers own result-lease release.
     * @param list<runtime_package_result> $results */
    public function join(array $results): Runtime_Input_Set
    {
        $packages = $this->packages($results);
        if (($this->previous?->base_catalog === $this->catalog) && ($this->previous->packages() === $packages)) {
            return $this->previous;
        }
        return $this->compose($packages);
    }

    /** Verify selected request provenance and common target before catalog composition. */
    private function packages(array $results): array
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            if (isset($selected[$task->directory]) || ($task->catalog !== $this->catalog)) {
                throw new \LogicException('Duplicate or stale runtime input task');
            }
            $selected[$task->directory] = $task;
        }
        if ($selected === []) {
            throw new \LogicException('Runtime input set requires selected packages');
        }
        $accepted = [];
        $packages = [];
        $first = null;
        foreach ($results as $result)
        {
            $task = $result->task;
            $package = $result->lease->package;
            if (($task !== ($selected[$task->directory] ?? null)) || isset($accepted[$task->directory])
                || ($package->directory !== $task->directory) || ($package->base_catalog !== $this->catalog)
                || !$result->lease->active()) {
                throw new \LogicException('Unexpected, duplicate or stale runtime input result');
            }
            if (isset($packages[$package->provider])) {
                throw new \RuntimeException('Conflicting runtime provider namespace: ' . $package->provider);
            }
            if (($first !== null) && (($package->target_triple !== $first->target_triple)
                || ($package->data_layout !== $first->data_layout) || ($package->link_driver !== $first->link_driver)
                || ($package->link_arguments !== $first->link_arguments))) {
                throw new \RuntimeException('Runtime input target or link context mismatch');
            }
            $first ??= $package;
            $accepted[$task->directory] = true;
            $packages[$package->provider] = $package;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete runtime input results');
        }
        // Referenced types require their exact accepted owner in the final link input closure.
        foreach ($packages as $package)
        {
            foreach ($package->bindings?->imports ?? [] as $import) {
                $owner = $packages[$import->provider] ?? null;
                if (($owner === null) || ($owner->type_for($import->type_id)->language_type !== $import->type->language_type)
                    || ($owner->type_for($import->type_id)->storage != $import->type->storage)) {
                    throw new \RuntimeException('Missing or incompatible runtime type import owner');
                }
            }
        }
        ksort($packages);
        return $packages;
    }

    /** Merge exact exposed definitions, rejecting conflicts rather than selecting a provider by order. */
    private function compose(array $packages): Runtime_Input_Set
    {
        $definitions = $this->catalog->definitions();
        $records = $this->catalog->records();
        $type_names = [];
        foreach ([...$definitions, ...$records] as $definition) {
            self::named($type_names, $definition->name, $definition->namespace_name, 'type');
        }
        $callables = [];
        $operations = [];
        $lifecycle = [];
        $families = [];
        $call_names = [];
        $links = [];
        $scalar_facts = [];
        $fingerprints = [$this->catalog->content_key];
        foreach ($packages as $package)
        {
            $imports = array_map(static fn($import) => $import->type->language_type, $package->bindings?->imports ?? []);
            // Each adapter read the same base catalog. Existing scalar mappings remain shared objects.
            foreach ($package->catalog->definitions() as $definition)
            {
                if (($definition === $this->catalog->find_type($definition->name, $definition->namespace_name))
                    || in_array($definition, $imports, true)) {
                    continue;
                }
                self::named($type_names, $definition->name, $definition->namespace_name, 'type');
                $definitions[] = $definition;
            }
            foreach ($package->catalog->records() as $record) {
                if ($record === $this->catalog->find_record($record->name, $record->namespace_name)) {
                    continue;
                }
                self::named($type_names, $record->name, $record->namespace_name, 'type');
                $records[] = $record;
            }
            self::type_facts($package, $scalar_facts);
            foreach ($package->callables() as $callable)
            {
                if ($callable->provider !== $package->provider) {
                    throw new \LogicException('Runtime callable belongs to a different provider');
                }
                self::named($call_names, $callable->name, $callable->namespace_name, 'callable');
                self::link_identity($links, $callable->abi->link_name);
                $operations[json_encode([$callable->provider, $callable->id], JSON_THROW_ON_ERROR)] = $callable;
                $callables[] = $callable;
            }
            foreach ($package->lifecycle_operations() as $operation)
            {
                if ($operation->provider !== $package->provider) {
                    throw new \LogicException('Runtime lifecycle belongs to a different provider');
                }
                self::link_identity($links, $operation->link_name);
                $lifecycle[] = $operation;
            }
            foreach ($package->storage_families as $family)
            {
                self::named($type_names, $family->name, $family->namespace_name, 'type');
                foreach ($family->operations as $name) {
                    self::named($call_names, $name, $family->namespace_name, 'callable');
                }
                $primitive_links = [];
                foreach ($family->primitives as $primitive) {
                    $primitive_links[$primitive->link_name] = true;
                }
                foreach ($primitive_links as $link => $_) {
                    self::link_identity($links, $link);
                }
                $families[] = $family;
            }
            $fingerprints[] = [$package->provider, $package->catalog->content_key];
        }
        Callable_Bindings::validate($callables);

        // Fingerprints are cache invalidation only. Membership and lookup use exact identities above.
        $catalog = (count($definitions) === count($this->catalog->definitions())) && (count($records) === count($this->catalog->records()))
            && ($families === []) ? $this->catalog : new \type_model\Type_Catalog($this->catalog->provider,
                hash('sha256', json_encode($fingerprints, JSON_THROW_ON_ERROR)), $this->catalog->representation_scope,
                $definitions, $this->catalog->integer_literal_type, $this->catalog->entry_return_type,
                $records, $this->catalog->boolean_type);
        $first = reset($packages);
        return new Runtime_Input_Set($packages, $this->catalog, $catalog, $first->target_triple, $first->data_layout,
            $first->link_driver, $first->link_arguments, $callables, $operations, $lifecycle, $families);
    }

    /** Reject ambiguous exposure by exact namespace/name, independent of package order. */
    private static function named(array &$seen, string $name, string $namespace, string $kind): void
    {
        $key = json_encode([$namespace, $name], JSON_THROW_ON_ERROR);
        if (isset($seen[$key])) {
            throw new \RuntimeException('Conflicting runtime ' . $kind . ' exposure: ' . $name);
        }
        $seen[$key] = true;
    }

    /** Matching source mappings must also agree on measured scalar storage within the common target. */
    private static function type_facts(Runtime_Package $package, array &$facts): void
    {
        foreach ($package->types() as $type)
        {
            $definition = $type->language_type;
            if (($definition === null) || !in_array($type->storage->kind, [runtime_storage_kind::integer, runtime_storage_kind::void_type], true)) {
                continue;
            }
            $key = json_encode([$definition->namespace_name, $definition->name], JSON_THROW_ON_ERROR);
            $old = $facts[$key] ?? null;
            if (($old !== null) && (($old->language_type !== $definition) || ($old->storage != $type->storage)
                || ($old->integer_bits !== $type->integer_bits) || ($old->signed !== $type->signed))) {
                throw new \RuntimeException('Conflicting runtime scalar mapping: ' . $definition->name);
            }
            $facts[$key] = $type;
        }
    }

    private static function link_identity(array &$seen, string $link): void
    {
        if (isset($seen[$link])) {
            throw new \RuntimeException('Conflicting runtime link identity: ' . $link);
        }
        $seen[$link] = true;
    }
}
