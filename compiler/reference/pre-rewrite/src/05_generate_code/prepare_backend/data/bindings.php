<?php
declare(strict_types=1);

/*
 * Role: Prepared callable bindings and semantic parameter contracts.
 * Used by: Callable_Preparer; Backend_Join; lowering and emission
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace prepare_backend;

/** @compiler-api One prepared semantic parameter and ABI passing mode; type belongs to the signature lineage. */
final class callable_parameter
{
    /** Validate value/span passing, object borrowing or const scalar borrowing against the shared semantic type definition. */
    public function __construct(
        public readonly int $type_id,
        public readonly \type_model\named_type_definition $definition,
        public readonly \type_model\abi_extension $extension = \type_model\abi_extension::none,
        public readonly \type_model\argument_passing $passing = \type_model\argument_passing::value,
        public readonly ?\type_model\runtime_byte_span_abi $span = null,
    )
    {
        if ($type_id <= 0) {
            throw new \InvalidArgumentException('Invalid callable parameter type');
        }
        if (($passing === \type_model\argument_passing::byte_span) !== ($span !== null)) {
            throw new \InvalidArgumentException('Only byte-span parameters carry a length ABI');
        }
        if ($passing === \type_model\argument_passing::value) {
            LLVM_Types::scalar($definition->representation);
        }
        elseif ($passing === \type_model\argument_passing::byte_span) {
            if (($span === null) || ($definition->representation->kind !== \type_model\representation_kind::byte_span)) {
                throw new \InvalidArgumentException('Byte-span passing requires its physical length contract');
            }
        }
        elseif (!in_array($definition->representation->kind,
            [\type_model\representation_kind::opaque_inline, \type_model\representation_kind::structure], true)
            && !(($passing === \type_model\argument_passing::borrow_const)
                && ($definition->representation->kind === \type_model\representation_kind::integer))) {
            throw new \InvalidArgumentException('Borrowed parameter requires inline object storage or a const scalar');
        }
    }
}

/**
 * @compiler-api Read-only callable contract produced by backend preparation.
 * Lowering/emission read all fields: concrete callable ID, shared signature/return
 * definition, link name, linkage, calling convention and configuration. Consumers
 * use these prepared facts without resolving the callee or choosing ABI policy.
 * References belong to the prepared type/backend snapshots, not just numeric IDs.
 * Parameters keep their semantic types; passing modes select direct values or borrowed addresses.
 */
final class callable_binding
{
    /** @compiler-api Shared physical call target; source signature and callable identity remain separate. */
    public readonly abi_target $abi;
    /** Validate the semantic binding and derive its shared physical ABI target once during preparation. */
    public function __construct(
        public readonly backend_configuration $configuration,
        public readonly int $callable_id,
        public readonly \type_model\signature_representation $signature,
        public readonly \type_model\named_type_definition $return_definition,
        public readonly string $link_name,
        public readonly string $linkage,
        public readonly string $calling_convention,

        /** @var list<callable_parameter> Ordered semantic parameter contracts. */
        public readonly array $parameters = [],
        public readonly \type_model\abi_extension $return_extension = \type_model\abi_extension::none,
        public readonly ?\type_model\runtime_callable $external = null,
        public readonly \type_model\result_passing $result_passing = \type_model\result_passing::direct,
        public readonly ?\type_model\storage_function $storage = null,
    )
    {
        if (($callable_id <= 0) || ($link_name === '') || ($linkage === '') || ($calling_convention === '')) {
            throw new \InvalidArgumentException('Incomplete backend callable binding');
        }
        if ((!array_is_list($parameters)) || (count($parameters) !== $signature->count)) {
            throw new \InvalidArgumentException('Incomplete backend callable parameters');
        }
        $this->abi = LLVM_Types::call_target($this);
    }
}
