<?php
declare(strict_types=1);

/*
 * Role: Named semantic definitions, operation capabilities and lifetime contracts.
 * Used by: catalog import; source definition materialization; semantic consumers
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace type_model;

/** @compiler-api Semantic permission for a conversion; destinations alone do not determine behavior. */
enum conversion_purpose: string {
    case implicit_boundary = 'implicit';
    case explicit_cast = 'explicit_cast';
    case condition = 'condition';
    case text = 'text';
}

/** @compiler-api Copy capability: value copying or an explicit imported/composed copy constructor. */
enum copy_kind: string {
    case value = 'value';
    case construct = 'construct';
    // The provider has not exposed a supported copy operation; not a C++ noncopyable claim.
    case unavailable = 'unavailable';
}

/** Construction from an expiring owned source; copy is a semantic fallback, unavailable is not. */
enum expiring_construction: string {
    case unavailable = 'unavailable';
    case value = 'value';
    case copy = 'copy';
    case construct = 'construct';
}

/** @compiler-api Assignment keeps a destination live; it is independent of copy construction. */
enum assignment_kind: string {
    case unavailable = 'unavailable';
    case value = 'value';
    case call = 'call';
}

/** @compiler-api Supported language cleanup policies; destruction requires its validated implementation. */
enum cleanup_kind: string {
    case none = 'none';
    case destroy = 'destroy';
}

// Shared language facts, not a particular value's ownership/scope or cleanup plan.
// Complete source operations compose fields; runtime values retain their imported operations.
/** @compiler-api Readable construction/copy/assignment/cleanup policy shared by every value of a type; not a per-value lifetime plan. */
final class lifetime_contract
{
    /**
     * @compiler-api Construct a definition/descriptor in its authoritative owner; consumers share it unchanged.
     * Local validation is not proof that the complete capability is supported.
     */
    public function __construct(
        public readonly copy_kind $copy,
        public readonly cleanup_kind $cleanup,
        public readonly runtime_lifecycle_operation|source_lifecycle_operation|null $destructor = null,
        public readonly runtime_lifecycle_operation|source_lifecycle_operation|null $copy_constructor = null,
        public readonly construction_kind $construction = construction_kind::unavailable,
        public readonly runtime_lifecycle_operation|source_lifecycle_operation|null $default_constructor = null,
        public readonly assignment_kind $assignment = assignment_kind::unavailable,
        public readonly runtime_lifecycle_operation|source_lifecycle_operation|null $copy_assignment = null,
        public readonly expiring_construction $expiring = expiring_construction::unavailable,
        public readonly runtime_lifecycle_operation|source_lifecycle_operation|null $move_constructor = null,
    )
    {
        if ((($expiring === expiring_construction::construct) !== ($move_constructor !== null))
            || (($move_constructor !== null) && ($move_constructor->kind !== lifecycle_operation_kind::move_construct))
            || (($expiring === expiring_construction::copy) && ($copy === copy_kind::unavailable))) {
            throw new \InvalidArgumentException('Expiring construction requires its selected implementation');
        }
        if ((($assignment === assignment_kind::call) !== ($copy_assignment !== null))
            || (($copy_assignment !== null) && ($copy_assignment->kind !== lifecycle_operation_kind::copy_assign))) {
            throw new \InvalidArgumentException('Copy assignment requires its implementation contract');
        }
        if ((($construction === construction_kind::construct) !== ($default_constructor !== null))
            || (($default_constructor !== null) && ($default_constructor->kind !== lifecycle_operation_kind::default_construct))) {
            throw new \InvalidArgumentException('Default construction requires its implementation contract');
        }
        if ((($cleanup === cleanup_kind::destroy) !== ($destructor !== null))
            || (($destructor !== null) && ($destructor->kind !== lifecycle_operation_kind::destroy))) {
            throw new \InvalidArgumentException('Destruction requires its implementation contract');
        }
        if ((($copy === copy_kind::construct) !== ($copy_constructor !== null))
            || (($copy_constructor !== null) && ($copy_constructor->kind !== lifecycle_operation_kind::copy_construct))) {
            throw new \InvalidArgumentException('Copy construction requires its implementation contract');
        }
    }

