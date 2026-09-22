<?php
declare(strict_types=1);
namespace load_runtime;

/** Source-language role, separate from public spelling and conversion permission. */
final class Call_Language_Binding {
    public function __construct(public readonly ?int $binding, public readonly bool $default_literal) {
        if ($binding !== null) { \type_model\Callable_Modes::binding_name($binding); }
        if ($default_literal) {
            if ($binding !== \type_model\BINDING_BYTE_LITERAL) { throw new \InvalidArgumentException('Only byte literals may be default literals'); }
        }
    }
}

final class Binding_Import {
    private static function present(\scpp\Json_View $row, string $key): bool {
        if (!$row->has($key)) { return false; }
        return $row->member($key)->kind() !== 'null';
    }
    public static function call_language_binding(\scpp\Json_View $row, \type_model\Semantic_Signature $signature): Call_Language_Binding {
        $default_literal = false;
        if (Binding_Import::present($row, 'default_literal')) { $default_literal = $row->member('default_literal')->boolean(); }
        if (!Binding_Import::present($row, 'language_binding')) {
            if ($default_literal) { throw new \RuntimeException('Invalid default literal binding'); }
            return new Call_Language_Binding(null, false);
        }
        $name = $row->member('language_binding')->text();
        if (($name !== 'byte_literal') && ($name !== 'echo')) { throw new \RuntimeException('Unsupported language operation binding'); }
        $binding = \type_model\Callable_Modes::binding($name);
        if ($default_literal) {
            if ($binding !== \type_model\BINDING_BYTE_LITERAL) { throw new \RuntimeException('Invalid default literal binding'); }
        }
        if ($signature->parameter_count() !== 1) { throw new \RuntimeException('Language binding requires exactly one parameter'); }
        $parameter = $signature->parameter_at(0);
        if ($binding === \type_model\BINDING_BYTE_LITERAL) {
            if (($signature->result->production !== \type_model\RESULT_OWNED) || ($parameter->passing !== \type_model\PASS_BYTE_SPAN)) { throw new \RuntimeException('Byte literal binding requires a span constructor'); }
        } else {
            if (($signature->result->production !== \type_model\RESULT_NONE) || ($parameter->passing !== \type_model\PASS_BORROW_CONST)) { throw new \RuntimeException('Echo binding requires one borrowed object and no result'); }
        }
        return new Call_Language_Binding($binding, $default_literal);
    }
    /** Ordinary package callables have named, accepted language types at this boundary. */
    public static function call_conversion(\scpp\Json_View $row, \type_model\Semantic_Signature $signature): ?int {
        if (!Binding_Import::present($row, 'conversion_purpose')) { return null; }
        $name = $row->member('conversion_purpose')->text();
        if (($name !== 'explicit_cast') && ($name !== 'text')) { throw new \RuntimeException('Unsupported conversion operation purpose'); }
        if ($row->member('kind')->text() !== 'free_function') { throw new \RuntimeException('Unsupported conversion operation kind'); }
        if (Binding_Import::present($row, 'language_binding')) { throw new \RuntimeException('Conversion cannot have a language binding'); }
        if ($signature->parameter_count() !== 1) { throw new \RuntimeException('Conversion requires one input'); }
        if ($signature->result->production === \type_model\RESULT_NONE) { throw new \RuntimeException('Conversion requires a result'); }
        $parameter = $signature->parameter_at(0);
        if ($parameter->passing === \type_model\PASS_BYTE_SPAN) { throw new \RuntimeException('Conversion cannot consume a byte span'); }
        $result = $signature->result->type;
        if (($parameter->type->kind !== \type_model\TYPE_REFERENCE_NAMED) || ($result->kind !== \type_model\TYPE_REFERENCE_NAMED)) { throw new \RuntimeException('Package conversion requires named language types'); }
        if (($parameter->type->name() === $result->name()) && ($parameter->type->namespace_name() === $result->namespace_name())) { throw new \RuntimeException('Conversion operation cannot replace type identity'); }
        return \type_model\Callable_Modes::conversion($name);
    }
}
