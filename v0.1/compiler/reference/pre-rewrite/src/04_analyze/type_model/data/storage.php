<?php
declare(strict_types=1);

/*
 * Role: Typed storage families and their native primitive contracts.
 * Used by: package import; concrete preparation; checking and backend preparation
 * Flow: provider definition + element argument -> concrete storage type and operations
 */
namespace type_model;

/** Native address/integer ABI only; addresses never become ordinary source values. */
final class storage_primitive {
    /** @param list<runtime_integer_abi|runtime_borrow_abi> $parameters */
    public function __construct(public readonly string $link_name,
        public readonly runtime_integer_abi|runtime_borrow_abi|null $result,
        public readonly array $parameters)
    {
    }
}

/** Metadata-owned family, independent of any source element representation. */
final class storage_family
{
    /** @param array<string, storage_primitive> $primitives
     * @param array<string, string> $operations Source spellings keyed by storage_role value. */
    public function __construct(public readonly string $provider, public readonly string $id,
        public readonly named_type_definition $descriptor, public readonly named_type_definition $counter,
        public readonly named_type_definition $void, public readonly array $primitives,
        public readonly array $operations, public readonly string $name, public readonly string $namespace_name)
    {
    }
}

/** Compiler semantic roles; source spellings and native implementations remain configurable. */
enum storage_role: string
{
    case allocate = 'allocate';
    case push = 'push';
    case pop = 'pop';
    case count = 'count';
    case release = 'release';
    case transfer = 'transfer';
}

/** One generic source-call declaration, specialized through the ordinary instance identity owner. */
final class storage_function
{
    public readonly allocation_effect $allocation_effect;

    /** Bind a configured source spelling to a semantic role and its reusable ownership effect. */
    public function __construct(public readonly storage_family $family, public readonly storage_role $role,
        public readonly string $name, public readonly string $namespace_name,
        public readonly string $provider, public readonly string $id)
    {
        $effect = match ($role) {
            storage_role::allocate => allocation_effect_kind::acquire,
            storage_role::release => allocation_effect_kind::release,
            storage_role::transfer => allocation_effect_kind::transfer,
            storage_role::count => allocation_effect_kind::observe,
            storage_role::push, storage_role::pop => allocation_effect_kind::mutate,
        };
        $this->allocation_effect = new allocation_effect($effect, 0, $role === storage_role::transfer ? 1 : null);
    }
}

/** Concrete semantic storage; the element ID belongs to its type-store lineage. */
final class element_storage {
    public function __construct(public readonly storage_family $family, public readonly int $element_type,
        public readonly named_type_definition $element)
    {
    }
}
