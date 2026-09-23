<?php
declare(strict_types=1);
namespace collect_symbols;

const PROVIDER_CALLABLE = 1;
const PROVIDER_STORAGE_FAMILY = 2;
const PROVIDER_STORAGE_FUNCTION = 3;
const PROVIDER_FAMILY = 4;
const PROVIDER_METHOD = 5;

/** Closed provider-declaration carrier. Exact owners stay shared; no source frontend or syntax is fabricated. */
final class Provider_Declaration {
    public function __construct(
        private readonly ?\type_model\Runtime_Callable $callable_value,
        private readonly ?\type_model\Storage_Family $storage_family_value,
        private readonly ?\type_model\Storage_Function $storage_function_value,
        private readonly ?\type_model\Family_Declaration $family_value,
        private readonly ?\type_model\Family_Method $method_value) {
        $count = 0;
        if ($callable_value !== null) { $count = $count+1; }
        if ($storage_family_value !== null) { $count = $count+1; }
        if ($storage_function_value !== null) { $count = $count+1; }
        if ($family_value !== null) { $count = $count+1; }
        if ($method_value !== null) { $count = $count+1; }
        if ($count !== 1) { throw new \InvalidArgumentException('Provider declaration requires exactly one authoritative payload'); }
    }
    public function kind(): int {
        $kind = 0;
        if ($this->callable_value !== null) { $kind = \collect_symbols\PROVIDER_CALLABLE; }
        elseif ($this->storage_family_value !== null) { $kind = \collect_symbols\PROVIDER_STORAGE_FAMILY; }
        elseif ($this->storage_function_value !== null) { $kind = \collect_symbols\PROVIDER_STORAGE_FUNCTION; }
        elseif ($this->family_value !== null) { $kind = \collect_symbols\PROVIDER_FAMILY; }
        elseif ($this->method_value !== null) { $kind = \collect_symbols\PROVIDER_METHOD; }
        return $kind;
    }
    public function callable(): \type_model\Runtime_Callable {
        $value = $this->callable_value;
        if ($value === null) { throw new \LogicException('Wrong provider declaration payload'); }
        return $value;
    }
    public function storage_family(): \type_model\Storage_Family {
        $value = $this->storage_family_value;
        if ($value === null) { throw new \LogicException('Wrong provider declaration payload'); }
        return $value;
    }
    public function storage_function(): \type_model\Storage_Function {
        $value = $this->storage_function_value;
        if ($value === null) { throw new \LogicException('Wrong provider declaration payload'); }
        return $value;
    }
    public function family(): \type_model\Family_Declaration {
        $value = $this->family_value;
        if ($value === null) { throw new \LogicException('Wrong provider declaration payload'); }
        return $value;
    }
    public function method(): \type_model\Family_Method {
        $value = $this->method_value;
        if ($value === null) { throw new \LogicException('Wrong provider declaration payload'); }
        return $value;
    }
    public function provider(): string {
        $text = '';
        if ($this->callable_value !== null) { $text = $this->callable_value->provider; }
        elseif ($this->storage_family_value !== null) { $text = $this->storage_family_value->provider; }
        elseif ($this->storage_function_value !== null) { $text = $this->storage_function_value->provider; }
        elseif ($this->family_value !== null) { $text = $this->family_value->provider; }
        elseif ($this->method_value !== null) { $text = $this->method_value->provider; }
        return $text;
    }
    public function id(): string {
        $text = '';
        if ($this->callable_value !== null) { $text = $this->callable_value->id; }
        elseif ($this->storage_family_value !== null) { $text = $this->storage_family_value->id; }
        elseif ($this->storage_function_value !== null) { $text = $this->storage_function_value->id; }
        elseif ($this->family_value !== null) { $text = $this->family_value->id; }
        elseif ($this->method_value !== null) { $text = $this->method_value->id; }
        return $text;
    }
    public function name(): string {
        $text = '';
        if ($this->callable_value !== null) { $text = $this->callable_value->name; }
        elseif ($this->storage_family_value !== null) { $text = $this->storage_family_value->name; }
        elseif ($this->storage_function_value !== null) { $text = $this->storage_function_value->name; }
        elseif ($this->family_value !== null) { $text = $this->family_value->name; }
        elseif ($this->method_value !== null) { $text = $this->method_value->name; }
        return $text;
    }
    public function namespace_name(): string {
        $text = '';
        if ($this->callable_value !== null) { $text = $this->callable_value->namespace_name; }
        elseif ($this->storage_family_value !== null) { $text = $this->storage_family_value->namespace_name; }
        elseif ($this->storage_function_value !== null) { $text = $this->storage_function_value->namespace_name; }
        elseif ($this->family_value !== null) { $text = $this->family_value->namespace_name; }
        elseif ($this->method_value !== null) { $text = $this->method_value->namespace_name; }
        return $text;
    }
    /** Fresh member wrappers may retain an existing declaration only under their exact immutable owners. */
    public function same(Provider_Declaration $other): bool {
        if (($this->kind() !== $other->kind()) || ($this->provider() !== $other->provider()) || ($this->id() !== $other->id())
            || ($this->name() !== $other->name()) || ($this->namespace_name() !== $other->namespace_name())) { return false; }
        $kind = $this->kind();
        if ($kind === \collect_symbols\PROVIDER_CALLABLE) { return $this->callable() === $other->callable(); }
        if ($kind === \collect_symbols\PROVIDER_STORAGE_FAMILY) { return $this->storage_family() === $other->storage_family(); }
        if ($kind === \collect_symbols\PROVIDER_FAMILY) { return $this->family() === $other->family(); }
        if ($kind === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
            $left = $this->storage_function(); $right = $other->storage_function();
            return ($left->family === $right->family) && ($left->role === $right->role);
        }
        $left_method = $this->method(); $right_method = $other->method();
        return ($left_method->family === $right_method->family) && ($left_method->operation === $right_method->operation);
    }
    public static function retain(Provider_Declaration $current, Provider_Declaration $previous): Provider_Declaration {
        if ($current->same($previous)) { return $previous; }
        return $current;
    }
    public function receiver_const(): bool {
        if ($this->kind() !== \collect_symbols\PROVIDER_METHOD) { return false; }
        $operation = $this->method()->operation; $maybe = $operation->receiver; $receiver /** int */ = 0;
        if (!take_nullable($receiver,$maybe)) { throw new \LogicException('Exposed family method requires a receiver'); }
        return $operation->signature->parameter_at($receiver)->passing === \type_model\PASS_BORROW_CONST;
    }
}
