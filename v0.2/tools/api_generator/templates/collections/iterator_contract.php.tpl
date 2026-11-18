<?php
declare(strict_types=1);
namespace scpp\compiler;

/** Generated compile-time Iterator contract. No runtime interface object or copied signature. */
final class Iterator_Contract
{
    public const NAME = @name@;
    public const METHOD = @method@;

    public static function validate(ast_function_declaration $method, type_definition $element): bool
    {
        if ($method->is_static || $method->is_private || $method->is_protected
            || $method->return_type_def() !== type_bool::instance() || count($method->parameters) !== 1) return false;
        $parameter = $method->parameters[0];
        return $parameter->mode === passing_mode::reference && $parameter->default_value === null
            && $parameter->type_def() === $element;
    }
}
