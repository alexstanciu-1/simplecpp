<?php
declare(strict_types=1);
namespace scpp\compiler;

/** Generated compile-time Iterator contract. No runtime interface object or copied signature. */
final class Iterator_Contract
{
    public const NAME = @name@;
    public const METHOD = @method@;

    public static function validate(ast_function_declaration $method, type_definition $element, ?type_definition $key): bool
    {
        if ($method->is_static || $method->is_private || $method->is_protected
            || $method->return_type_def() !== type_bool::instance() || count($method->parameters) !== ($key === null ? 1 : 2)) return false;
        $parameter = $method->parameters[0];
        $valid = $parameter->mode === passing_mode::reference && $parameter->default_value === null
            && $parameter->type_def() === $element;
        if (!$valid || $key === null) return $valid;
        $parameter = $method->parameters[1];
        return $parameter->mode === passing_mode::reference && $parameter->default_value === null
            && $parameter->type_def() === $key;
    }
}
