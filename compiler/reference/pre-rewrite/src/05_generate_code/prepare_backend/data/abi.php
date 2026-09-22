<?php
declare(strict_types=1);

/*
 * Role: Physical ABI targets and supported integer adaptations.
 * Used by: Callable_Preparer; LLVM_Types; lowering and emission
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace prepare_backend;

/** @compiler-api One physical ABI parameter; LLVM spelling is backend-owned, never source type meaning. */
final class abi_parameter {
    public function __construct(public readonly string $type,
        public readonly \type_model\abi_extension $extension = \type_model\abi_extension::none)
    {
    }
}

/** @compiler-api Shared call/declaration target for source calls and implicit runtime actions. */
final class abi_target
{
    /** @param list<abi_parameter> $parameters */
    public function __construct(public readonly string $link_name, public readonly string $calling_convention,
        public readonly string $return_type, public readonly array $parameters,
        public readonly \type_model\abi_extension $return_extension = \type_model\abi_extension::none,
        public readonly \type_model\runtime_lifecycle_operation|\type_model\source_lifecycle_operation|null $lifecycle_operation = null)
    {
    }
}

/** @compiler-api Prepared backend integer primitive, shared by body and native-entry emission. */
enum integer_adaptation: string {
    case identity = 'identity';
    case truncate = 'trunc';
    case sign_extend = 'sext';
    case zero_extend = 'zext';
}
