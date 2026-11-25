<?php
declare(strict_types=1);
namespace load_runtime;

/** Uniqueness across a fixed callable set; names never imply language roles. */
final class Callable_Bindings {
    private static function identity(\type_model\Type_Reference $reference): string {
        if ($reference->kind !== \type_model\TYPE_REFERENCE_NAMED) { throw new \RuntimeException('Callable binding requires named types'); }
        return json_quote($reference->namespace_name()) . ',' . json_quote($reference->name());
    }
    public static function validate(array $callables /** vector<\type_model\Runtime_Callable> */): void {
        $seen /** hash<bool> */ = []; $conversions /** hash<bool> */ = []; $has_default = false;
        foreach ($callables as $callable) {
            $purpose = -1;
            if (take_nullable($purpose, $callable->conversion_purpose)) {
                if ($callable->signature->parameter_count() !== 1) { throw new \RuntimeException('Conversion binding requires one input'); }
                $source = $callable->signature->parameter_at(0)->type;
                $result = $callable->signature->result->type;
                $key = '[' . json_quote(\type_model\Callable_Modes::conversion_name($purpose)) . ',' . Callable_Bindings::identity($source) . ',' . Callable_Bindings::identity($result) . ']';
                if (isset($conversions[$key])) { throw new \RuntimeException('Duplicate conversion operation binding'); }
                $conversions[$key] = true;
            }
            $role = -1;
            if (!take_nullable($role, $callable->language_binding)) { continue; }
            $definition = $callable->signature->result->type;
            if ($role !== \type_model\BINDING_BYTE_LITERAL) {
                if ($callable->signature->parameter_count() !== 1) { throw new \RuntimeException('Language binding requires one input'); }
                $definition = $callable->signature->parameter_at(0)->type;
            }
            $key = '[' . json_quote(\type_model\Callable_Modes::binding_name($role)) . ',' . Callable_Bindings::identity($definition) . ']';
            if (isset($seen[$key]) || ($has_default && $callable->default_literal)) { throw new \RuntimeException('Duplicate language operation or default literal binding'); }
            $seen[$key] = true; $has_default = $has_default || $callable->default_literal;
        }
    }
}
