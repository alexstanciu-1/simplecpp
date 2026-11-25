<?php
declare(strict_types=1);
namespace type_model;

/** Validate declarations independently of native bindings or favorable specializations. */
final class Family_Contracts {
    public static function validate(Family_Definition $family): void {
        if (($family->provider === '') || ($family->id === '') || ($family->parameter_count() === 0)) {
            throw new \RuntimeException('Invalid family identity or formal parameter list');
        }
        $language = $family->language_type;
        if ($language !== null) { Family_Contracts::exposure($language); }
        $names /** hash<bool> */ = [];
        for ($slot = 0; $slot < $family->parameter_count(); $slot++) {
            $parameter = $family->parameter_at($slot); $name = $parameter->name;
            if (isset($names[$name])) { throw new \RuntimeException('Invalid or duplicate formal parameter'); }
            $names[$name] = true;
        }
        $lifecycle = $family->lifecycle_bindings();
        $lifecycle_ids /** hash<bool> */ = [];
        foreach ($lifecycle as $role => $operation_id) {
            if ($family->find_operation($operation_id) === null) { throw new \RuntimeException('Missing declared family lifecycle operation'); }
            $lifecycle_ids[$operation_id] = true;
        }
        $members /** hash<bool> */ = [];
        for ($index = 0; $index < $family->operation_count(); $index++) {
            $operation = $family->operation_at($index); $exposure = $operation->expose_as;
            if ($exposure !== null) {
                Family_Contracts::exposure($exposure); $name = $exposure->name(); $id = $operation->id;
                if (($language === null) || ($operation->receiver === null) || ($exposure->namespace_name() !== '')) {
                    throw new \RuntimeException('Invalid or duplicate family member exposure');
                }
                if (isset($members[$name]) || isset($lifecycle_ids[$id])) { throw new \RuntimeException('Invalid or duplicate family member exposure'); }
                $members[$name] = true;
            }
            $signature = $operation->signature;
            for ($position = 0; $position < $signature->parameter_count(); $position++) {
                $parameter = $signature->parameter_at($position);
                Family_Contracts::reference($parameter->type, $family);
            }
            Family_Contracts::reference($signature->result->type, $family);
            for ($position = 0; $position < $operation->requirement_count(); $position++) {
                $requirement = $operation->requirement_at($position);
                if (($requirement->slot < 0) || ($requirement->slot >= $family->parameter_count())) { throw new \RuntimeException('Operation requirement exceeds declared generic permission'); }
                $formal = $family->parameter_at($requirement->slot);
                if (!Generic_Contracts::permits($formal->contract, $requirement->operation)) { throw new \RuntimeException('Operation requirement exceeds declared generic permission'); }
            }
            $receiver_index = $operation->receiver;
            if ($receiver_index !== null) {
                Family_Contracts::require_position($signature, $receiver_index, 'Invalid semantic receiver');
                $receiver = $signature->parameter_at($receiver_index);
                if (($receiver->type->kind !== \type_model\TYPE_REFERENCE_FAMILY) || (!Semantic_Modes::is_borrow($receiver->passing))) { throw new \RuntimeException('Invalid semantic receiver'); }
            }
            $effects /** hash<bool> */ = [];
            for ($position = 0; $position < $operation->effect_count(); $position++) {
                $effect = $operation->effect_at($position);
                Family_Contracts::require_position($signature, $effect->receiver, 'Invalid element-effect receiver');
                $receiver = $signature->parameter_at($effect->receiver);
                if (($receiver->type->kind !== \type_model\TYPE_REFERENCE_FAMILY) || ($receiver->passing !== \type_model\PASS_BORROW_MUTABLE)) { throw new \RuntimeException('Invalid element-effect receiver'); }
                $safe = $effect->safe_input; $safe_key = 'null';
                if ($effect->kind === \type_model\ELEMENT_SAFE_INPUT) {
                    if ($safe === null) { throw new \RuntimeException('Invalid safe element-input relationship'); }
                    Family_Contracts::require_position($signature, $safe, 'Invalid safe element-input relationship');
                    $input = $signature->parameter_at($safe);
                    if (($input->passing !== \type_model\PASS_BORROW_CONST) || ($input->type->kind !== \type_model\TYPE_REFERENCE_PARAMETER)) { throw new \RuntimeException('Invalid safe element-input relationship'); }
                    $safe_key = '' . $safe;
                } elseif ($safe !== null) { throw new \RuntimeException('Invalidation must not contain an input-overlap claim'); }
                $key = '' . $effect->kind . ':' . $effect->receiver . ':' . $safe_key;
                if (isset($effects[$key])) { throw new \RuntimeException('Duplicate family effect'); }
                $effects[$key] = true;
            }
        }
    }
    private static function require_position(Semantic_Signature $signature, int $index, string $reason): void {
        if (($index < 0) || ($index >= $signature->parameter_count())) { throw new \RuntimeException($reason); }
    }
    private static function identifier(string $name): bool {
        if (string_byte_len($name) === 0) { return false; }
        for ($index = 0; $index < string_byte_len($name); $index++) {
            $byte = string_byte_at($name, $index);
            $letter = (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123)) || ($byte === 95);
            if (!$letter) {
                if (($index === 0) || ($byte < 48) || ($byte > 57)) { return false; }
            }
        }
        return true;
    }
    private static function exposure(Type_Reference $name): void {
        if (!Family_Contracts::identifier($name->name())) { throw new \RuntimeException('Invalid family source exposure'); }
        $namespace_text = $name->namespace_name();
        if ($namespace_text === '') { return; }
        $start = 0;
        for ($index = 0; $index < string_byte_len($namespace_text); $index++) {
            if (string_byte_at($namespace_text, $index) === 58) {
                if (string_byte_at($namespace_text, $index + 1) !== 58) { throw new \RuntimeException('Invalid family source exposure'); }
                if (!Family_Contracts::identifier(string_byte_slice($namespace_text, $start, $index - $start))) { throw new \RuntimeException('Invalid family source exposure'); }
                $index = $index + 1; $start = $index + 1;
            }
        }
        if (!Family_Contracts::identifier(string_byte_slice($namespace_text, $start, string_byte_len($namespace_text) - $start))) { throw new \RuntimeException('Invalid family source exposure'); }
    }
    /** Only provider primitives, this owner's formals, and its exact self application. */
    private static function reference(Type_Reference $reference, Family_Definition $family): void {
        if ($reference->kind === \type_model\TYPE_REFERENCE_PROVIDER) {
            if (($reference->provider() !== $family->provider) || ($reference->id() === '')) { throw new \RuntimeException('Foreign or empty provider type reference'); }
            return;
        }
        if ($reference->kind === \type_model\TYPE_REFERENCE_PARAMETER) {
            if (($reference->owner() !== $family->key()) || ($reference->parameter_slot() >= $family->parameter_count())) { throw new \RuntimeException('Foreign formal owner or slot'); }
            return;
        }
        if ($reference->kind === \type_model\TYPE_REFERENCE_FAMILY) {
            if (($reference->family_key() !== $family->key()) || ($reference->argument_count() !== $family->parameter_count())) { throw new \RuntimeException('Unsupported family declaration reference'); }
            for ($slot = 0; $slot < $reference->argument_count(); $slot++) {
                $argument = $reference->argument_at($slot);
                if ($argument->kind !== \type_model\TYPE_REFERENCE_PARAMETER) { throw new \RuntimeException('Family self application has reordered or substituted slots'); }
                if ($argument->parameter_slot() !== $slot) { throw new \RuntimeException('Family self application has reordered or substituted slots'); }
                Family_Contracts::reference($argument, $family);
            }
            return;
        }
        throw new \RuntimeException('Unsupported family declaration reference');
    }
}
