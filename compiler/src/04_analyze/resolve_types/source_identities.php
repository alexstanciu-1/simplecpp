<?php
declare(strict_types=1);

/*
 * Role: Project portable type identities without retaining syntax in native contracts.
 * Call map: export coordinator -> Source_Identities::for_type()
 *   -> declaration(); argument() -> for_type() [reachable identity dependencies]
 * Output: shared exact keys; numeric IDs are lookup keys within this fixed lineage only.
 */
namespace resolve_types;

final class Source_Identities
{
    private array $known = [];
    private array $active = [];
    private ?array $provider_keys = null;

    /** The coordinator fixes these accepted inputs while projecting a selected batch. */
    public function __construct(private readonly \compile\native_project $project,
        private readonly \type_model\Type_Store $types, private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \instantiate\Instance_View $instances, private readonly \type_model\Type_Catalog $language,
        private readonly ?\load_runtime\Runtime_Input_Set $runtime = null)
    {
    }

    /** Resolve only demanded keys, memoizing shared arguments; reject absent or ambiguous provenance. */
    public function for_type(int $id): export_type_identity
    {
        if (isset($this->known[$id])) {
            return $this->known[$id];
        }
        if (isset($this->active[$id])) {
            throw new \RuntimeException('Recursive portable type identity is unsupported');
        }
        $this->active[$id] = true;
        try {
            return $this->known[$id] = $this->project($id);
        }
        finally {
            unset($this->active[$id]);
        }
    }

    /** Nominal origins precede structural shapes: equal layouts never establish type identity. */
    private function project(int $id): export_type_identity
    {
        $type = $this->types->definition_for_type($id);
        $instance = $this->instances->type_context($type);
        if ($instance !== null)
        {
            $arguments = array_map($this->argument(...), $instance->arguments);
            $external = $instance->definition->external;
            if ($external instanceof \type_model\family_declaration) {
                return self::key(['family', $external->provider, $external->id, $arguments]);
            }
            if ($external !== null) {
                throw new \RuntimeException('Provider instance has no portable export identity contract');
            }
            return self::key(['source', $this->project->project_key, $this->declaration($instance->definition), $arguments], true);
        }

        $symbol = $this->symbols->find_symbol($type->name, $type->namespace_name, \collect_symbols\symbol_kind::struct_symbol);
        if ($symbol !== 0) {
            return self::key(['source', $this->project->project_key,
                $this->declaration($this->symbols->symbol_by_id($symbol)), []], true);
        }
        if ($this->language->find_type($type->name, $type->namespace_name) === $type) {
            return self::key(['language', $this->language->provider, $type->namespace_name, $type->name]);
        }
        $provider = $this->provider_keys()[$id] ?? null;
        if ($provider !== null) {
            return $provider;
        }
        if ($type->representation->kind === \type_model\representation_kind::fixed_array) {
            $shape = $type->representation->payload;
            return self::key(['array', $this->for_type($shape->element_type)->parts, (string)$shape->count]);
        }
        throw new \RuntimeException('Type has no portable export identity: ' . $type->name);
    }

    /** Preserve argument order and kind, including the declared type of each normalized integer literal. */
    private function argument(\instantiate\template_argument $argument): array
    {
        $id = $this->types->find_type($argument->type->name, $argument->type->namespace_name);
        if (($id === 0) || ($this->types->definition_for_type($id) !== $argument->type)) {
            throw new \LogicException('Stale export identity argument');
        }
        $key = $this->for_type($id)->parts;
        if ($argument->value === null) {
            return ['type', $key];
        }
        if (!preg_match('/^(0|-?[1-9][0-9]*)$/D', $argument->value)) {
            throw new \LogicException('Export constant must be a normalized integer literal');
        }
        return ['constant', $key, $argument->value];
    }

    /** Paths are normalized project-relative module identities; local/anonymous definitions are excluded. */
    private function declaration(\collect_symbols\symbol_record $symbol): array
    {
        $path = $symbol->frontend?->tokens->source->path;
        $prefix = rtrim($this->project->source_root, '/') . '/';
        if (($symbol->owner_symbol_id !== 0) || ($symbol->name === '') || ($path === null)
            || !str_starts_with($path, $prefix)) {
            throw new \RuntimeException('Source export requires a named project-relative declaration');
        }
        $relative = substr($path, strlen($prefix));
        foreach (explode('/', $relative) as $part) {
            if (($part === '') || ($part === '.') || ($part === '..')) {
                throw new \RuntimeException('Source export module path is not normalized');
            }
        }
        return [$relative, $symbol->namespace_name, $symbol->name];
    }

    /** Index imported definitions once per fixed batch; aliases import their original owner's key. */
    private function provider_keys(): array
    {
        if ($this->provider_keys !== null) {
            return $this->provider_keys;
        }
        $keys = [];
        foreach ($this->runtime?->packages() ?? [] as $package)
        {
            foreach ($package->types() as $row)
            {
                $definition = $row->language_type;
                if (($definition === null) || isset($package->bindings?->imports[$row->id])) {
                    continue;
                }
                $id = $this->types->find_type($definition->name, $definition->namespace_name);
                if (($id === 0) || ($this->types->definition_for_type($id) !== $definition)) {
                    continue;
                }
                $key = self::key(['provider', $package->provider, $row->id]);
                if (isset($keys[$id]) && ($keys[$id]->key !== $key->key)) {
                    throw new \LogicException('Ambiguous provider type export identity');
                }
                $keys[$id] = $key;
            }
        }
        return $this->provider_keys = $keys;
    }

    /** Guard the projection service against accidental reuse with another canonical lineage or definition. */
    public function accepts(\compile\native_project $project, \type_model\Type_Store $types, int $id): bool
    {
        return ($project == $this->project) && ($types->lineage === $this->types->lineage)
            && ($types->definition_for_type($id) === $this->types->definition_for_type($id));
    }

    private static function key(array $parts, bool $source = false): export_type_identity
    {
        return new export_type_identity($parts, $source);
    }
}
