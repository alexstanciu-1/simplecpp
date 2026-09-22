<?php
declare(strict_types=1);

/*
 * Role: Validate concrete operation imports against the accepted family declaration.
 * Call map: Family_Preparation_Join -> Family_Operations::validate() -> reference()
 * Output: permission-preserving association; no preparation or canonical allocation.
 */
namespace load_runtime;

final class Family_Operations
{
    /** Check complete declared semantic positions independently of measured physical ABI. */
    public static function validate(family_preparation_result $result, string $id, \type_model\runtime_callable $callable): void
    {
        $context = $result->task->context;
        $family = $context->definition->external;
        $operation = $family->definition->operations[$id] ?? null;
        $binding = self::binding($context, $id);
        if (($operation?->expose_as === null) || ($callable->provider !== $result->package->provider)
            || ($callable->name !== $binding->name) || ($callable->namespace_name !== $binding->namespace_name)) {
            throw new \LogicException('Unexpected prepared family operation');
        }
        $expected = $operation->signature;
        $actual = $callable->signature;
        if ((count($expected->parameters) !== count($actual->parameters))
            || ($expected->allocation_effect != $actual->allocation_effect)) {
            throw new \LogicException('Prepared family operation changed its semantic signature');
        }
        foreach ($expected->parameters as $index => $parameter) {
            if (($parameter->passing !== $actual->parameters[$index]->passing)
                || (self::reference($result, $parameter->type) != $actual->parameters[$index]->type)) {
                throw new \LogicException('Prepared family operation changed a parameter contract');
            }
        }
        $name = self::reference($result, $expected->result->type);
        $production = $expected->result->production;
        if ($production === \type_model\result_production::dependent_value)
        {
            $reference = $expected->result->type;
            $definition = $reference instanceof \type_model\parameter_type_reference
                ? $context->arguments[$reference->slot]->type
                : $result->package->catalog->find_type($name->name, $name->namespace_name);
            $production = $definition->representation->kind === \type_model\representation_kind::integer
                ? \type_model\result_production::value : \type_model\result_production::owned;
        }
        if (($name != $actual->result->type) || ($production !== $actual->result->production)) {
            throw new \LogicException('Prepared family operation changed its result contract');
        }
    }

    /** Internal exposure identifies the complete semantic operation within its allocated type instance. */
    public static function binding(\instantiate\instance_context $context, string $operation): \type_model\named_type_reference
    {
        return new \type_model\named_type_reference($context->type_name() . ':' . $operation, $context->type_namespace());
    }

    /** Substitute only exact declared formals, provider mappings and the owning self application. */
    private static function reference(family_preparation_result $result, \type_model\type_reference $reference): \type_model\named_type_reference
    {
        $context = $result->task->context;
        $family = $context->definition->external;
        if ($reference instanceof \type_model\parameter_type_reference) {
            $type = $context->arguments[$reference->slot]->type;
            return new \type_model\named_type_reference($type->name, $type->namespace_name);
        }
        if ($reference instanceof \type_model\family_type_reference) {
            return new \type_model\named_type_reference($context->type_name(), $context->type_namespace());
        }
        if ($reference instanceof \type_model\provider_type_reference) {
            return $family->language_types[json_encode([$reference->provider, $reference->id], JSON_THROW_ON_ERROR)];
        }
        throw new \LogicException('Unsupported prepared family operation reference');
    }
}
