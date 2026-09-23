<?php
declare(strict_types=1);
namespace check_bodies;
/** Select exact operands from declared language capabilities, without inferred coercion. */
final class Operation_Resolver {
    public static function binary(\type_model\Type_Store $types, string $operation, int $left, int $right, int $boolean): ?\type_model\Operation_Contract {
        $definition = $types->definition_for_type($left);
        if ($left !== $right) { return null; }
        $entry = '';
        $result = $left;
        if ($operation === 'addition') {
            if ($definition->wrapping_addition) { $entry = 'add_wrap'; }
        } elseif ($operation === 'less_than') {
            if ($definition->ordered_comparison) {
                if ($boolean !== 0) {
                    $entry = 'less_unsigned';
                    if ($definition->signed === true) { $entry = 'less_signed'; }
                    $result = $boolean;
                }
            }
        }
        if ($entry === '') { return null; }
        $operands /** vector<int> */ = [$left, $right];
        return new \type_model\Operation_Contract($operation, $operands, $result,
            new \type_model\Implementation_Binding(\type_model\IMPLEMENTATION_NATIVE_OPERATION, 'compiler.integer', $entry));
    }
}
