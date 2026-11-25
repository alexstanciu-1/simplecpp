<?php
declare(strict_types=1);

/*
 * Role: Select exact-operand binary contracts from explicit language capabilities.
 * Used by: Body_Worker expression handlers
 * Call map: Operation_Resolver::binary() -> declared capability and implementation binding
 */
namespace check_bodies;

final class Operation_Resolver
{
    /** Operand permission, result identity and native implementation are selected together. */
    public static function binary(\type_model\Type_Store $types, string $operator, int $left, int $right, int $boolean): ?\type_model\operation_contract
    {
        $definition = $types->definition_for_type($left);
        if ($left !== $right) {
            return null;
        }
        $entry = match ($operator) {
            'addition' => $definition->addition === \type_model\integer_addition::wrapping ? 'add_wrap' : null,
            'less_than' => (($definition->comparison === \type_model\integer_comparison::ordered) && ($boolean !== 0))
                ? ($definition->signed ? 'less_signed' : 'less_unsigned') : null,
            default => null,
        };
        return $entry === null ? null : new \type_model\operation_contract($operator, [$left, $right],
            $operator === 'less_than' ? $boolean : $left,
            new \type_model\implementation_binding(\type_model\implementation_kind::native_operation, 'compiler.integer', $entry));
    }
}
