<?php
declare(strict_types=1);
namespace type_model;
const STORAGE_ALLOCATE = 0;
const STORAGE_PUSH = 1;
const STORAGE_POP = 2;
const STORAGE_COUNT = 3;
const STORAGE_RELEASE = 4;
const STORAGE_TRANSFER = 5;

/** Source spellings are metadata; these roles own the fixed semantic effects. */
final class Storage_Roles {
    public static function name(int $role): string {
        $name = '';
        if ($role === \type_model\STORAGE_ALLOCATE) { $name = 'allocate'; }
        elseif ($role === \type_model\STORAGE_PUSH) { $name = 'push'; }
        elseif ($role === \type_model\STORAGE_POP) { $name = 'pop'; }
        elseif ($role === \type_model\STORAGE_COUNT) { $name = 'count'; }
        elseif ($role === \type_model\STORAGE_RELEASE) { $name = 'release'; }
        elseif ($role === \type_model\STORAGE_TRANSFER) { $name = 'transfer'; }
        else { throw new \InvalidArgumentException('Unknown storage role'); }
        return $name;
    }
    public static function parse(string $name): int {
        $found = -1;
        for ($role = 0; $role < 6; $role++) { if (Storage_Roles::name($role) === $name) { $found = $role; break; } }
        if ($found === -1) { throw new \InvalidArgumentException('Unknown storage role'); }
        return $found;
    }
    public static function effect(int $role): Allocation_Effect {
        Storage_Roles::name($role);
        $kind = \type_model\ALLOCATION_MUTATE;
        if ($role === \type_model\STORAGE_ALLOCATE) { $kind = \type_model\ALLOCATION_ACQUIRE; }
        elseif ($role === \type_model\STORAGE_RELEASE) { $kind = \type_model\ALLOCATION_RELEASE; }
        elseif ($role === \type_model\STORAGE_COUNT) { $kind = \type_model\ALLOCATION_OBSERVE; }
        if ($role === \type_model\STORAGE_TRANSFER) { return new Allocation_Effect(\type_model\ALLOCATION_TRANSFER, 0, 1); }
        return new Allocation_Effect($kind, 0);
    }
}

/** Low-level integer/address ABI. Addresses never become ordinary source values. */
final class Storage_Primitive {
    private array $parameters /** vector<Runtime_Abi_Position> */ = [];
    public function __construct(public readonly string $link_name, public readonly ?Runtime_Abi_Position $result,
        array $parameters /** vector<Runtime_Abi_Position> */) {
        if ($result !== null) { Storage_Primitive::require_position($result); }
        foreach ($parameters as $parameter) { Storage_Primitive::require_position($parameter); $this->parameters[] = $parameter; }
    }
    private static function require_position(Runtime_Abi_Position $position): void {
        if (($position->kind !== \type_model\ABI_INTEGER) && ($position->kind !== \type_model\ABI_BORROW)) { throw new \InvalidArgumentException('Storage primitive requires integer/address ABI'); }
    }
    public function parameter_count(): int { return q_count($this->parameters); }
    public function parameter_at(int $index): Runtime_Abi_Position {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \OutOfBoundsException('Missing storage primitive parameter'); }
        return $this->parameters[$index];
    }
}

/** Provider-owned physical descriptor plus configured source operation spellings. */
final class Storage_Family {
    private array $primitives /** hash<Storage_Primitive> */ = [];
    private array $operations /** hash<string> */ = [];
    public function __construct(public readonly string $provider, public readonly string $id,
        public readonly Named_Definition $descriptor, public readonly Named_Definition $counter,
        public readonly Named_Definition $void_type, array $primitives /** hash<Storage_Primitive> */,
        array $operations /** hash<string> */, public readonly string $name, public readonly string $namespace_name) {
        foreach ($primitives as $key => $primitive) { $this->primitives[$key] = $primitive; }
        foreach ($operations as $role => $spelling) { Storage_Roles::parse($role); $this->operations[$role] = $spelling; }
    }
    public function primitive_for(string $name): Storage_Primitive {
        if (!isset($this->primitives[$name])) { throw new \OutOfBoundsException('Missing storage primitive'); }
        return $this->primitives[$name];
    }
    public function operation_name(int $role): string {
        $key = Storage_Roles::name($role);
        if (!isset($this->operations[$key])) { throw new \OutOfBoundsException('Missing storage operation spelling'); }
        return $this->operations[$key];
    }
}

final class Storage_Function {
    private ?Allocation_Effect $effect = null;
    public function __construct(public readonly Storage_Family $family, public readonly int $role,
        public readonly string $name, public readonly string $namespace_name,
        public readonly string $provider, public readonly string $id) {
        $this->effect = Storage_Roles::effect($role);
    }
    public function allocation_effect(): Allocation_Effect {
        $effect = $this->effect;
        if ($effect === null) { throw new \LogicException('Storage effect is not initialized'); }
        return $effect;
    }
}

/** Element IDs are interpreted only in the containing type-store lineage. */
final class Element_Storage {
    public function __construct(public readonly Storage_Family $family, public readonly int $element_type,
        public readonly Named_Definition $element) {
        if ($element_type < 1) { throw new \InvalidArgumentException('Typed storage requires an element type ID'); }
    }
}