    /** Read the implementation for an operation role; availability remains an independent capability. */
    public function operation(lifecycle_operation_kind $kind): runtime_lifecycle_operation|source_lifecycle_operation|null
    {
        return match ($kind) {
            lifecycle_operation_kind::default_construct => $this->default_constructor,
            lifecycle_operation_kind::copy_construct => $this->copy_constructor,
            lifecycle_operation_kind::move_construct => $this->move_constructor,
            lifecycle_operation_kind::copy_assign => $this->copy_assignment,
            lifecycle_operation_kind::destroy => $this->destructor,
        };
    }
}

/** @compiler-api Same-type integer ordering uses the definition's declared signedness. */
enum integer_comparison: string {
    case ordered = 'ordered';
}

/** @compiler-api Same-type integer addition policy, explicitly authorized by a definition. */
enum integer_addition: string {
    case wrapping = 'wrapping';
}

// Language meaning stays in the shared provider record. Signedness does not
// distinguish LLVM integer representations and must not be guessed from names.
/**
 * @compiler-api Readable name, namespace_name, representation, lifetime, signed, integer_family and addition facts from the catalog.
 * Integer signedness is language meaning, independent of LLVM storage shape. Null
 * lifetime means no value; consumers read the provider definition rather than guess
 * from names/widths. Construction validates shape contracts, not backend support.
 */
final class named_type_definition
{
    /**
     * @compiler-api Construct a definition/descriptor in its authoritative owner; consumers share it unchanged.
     * Local validation is not proof that the complete capability is supported.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $namespace_name,
        public readonly representation_record $representation,

        // Explicit null means no value (void), never an unspecified/default policy.
        public readonly ?lifetime_contract $lifetime,
        public readonly ?bool $signed = null,

        // Explicit semantic family membership authorizes same-signed integer widening.
        // Null means no cross-type integer conversion, regardless of representation.
        public readonly ?string $integer_family = null,
        public readonly ?integer_addition $addition = null,
        public readonly bool $struct_field = false,
        public readonly ?native_record_layout $native_layout = null,
        public readonly ?resource_kind $resource = null,
        public readonly ?element_storage $element_storage = null,
        /** @var list<list<int>> Static owning leaf paths, relative to this record. */
        public readonly array $resource_paths = [],
        public readonly ?integer_comparison $comparison = null,
    )
    {
        if (($element_storage !== null) && (($resource !== resource_kind::allocation)
            || ($representation !== $element_storage->family->descriptor->representation)
            || ($lifetime !== $element_storage->family->descriptor->lifetime))) {
            throw new \InvalidArgumentException('Typed storage requires its family descriptor and allocation obligation');
        }
        if (($resource !== null) && (($representation->kind !== representation_kind::opaque_inline)
            || ($lifetime?->copy !== copy_kind::unavailable))) {
            throw new \InvalidArgumentException('Allocation owners require noncopyable inline storage');
        }
        if (($resource_paths !== []) && (($representation->kind !== representation_kind::structure)
            || !in_array($lifetime?->copy, [copy_kind::unavailable, copy_kind::construct], true))) {
            throw new \InvalidArgumentException('Owning fields require a structural definition with explicit copying or no copy capability');
        }
        if ((($resource !== null) || ($resource_paths !== [])) && ($lifetime?->assignment === assignment_kind::value)) {
            throw new \InvalidArgumentException('Owning resources require explicit assignment or no assignment capability');
        }
        $seen_paths = [];
        foreach ($resource_paths as $path)
        {
            if (!is_array($path) || !array_is_list($path) || ($path === [])) {
                throw new \InvalidArgumentException('Resource field paths must name static subobjects');
            }
            foreach ($path as $ordinal) {
                if (!is_int($ordinal) || ($ordinal < 0)) {
                    throw new \InvalidArgumentException('Resource field paths require nonnegative field ordinals');
                }
            }
            $key = implode('.', $path);
            if (isset($seen_paths[$key])) {
                throw new \InvalidArgumentException('Duplicate resource field path');
            }
            $seen_paths[$key] = true;
        }
        if (($name === '') || (($representation->kind === representation_kind::integer) !== ($signed !== null))) {
            throw new \InvalidArgumentException('Named integer definitions require signedness; other kinds must omit it');
        }
        if (($struct_field) && !in_array($representation->kind, [representation_kind::integer, representation_kind::fixed_array, representation_kind::structure, representation_kind::opaque_inline], true)) {
            throw new \InvalidArgumentException('Struct fields require an eligible inline storage contract');
        }
        if (($native_layout !== null) && ($representation->kind !== representation_kind::structure)) {
            throw new \InvalidArgumentException('Native field layout requires a structural definition');
        }
        if (($comparison !== null) && ($representation->kind !== representation_kind::integer)) {
            throw new \InvalidArgumentException('Integer comparison requires integer representation');
        }
        if (($addition !== null) && ($representation->kind !== representation_kind::integer)) {
            throw new \InvalidArgumentException('Integer addition requires integer representation');
        }
        if (($integer_family !== null) && (($integer_family === '') || ($representation->kind !== representation_kind::integer))) {
            throw new \InvalidArgumentException('Integer conversion family requires a nonempty identity and integer representation');
        }
        if (($representation->kind === representation_kind::void_type) !== ($lifetime === null)) {
            throw new \InvalidArgumentException('Value types require a lifetime contract; void must have none');
        }
    }
}

