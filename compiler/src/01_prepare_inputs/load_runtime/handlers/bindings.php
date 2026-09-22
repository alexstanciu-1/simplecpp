<?php
declare(strict_types=1);

/*
 * Role: Private bindings processing for Package_Adapter.
 * Used by: Package_Adapter; methods execute on that single adapter owner
 * Call map: Package_Adapter::call_language_binding(); call_conversion()
 * Output: validated compiler contracts; no canonical type IDs allocated here.
 */

namespace load_runtime;

use type_model\conversion_purpose;
use type_model\language_binding;

trait Binding_Import
{
    /** Validate source-language roles independently of provider names and ordinary source exposure. */
    private static function call_language_binding(array $row, \type_model\semantic_signature $signature): array
    {
        $binding = isset($row['language_binding']) ? language_binding::tryFrom($row['language_binding']) : null;
        if (isset($row['language_binding']) && ($binding === null)) {
            throw new \RuntimeException('Unsupported language operation binding');
        }
        $default_literal = $row['default_literal'] ?? false;
        if (!is_bool($default_literal) || (($default_literal) && ($binding !== language_binding::byte_literal))) {
            throw new \RuntimeException('Invalid default literal binding');
        }
        if (($binding === language_binding::byte_literal)
            && (($signature->result->production !== \type_model\result_production::owned) || (count($signature->parameters) !== 1)
                || ($signature->parameters[0]->passing !== \type_model\argument_passing::byte_span))) {
            throw new \RuntimeException('Byte literal binding requires a span constructor');
        }
        if (($binding === language_binding::echo_value)
            && (($signature->result->production !== \type_model\result_production::none)
                || (count($signature->parameters) !== 1) || ($signature->parameters[0]->passing !== \type_model\argument_passing::borrow_const))) {
            throw new \RuntimeException('Echo binding requires one borrowed object and no result');
        }
        return [$binding, $default_literal];
    }

    /** Import a one-input conversion capability without granting other conversion purposes. */
    private static function call_conversion(array $row, \type_model\semantic_signature $signature): ?conversion_purpose
    {
        if (!isset($row['conversion_purpose'])) {
            return null;
        }
        $parameters = $signature->parameters;
        $result = $signature->result->type;
        $purpose = is_string($row['conversion_purpose']) ? conversion_purpose::tryFrom($row['conversion_purpose']) : null;
        if (!in_array($purpose, [conversion_purpose::explicit_cast, conversion_purpose::text], true)
            || ($row['kind'] !== 'free_function') || isset($row['language_binding']) || (count($parameters) !== 1)
            || ($signature->result->production === \type_model\result_production::none)
            || ($parameters[0]->passing === \type_model\argument_passing::byte_span)) {
            throw new \RuntimeException('Unsupported conversion operation contract');
        }
        if (($parameters[0]->type->name === $result->name) && ($parameters[0]->type->namespace_name === $result->namespace_name)) {
            throw new \RuntimeException('Conversion operation cannot replace type identity');
        }
        return $purpose;
    }

}
