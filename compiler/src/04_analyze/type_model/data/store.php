<?php
declare(strict_types=1);

/*
 * Role: Canonical type identities and interned representations.
 * Used by: Type_Resolver; type joins; semantic consumers
 * Flow: declarations -> bound representations -> indexed lookup
 */

namespace type_model;


// Only the coordinator constructs a candidate. Clone the owner for an update;
// PHP arrays copy on write and immutable rows/payloads remain shared. Workers
// consume a fixed candidate through lookup methods, never intern into it.
/**
 * @compiler-api Canonical type/representation owner with a fixed context read by semantic consumers.
 * Type IDs and representation IDs are separate one-based domains; member ranges are
 * zero-based. IDs survive only within a retained lineage; full reconstruction can
 * reuse the same numbers for unrelated records. Read through lookup methods.
 * Only resolution joins and their materializers populate a private coordinator-owned candidate. Once
 * Type_Resolution is handed downstream, the owner and shared rows are read-only.
 * Representation availability does not establish operations, layout, lifetime or ABI.
 */
class Type_Store implements \compile\Step_Store
{
    public readonly type_lineage $lineage;
    /** @var list<type_record> One-based canonical IDs within this store lineage. */
    private array $types = [];

    /** @var list<representation_record> One-based representation IDs. */
    private array $representations = [];

    /** @var list<type_member> */
    private array $members = [];

    /** @var array<string, int> */
    private array $by_name = [];

    /** @var array<string, int> */
    private array $by_representation = [];

    /**
     * @compiler-api Coordinator creates an empty cache in an explicit context; use resolve_types\Type_Cache::prepare
     * for resident updates so invalidation/full selection follows the existing protocol.
     */
    public function __construct(public readonly type_context $context)
    {
        $this->lineage = new type_lineage();
    }

    /** @compiler-api Read a canonical ID by qualified name; zero means absent. No interning. */
    public function find_type(string $name, string $namespace_name = ''): int
    {
        return $this->by_name[self::name_key($name, $namespace_name)] ?? 0;
    }

    // Coordinator-side reference lookup after scope/name interpretation. Repeated
    // encounters share even a pending identity. This does not register a declaration.
    /** @compiler-internal Candidate-only: find or create a pending canonical identity; does not declare a type. */
    public function reference_type(string $name, string $namespace_name = ''): int
    {
        $id = $this->find_type($name, $namespace_name);
        if ($id !== 0) {
            return $id;
        }
        if ($name === '') {
            throw new \InvalidArgumentException('Empty type name');
        }
        $id = count($this->types) + 1;
        $this->types[] = new type_record($name, $namespace_name);
        $this->by_name[self::name_key($name, $namespace_name)] = $id;
        return $id;
    }

    /** @compiler-api Read declaration status for a type ID; throws when unknown. */
    public function is_declared(int $type_id): bool
    {
        return $this->type_by_id($type_id)->declared;
    }

    /** @compiler-api Read a shared canonical row by type ID; throws OutOfBoundsException when absent. */
    public function type_by_id(int $id): type_record
    {
        return $this->types[$id - 1] ?? throw new \OutOfBoundsException('Unknown type ID: ' . $id);
    }

    // This checks representation work only, never operations, layout or ABI.
    /** @compiler-api Read whether representation is absent; does not test semantic or backend readiness. */
    public function needs_representation(int $type_id): bool
    {
        return $this->type_by_id($type_id)->representation_id === 0;
    }

    // Invalidate meaning and representation together while retaining identity.
    // Rebuilding dependent semantic/layout results belongs to later
    // phase selection; this operation alone does not authorize their reuse.
    /** @compiler-internal Candidate-only: replace the row to clear meaning/representation; no downstream invalidation here. */
    public function invalidate_definition(int $type_id): void
    {
        $type = $this->type_by_id($type_id);
        if (($type->representation_id === 0) && ($type->definition === null)) {
            return;
        }
        $this->types[$type_id - 1] = new type_record($type->name, $type->namespace_name, declared: $type->declared);
    }

    // Complete an earlier reference or establish a new declared identity. A
    // second declaration is an error even before a representation exists.
    /** @compiler-internal Candidate-only: establish one declaration; reject duplicate declarations even if pending. */
    public function declare_type(string $name, string $namespace_name = ''): int
    {
        $id = $this->reference_type($name, $namespace_name);
        $type = $this->type_by_id($id);
        if ($type->declared) {
            throw new \InvalidArgumentException('Duplicate type declaration');
        }
        $this->types[$id - 1] = new type_record($name, $namespace_name, declared: true);
        return $id;
    }

