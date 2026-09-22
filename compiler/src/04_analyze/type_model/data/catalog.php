<?php
declare(strict_types=1);

/*
 * Role: Named definition catalog and lookup index.
 * Used by: Language_Types; type/body analysis
 * Flow: validated definitions -> Type_Catalog -> shared lookup
 */

namespace type_model;


// Fixed provider snapshot. Every phase lookup shares these immutable definitions.
/**
 * @compiler-api Fixed language definitions consumed by resolve_types and compile.
 * Readable fields: provider, content_key, representation_scope, integer_literal_type,
 * entry_return_type, optional boolean_type. find_type hides name indexing; defaults reference the same
 * shared definitions. No consumer mutation or backend target-layout inference.
 */
class Type_Catalog implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<string, named_type_definition> */
    private array $by_name = [];
    /** @var array<string, record_declaration> Normalized structural inputs awaiting canonical IDs. */
    private array $records_by_name = [];

    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<named_type_definition> $definitions
     * @param list<record_declaration> $records Normalized structural inputs without canonical IDs.
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $content_key,
        public readonly string $representation_scope,
        array $definitions,
        public readonly named_type_definition $integer_literal_type,
        public readonly named_type_definition $entry_return_type,
        array $records = [],
        public readonly ?named_type_definition $boolean_type = null,
    )
    {
        foreach ($definitions as $definition) {
            $key = self::key($definition->name, $definition->namespace_name);
            if (isset($this->by_name[$key])) {
                throw new \InvalidArgumentException('Duplicate type definition: ' . $definition->name);
            }
            $this->by_name[$key] = $definition;
        }
        foreach ($records as $record)
        {
            $key = self::key($record->name, $record->namespace_name);
            if (isset($this->by_name[$key]) || isset($this->records_by_name[$key])) {
                throw new \InvalidArgumentException('Duplicate structural definition: ' . $record->name);
            }
            $this->records_by_name[$key] = $record;
        }
        if (($boolean_type !== null) && (($this->find_type($boolean_type->name, $boolean_type->namespace_name) !== $boolean_type)
            || ($boolean_type->representation->kind !== representation_kind::integer)
            || ($boolean_type->representation->payload->bit_width !== 1) || ($boolean_type->signed !== false))) {
            throw new \InvalidArgumentException('Boolean binding requires an unsigned one-bit catalog integer');
        }
        if (($this->find_type($integer_literal_type->name, $integer_literal_type->namespace_name) !== $integer_literal_type)
            || ($integer_literal_type->representation->kind !== representation_kind::integer)) {
            throw new \InvalidArgumentException('Literal type binding must reference a catalog integer definition');
        }
        if (($this->find_type($entry_return_type->name, $entry_return_type->namespace_name) !== $entry_return_type)
            || ($entry_return_type->representation->kind !== representation_kind::integer)) {
            throw new \InvalidArgumentException('Entry return type must reference a catalog integer definition');
        }
    }

    /** @compiler-api Exact qualified lookup returns the shared definition or null; does not materialize type IDs. */
    public function find_type(string $name, string $namespace_name): ?named_type_definition
    {
        return $this->by_name[self::key($name, $namespace_name)] ?? null;
    }

    /** @compiler-api Fixed normalized structural inputs; no foreign canonical member ranges. */
    public function records(): array
    {
        return array_values($this->records_by_name);
    }

    /** @compiler-api Exact normalized record lookup before canonical materialization. */
    public function find_record(string $name, string $namespace_name): ?record_declaration
    {
        return $this->records_by_name[self::key($name, $namespace_name)] ?? null;
    }

    /** @compiler-api Shared definition rows for provider composition; consumers must not mutate them. */
    public function definitions(): array
    {
        return array_values($this->by_name);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $literals = ['integer' => ['name' => $this->integer_literal_type->name,
            'namespace' => $this->integer_literal_type->namespace_name]];
        if ($this->boolean_type !== null) {
            $literals['boolean'] = ['name' => $this->boolean_type->name, 'namespace' => $this->boolean_type->namespace_name];
        }
        return json_encode(['provider' => $this->provider, 'content_key' => $this->content_key,
            'representation_scope' => $this->representation_scope,
            'definitions' => array_values($this->by_name), 'records' => $this->records(),
            'entry_return_type' => ['name' => $this->entry_return_type->name, 'namespace' => $this->entry_return_type->namespace_name],
            'literal_types' => $literals], JSON_THROW_ON_ERROR);
    }

    private static function key(string $name, string $namespace_name): string
    {
        return strlen($namespace_name) . ':' . $namespace_name . $name;
    }
}
