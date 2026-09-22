<?php
declare(strict_types=1);
namespace prepare_backend;
const EXPORT_AVAILABLE = 0;
const EXPORT_FORBIDDEN = 1;
const EXPORT_UNSUPPORTED = 2;
const EXPORT_DEFAULT = 1;
const EXPORT_DESTROY = 2;
const EXPORT_COPY = 3;
const EXPORT_MOVE = 4;
const EXPORT_ASSIGN = 5;
const EXPORT_MOVE_ASSIGN = 6;

/** Fixed protocol record; these facts do not imply physical noalias/readonly attributes. */
final class Source_Export_Semantics {
    public function __construct(public readonly string $destination_before, public readonly string $destination_after,
        public readonly string $source_access, public readonly string $source_after, public readonly string $aliasing,
        public readonly string $payload_escape, public readonly string $failure, public readonly string $unwind,
        public readonly string $resources) {}
}
final class Source_Export_Roles {
    public static function name(int $role): string {
        if ($role === \prepare_backend\EXPORT_MOVE_ASSIGN) { return 'move_assign'; }
        return \type_model\Lifecycle_Roles::name($role);
    }
    public static function all(): array /** vector<int> */ {
        $roles /** vector<int> */ = [1,3,4,5,6,2]; return $roles;
    }
    public static function state_name(int $state): string {
        $name = '';
        if ($state === 0) { $name = 'available'; }
        elseif ($state === 1) { $name = 'forbidden'; }
        elseif ($state === 2) { $name = 'unsupported'; }
        else { throw new \InvalidArgumentException('Invalid source export availability'); }
        return $name;
    }
    /** Zero is an explicit unsupported implementation kind, never a copy fallback. */
    public static function implemented_kind(int $role): int {
        Source_Export_Roles::name($role);
        if (($role === \prepare_backend\EXPORT_MOVE) || ($role === \prepare_backend\EXPORT_MOVE_ASSIGN)) { return \type_model\LIFECYCLE_NONE; }
        return $role;
    }
    public static function semantics(int $role): Source_Export_Semantics {
        Source_Export_Roles::name($role);
        $creates = ($role === \prepare_backend\EXPORT_DEFAULT) || ($role === \prepare_backend\EXPORT_COPY) || ($role === \prepare_backend\EXPORT_MOVE);
        $before = $creates ? 'uninitialized_aligned' : 'live';
        $after = $role === \prepare_backend\EXPORT_DESTROY ? 'dead' : 'live_owned';
        $access = 'none'; $source_after = 'none';
        if (($role === \prepare_backend\EXPORT_COPY) || ($role === \prepare_backend\EXPORT_ASSIGN)) { $access = 'const_live'; $source_after = 'live_preserved'; }
        elseif (($role === \prepare_backend\EXPORT_MOVE) || ($role === \prepare_backend\EXPORT_MOVE_ASSIGN)) { $access = 'mutable_live'; $source_after = 'requires_move_contract'; }
        $aliasing = 'disjoint';
        if (!$creates) { $aliasing = $access === 'none' ? 'exclusive_destination' : 'self_assignment_or_disjoint'; }
        return new Source_Export_Semantics($before,$after,$access,$source_after,$aliasing,'call_scoped','terminate','none','selected_field_contracts');
    }
}

/** Available requires a complete supported source operation; unavailable states carry none. */
final class Source_Export_Capability {
    public function __construct(public readonly int $role, public readonly int $state, public readonly string $reason,
        public readonly ?\type_model\Lifecycle_Operation $operation = null) {
        $kind = Source_Export_Roles::implemented_kind($role); Source_Export_Roles::state_name($state);
        if (($state === \prepare_backend\EXPORT_AVAILABLE) !== ($operation !== null)) { throw new \InvalidArgumentException('Source export capability requires its complete supported operation'); }
        if ($operation !== null) {
            if ($operation->imported || ($operation->kind !== $kind)) { throw new \InvalidArgumentException('Source export capability requires its complete supported operation'); }
        }
    }
}
final class Source_Export_Task {
    public function __construct(public readonly \compile\Native_Project $project, public readonly \resolve_types\Export_Type_Identity $identity,
        public readonly Storage_Layout $layout, public readonly array $identities /** hash<\resolve_types\Export_Type_Identity,int> */,
        public readonly array $capabilities /** hash<Source_Export_Capability> */) {}
}
/** Selected physical import and current complete implementation remain separate associations. */
final class Source_Operation_Export {
    public function __construct(public readonly Source_Export_Capability $capability,
        public readonly ?Abi_Target $implementation, public readonly ?Abi_Target $import) {}
}
/** Join acceptance owns publication; this record retains the fixed task and selected operations. */
final class Source_Type_Export {
    public static function profile(): string { return 'inline_source_payload_v1'; }
    public function __construct(public readonly Source_Export_Task $task,
        public readonly array $operations /** hash<Source_Operation_Export> */) {}
    public function require_operation(int $role): Source_Operation_Export {
        $name = Source_Export_Roles::name($role);
        if (!isset($this->operations[$name])) { throw new \LogicException('Missing selected source export operation'); }
        $operation = $this->operations[$name];
        if ($operation->capability->state !== \prepare_backend\EXPORT_AVAILABLE) {
            throw new \RuntimeException('Source export ' . $name . ' is ' . Source_Export_Roles::state_name($operation->capability->state) . ': ' . $operation->capability->reason);
        }
        return $operation;
    }
}
