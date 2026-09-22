<?php
declare(strict_types=1);

/*
 * Role: Validate prepared linkage and signature associations.
 * Used by: LLVM_Backend and Backend_Join
 * Call map:
 *   Callable_Contract::is_current()
 *     -> Backend_Context::parameters_match(); Callable_Contract::link_name()
 */

namespace prepare_backend;

use resolve_types\Type_Resolution;
use resolve_types\Callable_Signature;

/** @compiler-internal Shared callable linkage and validity under LLVM_Backend policy. */
class Callable_Contract
{
    /** Check semantic dependencies, provider passing modes and backend policy before reusing a callable binding. */
    public static function is_current(?callable_binding $binding, Callable_Signature $callable, Type_Resolution $types, backend_configuration $configuration): bool
    {
        $id = $callable->callable_id;
        $signature = $types->signature_for($id);
        if (($binding === null) || (!Backend_Context::parameters_match($binding, $types))) {
            return false;
        }
        foreach ($binding->parameters as $index => $parameter) {
            if (($parameter->extension !== ($callable->external?->abi->parameters[$index]->extension ?? \type_model\abi_extension::none))
                || ($parameter->passing !== $signature->parameter_passing[$index])
                || (($parameter->span !== null) && ($parameter->span !== ($callable->external?->abi->parameters[$index] ?? null)))) {
                return false;
            }
        }
        return ($binding->configuration == $configuration) && ($binding->callable_id === $id)
            && ($binding->signature === $signature) && ($binding->return_definition === $types->definition_for($signature->return_type))
            && ($binding->external === $callable->external) && ($binding->storage === $callable->storage)
            && ($binding->result_passing === self::result_passing($callable, $signature))
            && ($binding->link_name === ($callable->external?->abi->link_name ?? self::link_name($id))) && ($binding->linkage === LLVM_Backend::LINKAGE)
            && ($binding->return_extension === ($callable->external?->abi->result?->extension ?? \type_model\abi_extension::none))
            && ($binding->calling_convention === ($callable->external?->abi->calling_convention ?? LLVM_Backend::CALLING_CONVENTION));
    }

    /** Source and provider results share ownership; provider ABI remains explicitly supplied. */
    public static function result_passing(Callable_Signature $callable, \type_model\signature_representation $signature): \type_model\result_passing
    {
        return $callable->external?->abi->result_passing ?? ($signature->result === \type_model\result_production::owned
            ? \type_model\result_passing::caller_storage : \type_model\result_passing::direct);
    }

    public static function link_name(int $callable_id): string
    {
        // Logical project identity, independent of file order or identifier spelling.
        // Project-wide IDs are unique within this executable; no persistent/library ABI claim.
        return 'scpp_' . $callable_id;
    }

    /** @compiler-internal Validate a role's normalized pointer ABI without allocating another target. */
    public static function lifecycle_matches(abi_target $target, backend_configuration $configuration): bool
    {
        if (($target->lifecycle_operation === null)
            || (count($target->parameters) !== LLVM_Types::lifecycle_arity($target->lifecycle_operation->kind))) {
            return false;
        }
        foreach ($target->parameters as $parameter) {
            if (($parameter->type !== 'ptr') || ($parameter->extension !== \type_model\abi_extension::none)) {
                return false;
            }
        }
        return ($target->link_name === $target->lifecycle_operation->link_name)
            && ($target->calling_convention === $target->lifecycle_operation->calling_convention)
            && ($target->return_type === 'void') && ($target->return_extension === \type_model\abi_extension::none)
            && (LLVM_Types::alloca_address_space($configuration) === 0);
    }

    /** @compiler-internal Shared lifecycle reuse check for selection and joining against fixed provider/target facts. */
    public static function lifecycle_is_current(?Backend_Context $previous, \type_model\runtime_lifecycle_operation|\type_model\source_lifecycle_operation $operation,
        backend_configuration $configuration): bool
    {
        $target = $previous?->abi_for($operation->link_name);
        return ($target !== null) && ($target->lifecycle_operation === $operation)
            && ($previous->configuration == $configuration) && self::lifecycle_matches($target, $configuration);
    }
}
