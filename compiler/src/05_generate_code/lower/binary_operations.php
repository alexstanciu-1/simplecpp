<?php
declare(strict_types=1);

/*
 * Role: Validate selected semantic binary contracts and choose supported native primitives.
 * Used by: Lowering_Worker::lower_operation()
 * Call map: Binary_Operations::select() -> operand/capability/result validation
 */
namespace lower;

final class Binary_Operations
{
    /** The result's representation is independent from the exact matched operand types. */
    public static function select(\check_bodies\Checked_Body $body, \type_model\operation_contract $contract): binary_operation
    {
        $binding = $contract->implementation;
        $native = binary_operation::tryFrom($binding->entry);
        if (($binding->kind !== \type_model\implementation_kind::native_operation)
            || ($binding->provider !== 'compiler.integer') || ($native === null)
            || (count($contract->operand_types) !== 2) || ($contract->operand_types[0] !== $contract->operand_types[1])) {
            throw new \LogicException('Unsupported binary operation implementation');
        }
        $type = $contract->operand_types[0];
        $operand = $body->definition_for($type);
        $result = $body->definition_for($contract->result_type);
        $valid = match ($native)
        {
            binary_operation::add_wrap => ($contract->operation === 'addition') && ($contract->result_type === $type)
                && ($operand->addition === \type_model\integer_addition::wrapping),
            binary_operation::less_signed, binary_operation::less_unsigned => ($contract->operation === 'less_than')
                && ($operand->comparison === \type_model\integer_comparison::ordered)
                && ($operand->signed === ($native === binary_operation::less_signed))
                && ($result->representation->kind === \type_model\representation_kind::integer)
                && ($result->representation->payload->bit_width === 1) && ($result->signed === false),
        };
        if (!$valid) {
            throw new \LogicException('Binary operation does not match its semantic contract');
        }
        return $native;
    }
}
