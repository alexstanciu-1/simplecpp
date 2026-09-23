<?php
declare(strict_types=1);
namespace resolve_symbols;

const NAME_TYPE = 1;
const NAME_VALUE = 2;
const NAME_TYPE_FAMILY = 3;
const REFERENCE_SOURCE_TYPE = 1;
const REFERENCE_PROVIDED_TYPE = 2;
const REFERENCE_PROVIDED_RECORD = 3;
const REFERENCE_TEMPLATE_TYPE = 4;
const REFERENCE_TEMPLATE_PARAMETER = 5;
const REFERENCE_PROJECT_CONSTANT = 6;
const REFERENCE_LOCAL_CONSTANT = 7;

/** Immutable occurrence target. Numeric domains remain distinct from canonical type IDs. */
final class Name_Binding {
    public function __construct(public readonly int $use_node_id, public readonly int $role,
        public readonly int $kind, public readonly int $target_id,
        public readonly ?\type_model\Named_Definition $provided_type,
        public readonly ?\type_model\Record_Declaration $provided_record = null) {
        if ($use_node_id < 1) { throw new \InvalidArgumentException('Binding requires a source occurrence'); }
        if (($role < \resolve_symbols\NAME_TYPE) || ($role > \resolve_symbols\NAME_TYPE_FAMILY)) { throw new \InvalidArgumentException('Invalid name role'); }
        if ($kind === \resolve_symbols\REFERENCE_PROVIDED_TYPE) {
            if (($provided_type === null) || ($provided_record !== null) || ($target_id !== 0) || ($role !== \resolve_symbols\NAME_TYPE)) { throw new \InvalidArgumentException('Invalid provided type binding'); }
        } elseif ($kind === \resolve_symbols\REFERENCE_PROVIDED_RECORD) {
            if (($provided_record === null) || ($provided_type !== null) || ($target_id !== 0) || ($role !== \resolve_symbols\NAME_TYPE)) { throw new \InvalidArgumentException('Invalid provided record binding'); }
        } else {
            if (($provided_type !== null) || ($provided_record !== null)) { throw new \InvalidArgumentException('Numeric binding cannot carry a provided type'); }
            if ($kind === \resolve_symbols\REFERENCE_TEMPLATE_PARAMETER) {
                if (($target_id < 0) || ($role === \resolve_symbols\NAME_TYPE_FAMILY)) { throw new \InvalidArgumentException('Invalid template parameter binding'); }
            } else {
                if ($target_id < 1) { throw new \InvalidArgumentException('Binding requires a positive target'); }
                if ($kind === \resolve_symbols\REFERENCE_SOURCE_TYPE) {
                    if ($role !== \resolve_symbols\NAME_TYPE) { throw new \InvalidArgumentException('Source type requires type role'); }
                } elseif ($kind === \resolve_symbols\REFERENCE_TEMPLATE_TYPE) {
                    if ($role !== \resolve_symbols\NAME_TYPE_FAMILY) { throw new \InvalidArgumentException('Template type requires family role'); }
                } elseif (($kind === \resolve_symbols\REFERENCE_PROJECT_CONSTANT) || ($kind === \resolve_symbols\REFERENCE_LOCAL_CONSTANT)) {
                    if ($role !== \resolve_symbols\NAME_VALUE) { throw new \InvalidArgumentException('Constant requires value role'); }
                } else { throw new \InvalidArgumentException('Unsupported declaration reference kind'); }
            }
        }
    }
    /** Occurrence position is deliberately excluded; dependencies compare semantic identity. */
    public function same_target(Name_Binding $other): bool {
        return ($this->role === $other->role) && ($this->kind === $other->kind)
            && ($this->target_id === $other->target_id) && ($this->provided_type === $other->provided_type) && ($this->provided_record === $other->provided_record);
    }
}
