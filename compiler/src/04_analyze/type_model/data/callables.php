<?php
declare(strict_types=1);
namespace type_model;

/** Integer, borrowed address, or pointer/length pair; no semantic ownership inference. */
final class Runtime_Abi_Position {
    public function __construct(public readonly int $kind, public readonly int $bits,
        public readonly int $extension, public readonly bool $mutable,
        public readonly ?Runtime_Abi_Position $length) {
        Callable_Modes::extension_name($extension);
        if ($kind === \type_model\ABI_INTEGER) {
            if (($bits < 1) || $mutable || ($length !== null)) { throw new \InvalidArgumentException('Invalid integer ABI position'); }
        } elseif ($kind === \type_model\ABI_BORROW) {
            if (($bits !== 0) || ($extension !== \type_model\ABI_EXTENSION_NONE) || ($length !== null)) { throw new \InvalidArgumentException('Invalid borrow ABI position'); }
        } elseif ($kind === \type_model\ABI_BYTE_SPAN) {
            if (($bits !== 0) || ($extension !== \type_model\ABI_EXTENSION_NONE) || $mutable) { throw new \InvalidArgumentException('Invalid byte span ABI position'); }
            if ($length === null) { throw new \InvalidArgumentException('Byte span requires length ABI'); }
            if ($length->kind !== \type_model\ABI_INTEGER) { throw new \InvalidArgumentException('Byte span requires integer length ABI'); }
        } else { throw new \InvalidArgumentException('Unknown ABI position kind'); }
    }
    public static function integer(int $bits, int $extension): Runtime_Abi_Position {
        return new Runtime_Abi_Position(\type_model\ABI_INTEGER, $bits, $extension, false, null);
    }
    public static function borrow(bool $mutable): Runtime_Abi_Position {
        return new Runtime_Abi_Position(\type_model\ABI_BORROW, 0, \type_model\ABI_EXTENSION_NONE, $mutable, null);
    }
    public static function byte_span(Runtime_Abi_Position $length): Runtime_Abi_Position {
        return new Runtime_Abi_Position(\type_model\ABI_BYTE_SPAN, 0, \type_model\ABI_EXTENSION_NONE, false, $length);
    }
}

/** Semantic positions map to contiguous physical slots; hidden results occupy slot zero. */
final class Runtime_Callable_Abi {
    private array $parameters /** vector<Runtime_Abi_Position> */ = [];
    private array $indices /** vector<vector<int>> */ = [];
    public function __construct(public readonly string $link_name, public readonly string $calling_convention,
        public readonly ?Runtime_Abi_Position $result, array $parameters /** vector<Runtime_Abi_Position> */,
        public readonly int $result_passing = 0) {
        Callable_Modes::result_name($result_passing);
        if ($result !== null) {
            if ($result->kind !== \type_model\ABI_INTEGER) { throw new \InvalidArgumentException('Callable direct result requires integer ABI'); }
        }
        $offset = $result_passing === \type_model\ABI_RESULT_CALLER_STORAGE ? 1 : 0;
        foreach ($parameters as $parameter) {
            $slots /** vector<int> */ = [$offset]; $offset = $offset + 1;
            if ($parameter->kind === \type_model\ABI_BYTE_SPAN) { $slots[] = $offset; $offset = $offset + 1; }
            $this->indices[] = $slots; $this->parameters[] = $parameter;
        }
    }
    public function parameter_count(): int { return q_count($this->parameters); }
    public function parameter_at(int $index): Runtime_Abi_Position {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \OutOfBoundsException('Missing ABI parameter'); }
        return $this->parameters[$index];
    }
    public function parameter_indices(int $index): array /** vector<int> */ {
        if (($index < 0) || ($index >= q_count($this->indices))) { throw new \OutOfBoundsException('Missing ABI parameter indices'); }
        $copy /** vector<int> */ = []; foreach ($this->indices[$index] as $slot) { $copy[] = $slot; } return $copy;
    }
    public function validate(Semantic_Signature $signature): void {
        $production = $signature->result->production;
        if (($production === \type_model\RESULT_DEPENDENT_VALUE) || ($signature->parameter_count() !== $this->parameter_count())
            || (($production === \type_model\RESULT_OWNED) !== ($this->result_passing === \type_model\ABI_RESULT_CALLER_STORAGE))
            || (($production === \type_model\RESULT_VALUE) !== ($this->result !== null))) {
            throw new \InvalidArgumentException('Semantic result or parameter count differs from prepared ABI');
        }
        for ($index = 0; $index < $signature->parameter_count(); $index++) {
            $parameter = $signature->parameter_at($index); $abi = $this->parameter_at($index); $valid = false;
            if ($parameter->passing === \type_model\PASS_VALUE) { $valid = $abi->kind === \type_model\ABI_INTEGER; }
            elseif ($parameter->passing === \type_model\PASS_BYTE_SPAN) { $valid = $abi->kind === \type_model\ABI_BYTE_SPAN; }
            elseif ($parameter->passing === \type_model\PASS_BORROW_CONST) { $valid = ($abi->kind === \type_model\ABI_BORROW) && (!$abi->mutable); }
            elseif ($parameter->passing === \type_model\PASS_BORROW_MUTABLE) { $valid = ($abi->kind === \type_model\ABI_BORROW) && $abi->mutable; }
            if (!$valid) { throw new \InvalidArgumentException('Semantic parameter passing differs from prepared ABI'); }
        }
    }
}

/** Import identity and source exposure retain separately validated semantic/physical contracts. */
final class Runtime_Callable {
    public function __construct(public readonly string $provider, public readonly string $id,
        public readonly string $name, public readonly string $namespace_name,
        public readonly Semantic_Signature $signature, public readonly Runtime_Callable_Abi $abi,
        public readonly ?int $language_binding = null, public readonly bool $default_literal = false,
        public readonly ?int $conversion_purpose = null) {
        $abi->validate($signature);
        if ($language_binding !== null) { Callable_Modes::binding_name($language_binding); }
        if ($conversion_purpose !== null) { Callable_Modes::conversion_name($conversion_purpose); }
    }
    public function passing_for(int $index): int { return $this->signature->parameter_at($index)->passing; }
}
