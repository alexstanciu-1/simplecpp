<?php
declare(strict_types=1);
namespace load_runtime;

/** Normalized semantic result and its physical return/hidden-slot contract. */
final class Call_Result_Position {
    public function __construct(public readonly \type_model\Named_Definition $definition,
        public readonly \type_model\Semantic_Result $semantic, public readonly int $passing,
        public readonly ?\type_model\Runtime_Abi_Position $abi, public readonly int $next_position) {}
}
final class Call_Parameter_Position {
    public function __construct(public readonly \type_model\Semantic_Parameter $semantic,
        public readonly \type_model\Runtime_Abi_Position $abi, public readonly int $next_position) {}
}

/** Physical parameter/result normalization; complete callable publication is a separate step. */
final class Callable_Abi_Import {
    private static function type(\scpp\Json_View $row, array $types /** hash<Runtime_Type> */): Runtime_Type {
        $id = Package_Syntax::identifier($row->member('type'));
        if (!isset($types[$id])) { throw new \RuntimeException('Unknown runtime callable type'); }
        return $types[$id];
    }
    private static function text(\scpp\Json_View $row, string $key, string $expected): void {
        if ($row->member($key)->text() !== $expected) { throw new \RuntimeException('Unsupported runtime callable ' . $key); }
    }
    private static function position(array $positions /** vector<\scpp\Json_View> */, int $index): \scpp\Json_View {
        if (($index < 0) || ($index >= q_count($positions))) { throw new \RuntimeException('Missing runtime ABI position'); }
        return $positions[$index];
    }
    private static function language_reference(Runtime_Type $type): \type_model\Type_Reference {
        $definition = $type->language_type;
        if ($definition !== null) { return \type_model\Type_Reference::named($definition->name, $definition->namespace_name); }
        $record = $type->record;
        if ($record === null) { throw new \RuntimeException('Runtime parameter requires a language type'); }
        return \type_model\Type_Reference::named($record->name, $record->namespace_name);
    }
    public static function call_result(\scpp\Json_View $row, array $types /** hash<Runtime_Type> */, array $positions /** vector<\scpp\Json_View> */): Call_Result_Position {
        $result = $row->member('result'); $physical = $row->member('abi');
        $type = Callable_Abi_Import::type($result, $types);
        $definition = $type->language_type;
        if ($definition === null) { throw new \RuntimeException('Runtime result requires an accepted named definition'); }
        $reference = \type_model\Type_Reference::named($definition->name, $definition->namespace_name);
        $passing = $result->member('passing')->text();
        if ($passing === 'caller_storage') {
            $kind = $row->member('kind')->text();
            if (($kind !== 'construct') && ($kind !== 'construct_from_bytes') && ($kind !== 'free_function')) { throw new \RuntimeException('Unsupported caller-storage operation'); }
            if (($type->storage->kind !== \load_runtime\RUNTIME_STORAGE_OPAQUE) && ($type->storage->kind !== \load_runtime\RUNTIME_STORAGE_RECORD)) { throw new \RuntimeException('Unsupported caller-storage type'); }
            Callable_Abi_Import::text($result, 'ownership', 'owned');
            if ($result->member('abi_index')->integer() !== 0) { throw new \RuntimeException('Invalid caller-storage position'); }
            Callable_Abi_Import::text($row, 'storage_precondition', 'aligned_uninitialized_storage');
            Callable_Abi_Import::text($physical, 'return_type', 'void');
            Callable_Abi_Import::text($physical, 'return_attributes', '');
            if ($kind === 'free_function') { Callable_Abi_Import::text($row, 'storage_after', 'live_owned_object'); }
            Package_Syntax::address_abi(Callable_Abi_Import::position($positions, 0));
            return new Call_Result_Position($definition, new \type_model\Semantic_Result($reference, \type_model\RESULT_OWNED), \type_model\ABI_RESULT_CALLER_STORAGE, null, 1);
        }
        if ($passing !== 'direct') { throw new \RuntimeException('Unsupported runtime result passing'); }
        Callable_Abi_Import::text($result, 'ownership', 'value');
        if ($type->storage->kind === \load_runtime\RUNTIME_STORAGE_VOID) {
            Callable_Abi_Import::text($physical, 'return_type', 'void');
            Callable_Abi_Import::text($physical, 'return_attributes', '');
            return new Call_Result_Position($definition, new \type_model\Semantic_Result($reference, \type_model\RESULT_NONE), \type_model\ABI_RESULT_DIRECT, null, 0);
        }
        $abi = Package_Syntax::integer_abi($physical->member('return_type'), $physical->member('return_attributes'), $type);
        return new Call_Result_Position($definition, new \type_model\Semantic_Result($reference, \type_model\RESULT_VALUE), \type_model\ABI_RESULT_DIRECT, $abi, 0);
    }
    /** Decimal ABI width, checked before multiplication; malformed/overflowing widths are rejected. */
    private static function length_bits(string $name): int {
        if (string_byte_len($name) < 2) { throw new \RuntimeException('Invalid byte-span length ABI'); }
        if (string_byte_at($name, 0) !== 105) { throw new \RuntimeException('Invalid byte-span length ABI'); }
        if (string_byte_at($name, 1) === 48) { throw new \RuntimeException('Invalid byte-span length ABI'); }
        $value = 0;
        for ($index = 1; $index < string_byte_len($name); $index++) {
            $digit = string_byte_at($name, $index) - 48;
            if (($digit < 0) || ($digit > 9)) { throw new \RuntimeException('Invalid byte-span length ABI'); }
            if (($value > 922337203685477580) || (($value === 922337203685477580) && ($digit > 7))) { throw new \RuntimeException('Byte-span length width overflows'); }
            $value = $value * 10 + $digit;
        }
        return $value;
    }
    public static function call_parameter(\scpp\Json_View $parameter, array $types /** hash<Runtime_Type> */, array $positions /** vector<\scpp\Json_View> */, int $offset): Call_Parameter_Position {
        $type = Callable_Abi_Import::type($parameter, $types);
        $reference = Callable_Abi_Import::language_reference($type);
        $passing = $parameter->member('passing')->text();
        $width = 1;
        if ($passing === 'byte_span') { $width = 2; }
        if (($offset < 0) || ($offset > q_count($positions) - $width)) { throw new \RuntimeException('Missing runtime ABI positions'); }
        $indices = $parameter->member('abi_indices');
        if ($indices->kind() !== 'array') { throw new \RuntimeException('Invalid semantic ABI indices'); }
        if ($indices->size() !== $width) { throw new \RuntimeException('Invalid semantic ABI indices'); }
        for ($index = 0; $index < $width; $index++) {
            if ($indices->at($index)->integer() !== $offset + $index) { throw new \RuntimeException('Invalid semantic ABI index'); }
        }
        $position = Callable_Abi_Import::position($positions, $offset);
        if ($passing === 'byte_span') {
            if ($type->storage->kind !== \load_runtime\RUNTIME_STORAGE_BYTE_SPAN) { throw new \RuntimeException('Byte span requires span storage'); }
            Callable_Abi_Import::text($parameter, 'ownership', 'borrowed');
            Callable_Abi_Import::text($parameter, 'borrow_scope', 'call');
            if ($parameter->member('length_signed')->boolean()) { throw new \RuntimeException('Byte-span length must be unsigned'); }
            Package_Syntax::address_abi($position);
            $length = Callable_Abi_Import::position($positions, $offset + 1);
            $spelling = $length->member('type')->text(); $bits = Callable_Abi_Import::length_bits($spelling);
            Callable_Abi_Import::text($parameter, 'length_abi_type', $spelling);
            $attributes = $length->member('attributes')->text();
            if (($attributes !== '') && ($attributes !== 'noundef')) { throw new \RuntimeException('Unsupported byte-span length attributes'); }
            $abi = \type_model\Runtime_Abi_Position::byte_span(\type_model\Runtime_Abi_Position::integer($bits, \type_model\ABI_EXTENSION_NONE));
            return new Call_Parameter_Position(new \type_model\Semantic_Parameter($reference, \type_model\PASS_BYTE_SPAN), $abi, $offset + 2);
        }
        if (($passing === 'const_address') || ($passing === 'mutable_address')) {
            Callable_Abi_Import::text($parameter, 'ownership', 'borrowed');
            Callable_Abi_Import::text($parameter, 'borrow_scope', 'call');
            $allowed = ($type->storage->kind === \load_runtime\RUNTIME_STORAGE_OPAQUE) || ($type->storage->kind === \load_runtime\RUNTIME_STORAGE_RECORD);
            if ($type->storage->kind === \load_runtime\RUNTIME_STORAGE_INTEGER) { $allowed = $passing === 'const_address'; }
            if (!$allowed) { throw new \RuntimeException('Unsupported runtime borrowed parameter'); }
            Package_Syntax::address_abi($position);
            $is_mutable = $passing === 'mutable_address';
            $mode = \type_model\PASS_BORROW_CONST;
            if ($is_mutable) { $mode = \type_model\PASS_BORROW_MUTABLE; }
            return new Call_Parameter_Position(new \type_model\Semantic_Parameter($reference, $mode), \type_model\Runtime_Abi_Position::borrow($is_mutable), $offset + 1);
        }
        if ($passing !== 'direct') { throw new \RuntimeException('Unsupported runtime parameter passing'); }
        Callable_Abi_Import::text($parameter, 'ownership', 'value');
        $abi = Package_Syntax::integer_abi($position->member('type'), $position->member('attributes'), $type);
        return new Call_Parameter_Position(new \type_model\Semantic_Parameter($reference, \type_model\PASS_VALUE), $abi, $offset + 1);
    }
}
