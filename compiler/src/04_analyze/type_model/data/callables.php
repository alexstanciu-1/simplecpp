<?php
declare(strict_types=1);

/*
 * Role: Normalized callable passing, ABI and lifecycle implementation contracts.
 * Used by: Package_Adapter; symbol/type resolution; backend preparation
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace type_model;

enum abi_extension: string {
    case none = '';
    case sign = 'signext';
    case zero = 'zeroext';
}

/** @compiler-api Direct integer ABI position, normalized independently of JSON/LLVM spelling. */
final class runtime_integer_abi {
    public function __construct(public readonly int $bits, public readonly abi_extension $extension)
    {
    }
}

/** @compiler-api Physical result transport; caller_storage supplies a hidden destination. */
enum result_passing: string {
    case direct = 'direct';
    case caller_storage = 'caller_storage';
}

/** @compiler-api Validated call-scoped typed access, represented by one ABI pointer. */
final class runtime_borrow_abi {
    public readonly abi_extension $extension;
    public function __construct(public readonly bool $mutable = false)
    {
        $this->extension = abi_extension::none;
    }
}

/** @compiler-api Source-language operations bound by provider declarations, independently of callable names. */
enum language_binding: string {
    case byte_literal = 'byte_literal';
    case echo_value = 'echo';
}

/** @compiler-api One borrowed semantic span expands to a pointer and a measured unsigned length. */
final class runtime_byte_span_abi {
    public readonly abi_extension $extension;

    public function __construct(public readonly runtime_integer_abi $length)
    {
        $this->extension = abi_extension::none;
    }
}

/** @compiler-api Physical implementation associated with one semantic signature. */
final class runtime_callable_abi
{
    /** @var list<list<int>> Semantic parameter positions mapped to physical argument positions. */
    public readonly array $parameter_indices;

    /** Build the bounded ABI mapping once; hidden destinations and spans are physical details.
     * @param list<runtime_integer_abi|runtime_borrow_abi|runtime_byte_span_abi> $parameters */
    public function __construct(public readonly string $link_name, public readonly string $calling_convention,
        public readonly ?runtime_integer_abi $result, public readonly array $parameters,
        public readonly result_passing $result_passing = result_passing::direct)
    {
        $offset = $result_passing === result_passing::caller_storage ? 1 : 0;
        $indices = [];
        foreach ($parameters as $parameter) {
            $width = $parameter instanceof runtime_byte_span_abi ? 2 : 1;
            $indices[] = range($offset, $offset + $width - 1);
            $offset += $width;
        }
        $this->parameter_indices = $indices;
    }

    /** Physical facts cannot change semantic passing or claim an unproduced owned result. */
    public function validate(semantic_signature $signature): void
    {
        if (($signature->result->production === result_production::dependent_value)
            || (count($signature->parameters) !== count($this->parameters))
            || (($signature->result->production === result_production::owned)
                !== ($this->result_passing === result_passing::caller_storage))
            || (($signature->result->production === result_production::value) !== ($this->result !== null))) {
            throw new \InvalidArgumentException('Semantic result or parameter count differs from prepared ABI');
        }
        foreach ($signature->parameters as $index => $parameter)
        {
            $abi = $this->parameters[$index];
            $valid = match ($parameter->passing) {
                argument_passing::value => $abi instanceof runtime_integer_abi,
                argument_passing::byte_span => $abi instanceof runtime_byte_span_abi,
                argument_passing::borrow_const => ($abi instanceof runtime_borrow_abi) && (!$abi->mutable),
                argument_passing::borrow_mutable => ($abi instanceof runtime_borrow_abi) && ($abi->mutable),
            };
            if (!$valid) {
                throw new \InvalidArgumentException('Semantic parameter passing differs from prepared ABI');
            }
        }
    }
}

/** @compiler-api Imported identity/exposure joins independent semantic and physical contracts. */
final class runtime_callable
{
    /** Import only a compatible implementation; semantic consumers never infer ownership from ABI. */
    public function __construct(public readonly string $provider, public readonly string $id,
        public readonly string $name, public readonly string $namespace_name,
        public readonly semantic_signature $signature, public readonly runtime_callable_abi $abi,
        public readonly ?language_binding $language_binding = null, public readonly bool $default_literal = false,
        public readonly ?conversion_purpose $conversion_purpose = null)
    {
        $abi->validate($signature);
    }

    public function passing_for(int $index): argument_passing
    {
        return ($this->signature->parameters[$index] ?? throw new \OutOfBoundsException('Missing runtime parameter'))->passing;
    }
}
