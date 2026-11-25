<?php
declare(strict_types=1);

/*
 * Role: Validate language/conversion binding uniqueness across a fixed callable set.
 * Call map: Package_Adapter::callables(); Input_Join::compose() -> Callable_Bindings::validate()
 * Output: validation only; no selection based on provider or callable spelling
 */
namespace load_runtime;

use type_model\language_binding;

final class Callable_Bindings
{
    /** Reject ambiguous provider roles before publishing the normalized package. */
    public static function validate(array $callables): void
    {
        $seen = [];
        $default = false;
        $conversions = [];
        foreach ($callables as $callable)
        {
            if ($callable->conversion_purpose !== null)
            {
                $source = $callable->signature->parameters[0]->type;
                $result = $callable->signature->result->type;
                $key = json_encode([$callable->conversion_purpose->value, $source->namespace_name, $source->name,
                    $result->namespace_name, $result->name], JSON_THROW_ON_ERROR);
                if (isset($conversions[$key])) {
                    throw new \RuntimeException('Duplicate conversion operation binding');
                }
                $conversions[$key] = true;
            }
            $role = $callable->language_binding;
            if ($role === null) {
                continue;
            }
            $definition = $role === language_binding::byte_literal ? $callable->signature->result->type : $callable->signature->parameters[0]->type;
            $key = json_encode([$role->value, $definition->namespace_name, $definition->name], JSON_THROW_ON_ERROR);
            if (isset($seen[$key]) || (($default) && ($callable->default_literal))) {
                throw new \RuntimeException('Duplicate language operation or default literal binding');
            }
            $seen[$key] = true;
            $default = $default || $callable->default_literal;
        }
    }
}
