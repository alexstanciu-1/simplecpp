<?php
declare(strict_types=1);

/*
 * Role: Prepare immutable source-call bindings and implicit lifecycle targets.
 * Used by: LLVM_Backend::run()
 * Call map: Callable_Preparer::prepare_callable() -> [action] bind signature and configuration
 *   Callable_Preparer::prepare_lifecycle() -> [action] prepare one fixed lifecycle task
 */

namespace prepare_backend;

use resolve_types\Callable_Signature;
use resolve_types\Type_Resolution;

class Callable_Preparer
{
    /**
     * @compiler-api Prepare one current signature's linkage/calling contract using verified configuration.
     * Read-only worker; throws for stale or unsupported contracts; no tools/publication.
     */
    public static function prepare_callable(Callable_Signature $callable, Type_Resolution $types, backend_configuration $configuration): callable_binding
    {
        $id = $callable->callable_id;
        if ($types->for_callable($id) !== $callable) {
            throw new \LogicException('Stale backend callable task');
        }
        $signature = $types->signature_for($id);
        $definition = $types->definition_for($signature->return_type);
        $mode = Callable_Contract::result_passing($callable, $signature);
        if ($mode === \type_model\result_passing::direct) {
            LLVM_Types::scalar($definition->representation);
        }
        elseif (!in_array($definition->representation->kind, [\type_model\representation_kind::opaque_inline, \type_model\representation_kind::structure], true)) {
            throw new \LogicException('Caller-storage result requires a record or opaque inline object');
        }
        if ((LLVM_Types::alloca_address_space($configuration) !== 0)
            && (($mode === \type_model\result_passing::caller_storage)
                || (array_filter($signature->parameter_passing, static fn($passing) => $passing->is_borrow()) !== []))) {
            throw new \LogicException('Runtime address passing requires compatible stack and ABI address spaces');
        }
        $parameters = [];
        for ($i = 1; $i <= $signature->count; ++$i)
        {
            $type_id = $types->parameter_type_for($id, $i);
            $parameters[] = new callable_parameter($type_id, $types->definition_for($type_id),
                $callable->external?->abi->parameters[$i - 1]->extension ?? \type_model\abi_extension::none,
                $signature->parameter_passing[$i - 1],
                ($callable->external?->abi->parameters[$i - 1] ?? null) instanceof \type_model\runtime_byte_span_abi
                    ? $callable->external->abi->parameters[$i - 1] : null);
        }
        return new callable_binding($configuration, $id, $signature, $definition,
            $callable->external?->abi->link_name ?? Callable_Contract::link_name($id), LLVM_Backend::LINKAGE,
            $callable->external?->abi->calling_convention ?? LLVM_Backend::CALLING_CONVENTION, $parameters,
            $callable->external?->abi->result?->extension ?? \type_model\abi_extension::none, $callable->external, $mode, $callable->storage);
    }

    /** @compiler-api Prepare one selected lifecycle target with private output; no previous state, tools or publication. */
    public static function prepare_lifecycle(lifecycle_preparation_task $task): lifecycle_preparation_result
    {
        if (LLVM_Types::alloca_address_space($task->configuration) !== 0) {
            throw new \LogicException('Runtime lifecycle calls require compatible stack and ABI address spaces');
        }
        $operation = $task->operation;
        $parameters = [];
        for ($index = 0; $index < LLVM_Types::lifecycle_arity($operation->kind); ++$index) {
            $parameters[] = new abi_parameter('ptr');
        }
        $target = new abi_target($operation->link_name, $operation->calling_convention, 'void', $parameters,
            \type_model\abi_extension::none, $operation);
        return new lifecycle_preparation_result($task, $target);
    }

}
