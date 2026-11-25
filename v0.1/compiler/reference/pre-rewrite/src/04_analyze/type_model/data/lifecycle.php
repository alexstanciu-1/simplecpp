<?php
declare(strict_types=1);

/*
 * Role: Type-level lifecycle permissions and implementation references.
 * Used by: runtime import; record composition; backend preparation and emission
 * Flow: validated imported operations or accepted field plans -> shared consumers
 */
namespace type_model;

/** @compiler-api Default initialization is a separate capability from copying and cleanup. */
enum construction_kind: string {
    case unavailable = 'unavailable';
    case zero = 'zero';
    case construct = 'construct';
}

/** @compiler-api Implemented implicit object operations; semantic roles remain distinct. */
enum lifecycle_operation_kind: string
{
    case default_construct = 'default_construct';
    case destroy = 'destroy';
    case copy_construct = 'copy_construct';
    case move_construct = 'move_construct';
    case copy_assign = 'copy_assign';

    /** Whether this operation consumes an existing source as well as its destination. */
    public function has_source(): bool
    {
        return ($this === self::copy_construct) || ($this === self::move_construct) || ($this === self::copy_assign);
    }

    /** Construction starts a destination lifetime; assignment and destruction require a live one. */
    public function creates_destination(): bool
    {
        return ($this === self::default_construct) || ($this === self::copy_construct) || ($this === self::move_construct);
    }

    /** Shared source-language composition rules, independent of target ABI or ownership-state analysis. */
    public function composition(bool $custom_body): lifecycle_order
    {
        $member = match ($this) {
            self::copy_construct => $custom_body ? self::default_construct : $this,
            self::copy_assign => $custom_body ? null : $this,
            default => $this,
        };
        return new lifecycle_order($member, $this === self::destroy, $this === self::destroy);
    }
}

/** @compiler-api Complete-operation ordering; null member_kind means the custom body owns all field updates. */
final class lifecycle_order {
    public function __construct(public readonly ?lifecycle_operation_kind $member_kind,
        public readonly bool $body_before_members, public readonly bool $reverse_members)
    {
    }
}

/** @compiler-api Validated implicit lifecycle implementation, shared by type contracts and ABI preparation. */
final class runtime_lifecycle_operation {
    public function __construct(public readonly string $provider, public readonly string $id,
        public readonly string $link_name, public readonly string $calling_convention,
        public readonly lifecycle_operation_kind $kind)
    {
    }
}

/** @compiler-api One direct constituent; a null operation selects the role's primitive value behavior. */
final class lifecycle_member implements \JsonSerializable
{
    public function __construct(public readonly int $type_id, public readonly int $index,
        public readonly runtime_lifecycle_operation|source_lifecycle_operation|null $operation,
        public readonly lifecycle_operation_kind $kind)
    {
        if (($operation !== null) && ($operation->kind !== $kind)) {
            throw new \InvalidArgumentException('Constituent lifecycle role differs from its implementation');
        }
    }

    /** Debug exports reference the selected operation, without recursively duplicating its plan. */
    public function jsonSerialize(): array
    {
        return ['type_id' => $this->type_id, 'index' => $this->index, 'kind' => $this->kind->value, 'operation' => $this->operation?->link_name];
    }
}

/** @compiler-api Complete compiler-owned operation. Array plans retain one member and a repeat count. */
final class source_lifecycle_operation
{
    public readonly lifecycle_order $order;
    /** Fields are ordered for this role; type IDs belong to the containing lineage.
     * body_symbol_id is a source declaration ID, bound with the concrete receiver after member preparation.
     * @param list<lifecycle_member> $members */
    public function __construct(public readonly int $type_id, public readonly string $link_name,
        public readonly lifecycle_operation_kind $kind, public readonly array $members,
        public readonly int $repeat = 0, public readonly string $calling_convention = 'ccc',
        public readonly int $body_symbol_id = 0)
    {
        $this->order = $kind->composition($body_symbol_id !== 0);
        if (($this->order->member_kind === null) && ($members !== [])) {
            throw new \InvalidArgumentException('Custom assignment owns its field updates without an automatic field plan');
        }
        foreach ($members as $member) {
            if (($member->kind !== $this->order->member_kind)
                && !(($kind === lifecycle_operation_kind::move_construct) && ($member->kind === lifecycle_operation_kind::copy_construct))) {
                throw new \InvalidArgumentException('Constituent lifecycle role differs from its complete operation');
            }
        }
    }
}
