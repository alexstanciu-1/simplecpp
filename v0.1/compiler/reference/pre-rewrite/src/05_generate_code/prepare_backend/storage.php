<?php
declare(strict_types=1);

/*
 * Role: Prepare native storage primitive ABIs with fixed inputs and private results.
 * Used by: LLVM_Backend; Storage_Join
 * Call map: Storage_Preparation::select() -> prepare(); Storage_Join::join()
 * Output: exact primitive/configuration provenance alongside each physical target.
 */
namespace prepare_backend;

final class Storage_Preparation
{
    /** Current primitive identities come solely from the fixed prepared runtime package. */
    public static function primitives(?\load_runtime\Runtime_Input_Set $runtime): array
    {
        $result = [];
        foreach ($runtime?->storage_families ?? [] as $family)
        {
            foreach ($family->primitives as $primitive) {
                if (isset($result[$primitive->link_name]) && ($result[$primitive->link_name] !== $primitive)) {
                    throw new \LogicException('Conflicting storage primitive identity');
                }
                $result[$primitive->link_name] = $primitive;
            }
        }
        return $result;
    }

    /** Select full or changed primitive/configuration work before formatting ABI targets. */
    public static function select(?\load_runtime\Runtime_Input_Set $runtime, backend_configuration $configuration,
        ?Backend_Context $previous, bool $full): array
    {
        $tasks = [];
        foreach (self::primitives($runtime) as $link => $primitive) {
            $old = $previous?->storage_targets[$link] ?? null;
            if (($full) || ($old?->task->primitive !== $primitive) || ($old->task->configuration !== $configuration)) {
                $tasks[] = new storage_preparation_task($primitive, $configuration);
            }
        }
        return $tasks;
    }

    /** One pure worker formats validated address/integer ABI facts; no source type specialization occurs here. */
    public static function prepare(storage_preparation_task $task): prepared_storage_primitive
    {
        if (LLVM_Types::alloca_address_space($task->configuration) !== 0) {
            throw new \LogicException('Storage primitives require compatible address spaces');
        }
        $primitive = $task->primitive;
        $parameters = array_map(static fn($abi) => new abi_parameter(self::spelling($abi), $abi->extension), $primitive->parameters);
        return new prepared_storage_primitive($task, new abi_target($primitive->link_name, 'ccc',
            self::spelling($primitive->result), $parameters, $primitive->result?->extension ?? \type_model\abi_extension::none));
    }

    /** Acceptance checks the fixed physical contract without rerunning preparation or allocating replacement targets. */
    public static function matches(prepared_storage_primitive $result): bool
    {
        $primitive = $result->task->primitive;
        $target = $result->target;
        if (($target->link_name !== $primitive->link_name) || ($target->calling_convention !== 'ccc')
            || ($target->return_type !== self::spelling($primitive->result))
            || ($target->return_extension !== ($primitive->result?->extension ?? \type_model\abi_extension::none))
            || ($target->lifecycle_operation !== null) || !array_is_list($target->parameters)
            || (count($target->parameters) !== count($primitive->parameters))) {
            return false;
        }
        foreach ($primitive->parameters as $index => $abi) {
            $parameter = $target->parameters[$index];
            if (($parameter->type !== self::spelling($abi)) || ($parameter->extension !== $abi->extension)) {
                return false;
            }
        }
        return LLVM_Types::alloca_address_space($result->task->configuration) === 0;
    }

    private static function spelling(\type_model\runtime_integer_abi|\type_model\runtime_borrow_abi|null $abi): string
    {
        return $abi === null ? 'void' : ($abi instanceof \type_model\runtime_integer_abi ? 'i' . $abi->bits : 'ptr');
    }
}