    // Completing or replacing a representation changes a candidate row, never a row
    // retained by another snapshot. Downstream invalidation is not implemented yet.
    /** @compiler-internal Candidate-only: attach a shape ID after declaration and check compatibility with a bound definition. */
    public function set_representation(int $type_id, int $representation_id): void
    {
        $type = $this->type_by_id($type_id);
        if (!$type->declared) {
            throw new \LogicException('Cannot attach representation to an undeclared type');
        }
        $representation = $this->representation_by_id($representation_id);
        if (($type->definition !== null) && ($type->definition->representation != $representation)) {
            throw new \LogicException('Representation conflicts with the bound type definition');
        }
        if ($type->representation_id === $representation_id) {
            return;
        }
        $this->types[$type_id - 1] = new type_record($type->name, $type->namespace_name, $representation_id, true, $type->definition);
    }

    /** @compiler-api Read a shared shape by representation ID; throws OutOfBoundsException when absent. */
    public function representation_by_id(int $id): representation_record
    {
        return $this->representations[$id - 1] ?? throw new \OutOfBoundsException('Unknown representation ID: ' . $id);
    }

    // Bind the shared language definition once. The coordinator supplies it from
    // the current provider; consumers then use the canonical ID without name lookup.
    /** @compiler-internal Candidate-only: bind the shared authoritative definition; replacement requires prior invalidation. */
    public function bind_definition(int $type_id, named_type_definition $definition): void
    {
        $type = $this->type_by_id($type_id);
        if ((!$type->declared) || ($type->name !== $definition->name) || ($type->namespace_name !== $definition->namespace_name)
            || (($type->representation_id !== 0) && ($this->representation_for_type($type_id) != $definition->representation))) {
            throw new \LogicException('Type definition does not match its declared identity and representation');
        }
        if ($type->definition === $definition) {
            return;
        }
        if ($type->definition !== null) {
            throw new \LogicException('Invalidate the previous type definition before replacing it');
        }
        $this->types[$type_id - 1] = new type_record($type->name, $type->namespace_name, $type->representation_id, true, $definition);
    }

    /** @compiler-api Read a type's shared shape; throws when the type/representation is unresolved. */
    public function representation_for_type(int $type_id): representation_record
    {
        $type = $this->type_by_id($type_id);
        if ($type->representation_id === 0) {
            throw new \LogicException('Type representation is unresolved: ' . $type->name);
        }
        return $this->representation_by_id($type->representation_id);
    }

    /** @compiler-api Read the shared language definition; throws for unknown/unresolved type IDs. */
    public function definition_for_type(int $type_id): named_type_definition
    {
        return $this->type_by_id($type_id)->definition
            ?? throw new \LogicException('Type definition is unresolved: ' . $type_id);
    }

    /** @compiler-api Read a shared member by zero-based index in a range from this store; throws when absent. */
    public function member_at(int $index): type_member
    {
        return $this->members[$index] ?? throw new \OutOfBoundsException('Unknown type member index: ' . $index);
    }

    // Factories describe real representation kinds; none chooses a language type
    // name, default integer width, pointer size, ownership rule or LLVM spelling.
    /** @compiler-internal Candidate-only: intern the void shape, return a representation ID. */
    public function intern_void(): int
    {
        if (isset($this->by_representation['void'])) {
            return $this->by_representation['void'];
        }
        return $this->intern('void', new representation_record(representation_kind::void_type, null));
    }

    /** @compiler-internal Candidate-only: intern an integer value width, return a representation ID; no signedness inference. */
    public function intern_integer(int $bit_width): int
    {
        $key = 'integer:' . $bit_width;
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        return $this->intern($key,
            new representation_record(representation_kind::integer, new integer_representation($bit_width)));
    }

    /** @compiler-internal Candidate-only: intern a floating value format, return a representation ID; no ABI/layout inference. */
    public function intern_float(floating_format $format): int
    {
        $key = 'float:' . $format->value;
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        return $this->intern($key,
            new representation_record(representation_kind::floating_point, new floating_representation($format)));
    }

    /** @compiler-internal Candidate-only: intern element type/address space; no target pointer width or capability readiness. */
    public function intern_pointer(int $element_type, int $address_space = 0): int
    {
        $key = 'pointer:' . $element_type . ':' . $address_space;
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        $this->type_by_id($element_type);
        return $this->intern($key,
            new representation_record(representation_kind::pointer, new pointer_representation($element_type, $address_space)));
    }

    /** @compiler-internal Candidate-only: intern element type/count; no instantiated generic behavior or layout. */
    public function intern_array(int $element_type, int $count): int
    {
        $key = 'array:' . $element_type . ':' . $count;
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        $this->type_by_id($element_type);
        return $this->intern($key,
            new representation_record(representation_kind::fixed_array, new array_representation($element_type, $count)));
    }

    /** @compiler-internal Intern the semantic borrowed-byte-span shape; physical expansion belongs to its ABI contract. */
    public function intern_byte_span(): int
    {
        return $this->intern('byte_span', new representation_record(representation_kind::byte_span, null));
    }

    /** @compiler-internal Intern measured opaque storage in the current target context. */
    public function intern_opaque(opaque_representation $layout): int
    {
        return $this->intern('opaque:' . $layout->size_bytes . ':' . $layout->alignment_bytes,
            new representation_record(representation_kind::opaque_inline, $layout));
    }