/** @compiler-api Provider reference category only; does not establish an implemented runtime capability. */
enum implementation_kind: int {
    case callable = 0;
    case native_operation = 1;
}

// Provider-owned reference, not executable behavior or a backend dispatch name.
// Catalog loading must eventually validate the referenced implementation. Merely
// constructing this descriptor does not advertise an implemented capability.
/** @compiler-api Provider operation identity; primitive implementations and imported callable bindings remain distinct. */
final class implementation_binding
{
    /**
     * @compiler-api Construct a definition/descriptor in its authoritative owner; consumers share it unchanged.
     * Local validation is not proof that the complete capability is supported.
     */
    public function __construct(
        public readonly implementation_kind $kind,
        public readonly string $provider,
        public readonly string $entry,
    )
    {
        if (($provider === '') || ($entry === '')) {
            throw new \InvalidArgumentException('Implementation binding needs a provider and entry');
        }
    }
}

// Exact operand matching is the initial contract. No implicit conversions,
// generic patterns, lifetime defaults or arithmetic semantics are inferred here.
/**
 * @compiler-api Readable selected exact-operand contract: operation, operand_types, result_type,
 * implementation. Type IDs require the same Type_Store lineage. Describing a row
 * alone does not implement, validate or advertise an operation capability.
 */
final class operation_contract
{
    /**
     * @compiler-api Construct a definition/descriptor in its authoritative owner; consumers share it unchanged.
     * Local validation is not proof that the complete capability is supported.
     * @param list<int> $operand_types Canonical IDs in the owning type snapshot.
     */
    public function __construct(
        public readonly string $operation,
        public readonly array $operand_types,
        public readonly int $result_type,
        public readonly implementation_binding $implementation,
    )
    {
        if (($operation === '') || ($result_type <= 0) || (!array_is_list($operand_types))) {
            throw new \InvalidArgumentException('Invalid operation contract');
        }
        foreach ($operand_types as $id) {
            if ((!is_int($id)) || ($id <= 0)) {
                throw new \InvalidArgumentException('Invalid operand type ID');
            }
        }
    }
}
