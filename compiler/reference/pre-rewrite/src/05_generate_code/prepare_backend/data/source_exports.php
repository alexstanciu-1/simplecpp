<?php
declare(strict_types=1);

/*
 * Role: Fixed source export tasks and compact accepted lifecycle import contracts.
 * Used by: Source_Export_Preparation; Source_Export_Join; project Source_Adapter
 * Flow: accepted identity/layout/operations -> private ABI results -> shared exports.
 * Export tasks retain no AST or stores; final source_linkage references the fixed accepted type snapshot.
 */
namespace prepare_backend;

/** Export availability distinguishes a semantic prohibition from missing compiler support. */
enum source_export_availability: string {
    case available = 'available';
    case forbidden = 'forbidden';
    case unsupported = 'unsupported';
}

/** Native protocol vocabulary includes deferred roles without claiming source implementation support. */
enum source_export_role: string
{
    case default_construct = 'default_construct';
    case copy_construct = 'copy_construct';
    case move_construct = 'move_construct';
    case copy_assign = 'copy_assign';
    case move_assign = 'move_assign';
    case destroy = 'destroy';

    public function implemented_kind(): ?\type_model\lifecycle_operation_kind
    {
        return match ($this) {
            self::move_construct, self::move_assign => null,
            default => \type_model\lifecycle_operation_kind::from($this->value),
        };
    }

    /** Versioned semantic profile; no physical readonly/noalias attributes are inferred from it. */
    public function semantics(): array
    {
        $creates = in_array($this, [self::default_construct, self::copy_construct, self::move_construct], true);
        $source = in_array($this, [self::copy_construct, self::copy_assign], true) ? 'const_live'
            : (in_array($this, [self::move_construct, self::move_assign], true) ? 'mutable_live' : 'none');
        return ['destination_before' => $creates ? 'uninitialized_aligned' : 'live',
            'destination_after' => $this === self::destroy ? 'dead' : 'live_owned',
            'source_access' => $source, 'source_after' => $source === 'const_live' ? 'live_preserved' : ($source === 'none' ? 'none' : 'requires_move_contract'),
            'aliasing' => $creates ? 'disjoint' : ($source === 'none' ? 'exclusive_destination' : 'self_assignment_or_disjoint'),
            'payload_escape' => 'call_scoped', 'failure' => 'terminate', 'unwind' => 'none',
            'resources' => 'selected_field_contracts'];
    }
}

/** Declared semantic permission and complete plan; custom implementation verification follows analysis. */
final class source_export_capability
{
    /** Missing support cannot carry a callable; available always carries a real complete plan. */
    public function __construct(public readonly source_export_role $role,
        public readonly source_export_availability $state, public readonly string $reason,
        public readonly ?\type_model\source_lifecycle_operation $operation = null)
    {
        if ((($state === source_export_availability::available) !== ($operation !== null))
            || (($operation !== null) && ($operation->kind !== $role->implemented_kind()))) {
            throw new \InvalidArgumentException('Source export capability requires its complete supported operation');
        }
    }
}

/** One type's selected export boundary, sharing accepted layout/dependency rows and exact identity keys. */
final class source_export_task
{
    /** @param array<int, \resolve_types\export_type_identity> $identities Reachable layout dependency identities.
     * @param array<string, source_export_capability> $capabilities All six roles. */
    public function __construct(public readonly \compile\native_project $project,
        public readonly \resolve_types\export_type_identity $identity, public readonly storage_layout $layout,
        public readonly array $identities, public readonly array $capabilities)
    {
    }
}

/** Physical import and its current compiler-owned complete implementation remain separate associations. */
final class source_operation_export {
    public function __construct(public readonly source_export_capability $capability,
        public readonly ?abi_target $implementation, public readonly ?abi_target $import)
    {
    }
}

/** Accepted only by Source_Export_Join; the fixed task carries target/layout/lineage provenance. */
final class source_type_export
{
    public const PROFILE = 'inline_source_payload_v1';

    /** @param array<string, source_operation_export> $operations */
    public function __construct(public readonly source_export_task $task, public readonly array $operations)
    {
    }

    /** A demand cannot turn absent support into permission or silently choose a copy for a move. */
    public function require_operation(source_export_role $role): source_operation_export
    {
        $operation = $this->operations[$role->value];
        if ($operation->capability->state !== source_export_availability::available) {
            throw new \RuntimeException('Source export ' . $role->value . ' is ' . $operation->capability->state->value
                . ': ' . $operation->capability->reason);
        }
        return $operation;
    }
}

/** One backend phase's accepted source link roots and verified implementation evidence. */
final class source_linkage {
    /** @param array<string, \analyze_lifetimes\export_verification> $verifications Shared post-analysis evidence. */
    public function __construct(public readonly ?\type_model\Type_Store $types,
        public readonly ?\load_runtime\Runtime_Input_Set $runtime,
        public readonly array $entries, public readonly array $operations, public readonly array $verifications = [])
    {
    }
}
