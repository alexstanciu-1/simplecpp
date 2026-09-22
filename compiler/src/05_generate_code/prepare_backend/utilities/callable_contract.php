<?php
declare(strict_types=1);
namespace prepare_backend;

/** Prepared physical lifecycle ABI checks; semantic permission remains with its accepted operation. */
final class Callable_Contract {
    /** First well-formed LLVM DataLayout A entry owns stack address space; absence means zero. */
    private static function default_stack(string $layout): bool {
        $start = 0; $length = string_byte_len($layout);
        for ($end = 0; $end < $length + 1; $end++) {
            if ($end < $length) { if (string_byte_at($layout,$end) !== 45) { continue; } }
            if ($end > $start + 1) {
                if (string_byte_at($layout,$start) === 65) {
                    $valid = true; $zero = true;
                    for ($index = $start + 1; $index < $end; $index++) {
                        $byte = string_byte_at($layout,$index);
                        if (($byte < 48) || ($byte > 57)) { $valid = false; }
                        if ($byte !== 48) { $zero = false; }
                    }
                    if ($valid) { return $zero; }
                }
            }
            $start = $end + 1;
        }
        return true;
    }
    public static function lifecycle_matches(Abi_Target $target, Backend_Configuration $configuration): bool {
        $operation = $target->lifecycle_operation;
        if ($operation === null) { return false; }
        $arity = \type_model\Lifecycle_Roles::has_source($operation->kind) ? 2 : 1;
        if (q_count($target->parameters) !== $arity) { return false; }
        foreach ($target->parameters as $parameter) {
            if (($parameter->type !== 'ptr') || ($parameter->extension !== 0)) { return false; }
        }
        return ($target->link_name === $operation->link_name) && ($target->calling_convention === $operation->calling_convention)
            && ($target->return_type === 'void') && ($target->return_extension === 0)
            && Callable_Contract::default_stack($configuration->data_layout);
    }
}