    /**
     * @compiler-internal Candidate-only: intern ordered named fields and member range; reject duplicate names/unknown types.
     * @param list<type_member> $fields
     */
    public function intern_structure(array $fields): int
    {
        self::require_list($fields);
        $names = [];
        $key = 'structure:';
        foreach ($fields as $field)
        {
            if ((!$field instanceof type_member) || ($field->name === '') || (isset($names[$field->name]))) {
                throw new \InvalidArgumentException('Invalid or duplicate structure field');
            }
            $this->type_by_id($field->type_id);
            $names[$field->name] = true;
            $key .= $field->type_id . ':' . strlen($field->name) . ':' . $field->name . ':' . (int)$field->writable . ';';
        }
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        $payload = new structure_representation(count($this->members), count($fields));
        foreach ($fields as $field) {
            $this->members[] = $field;
        }
        return $this->intern($key, new representation_record(representation_kind::structure, $payload));
    }

    /**
     * @compiler-internal Candidate-only: intern return/parameter types and passing modes; target ABI facts stay in backend preparation.
     * @param list<int> $parameter_types
     * @param list<argument_passing> $parameter_passing Omitted means all value parameters.
     */
    public function intern_signature(int $return_type, array $parameter_types, array $parameter_passing = []): int
    {
        $this->type_by_id($return_type);
        self::require_list($parameter_types);
        foreach ($parameter_types as $id) {
            if (!is_int($id)) {
                throw new \InvalidArgumentException('Parameter type must be an ID');
            }
            $this->type_by_id($id);
        }
        $payload = new signature_representation($return_type, count($this->members), count($parameter_types), $parameter_passing,
            Result_Contracts::production($this->representation_for_type($return_type)->kind));
        $key = 'signature:' . $return_type . ':' . implode(',', $parameter_types) . ':'
            . implode(',', array_map(static fn($mode) => $mode->value, $payload->parameter_passing));
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        foreach ($parameter_types as $id) {
            $this->members[] = new type_member($id);
        }
        return $this->intern($key, new representation_record(representation_kind::function_signature, $payload));
    }

    // Debug view only. No compiler phase reads serialized descriptors as facts.
    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        return json_encode(['context' => $this->context, 'types' => $this->types, 'representations' => $this->representations,
                'members' => $this->members], JSON_THROW_ON_ERROR);
    }

    private static function name_key(string $name, string $namespace_name): string
    {
        return strlen($namespace_name) . ':' . $namespace_name . $name;
    }

    private static function require_list(array $items): void
    {
        if (!array_is_list($items)) {
            throw new \InvalidArgumentException('Type components must be an ordered list');
        }
    }

    /** Reuse the representation for an exact key or allocate its next store-local ID without recycling. */
    private function intern(string $key, representation_record $record): int
    {
        if (isset($this->by_representation[$key])) {
            return $this->by_representation[$key];
        }
        $id = count($this->representations) + 1;
        $this->representations[] = $record;
        $this->by_representation[$key] = $id;
        return $id;
    }

    /** Read complete compiler-owned operations in stable type/role order; no expansion or mutation. */
    public function lifecycle_operations(): array
    {
        $operations = [];
        foreach ($this->types as $type)
        {
            $life = $type->definition?->lifetime;
            foreach ([$life?->default_constructor, $life?->copy_constructor, $life?->copy_assignment, $life?->move_constructor, $life?->destructor] as $operation) {
                if ($operation instanceof source_lifecycle_operation) {
                    $operations[] = $operation;
                }
            }
        }
        return $operations;
    }

    /** Concrete typed storage definitions; consumers select their own dependent work. */
    public function element_storages(): array
    {
        $result = [];
        foreach ($this->types as $index => $type) {
            if ($type->definition?->element_storage !== null) {
                $result[$index + 1] = $type->definition;
            }
        }
        return $result;
    }

    /** Shared structure identities and definitions for layout selection; no field/layout copies. */
    public function structures(): array
    {
        $out = [];
        foreach ($this->types as $index => $type) {
            if ($type->definition?->representation->kind === representation_kind::structure) {
                $out[$index + 1] = $type->definition;
            }
        }
        return $out;
    }

    /** Resolve an exact field name to its ordinal in the containing canonical structure. */
    public function field_index(int $type_id, string $name): int
    {
        $shape = $this->representation_for_type($type_id);
        if ($shape->kind !== representation_kind::structure) {
            return -1;
        }
        for ($i = 0; $i < $shape->payload->count; ++$i) {
            if ($this->member_at($shape->payload->first + $i)->name === $name) {
                return $i;
            }
        }
        return -1;
    }

    /** Validate a field projection in its owning type; ordinals are never global identities. */
    public function field_for(int $type_id, int $index): type_member
    {
        $shape = $this->representation_for_type($type_id);
        if (($shape->kind !== representation_kind::structure) || ($index < 0) || ($index >= $shape->payload->count)) {
            throw new \LogicException('Invalid record field projection');
        }
        return $this->member_at($shape->payload->first + $index);
    }
}
