<?php
declare(strict_types=1);
namespace type_model;

/** Structural equality of immutable declaration/callable contracts, independent of allocation identity.
 * Derived ABI slot indices follow from the compared parameter shapes and result transport. */
final class Callable_Contracts {
    public static function reference(Type_Reference $left, Type_Reference $right): bool {
        if ($left === $right) { return true; }
        if ($left->kind !== $right->kind) { return false; }
        if ($left->kind === \type_model\TYPE_REFERENCE_NAMED) { return ($left->name() === $right->name()) && ($left->namespace_name() === $right->namespace_name()); }
        if ($left->kind === \type_model\TYPE_REFERENCE_PROVIDER) { return ($left->provider() === $right->provider()) && ($left->id() === $right->id()); }
        if ($left->kind === \type_model\TYPE_REFERENCE_PARAMETER) { return ($left->owner() === $right->owner()) && ($left->parameter_slot() === $right->parameter_slot()); }
        if (($left->family_key() !== $right->family_key()) || ($left->argument_count() !== $right->argument_count())) { return false; }
        for ($index = 0; $index < $left->argument_count(); $index++) {
            if (!Callable_Contracts::reference($left->argument_at($index),$right->argument_at($index))) { return false; }
        }
        return true;
    }
    public static function signature(Semantic_Signature $left, Semantic_Signature $right): bool {
        if ($left === $right) { return true; }
        if (($left->parameter_count() !== $right->parameter_count()) || ($left->result->production !== $right->result->production)) { return false; }
        if (!Callable_Contracts::reference($left->result->type,$right->result->type)) { return false; }
        for ($index = 0; $index < $left->parameter_count(); $index++) {
            $a = $left->parameter_at($index); $b = $right->parameter_at($index);
            if ($a->passing !== $b->passing) { return false; }
            if (!Callable_Contracts::reference($a->type,$b->type)) { return false; }
        }
        $a_effect = $left->allocation_effect; $b_effect = $right->allocation_effect;
        if ($a_effect === null) { return $b_effect === null; }
        if ($b_effect === null) { return false; }
        return ($a_effect->kind === $b_effect->kind) && ($a_effect->owner === $b_effect->owner) && ($a_effect->destination === $b_effect->destination);
    }
    public static function position(Runtime_Abi_Position $left, Runtime_Abi_Position $right): bool {
        if ($left === $right) { return true; }
        if (($left->kind !== $right->kind) || ($left->bits !== $right->bits) || ($left->extension !== $right->extension) || ($left->mutable !== $right->mutable)) { return false; }
        $a = $left->length; $b = $right->length;
        if ($a === null) { return $b === null; }
        if ($b === null) { return false; }
        return Callable_Contracts::position($a,$b);
    }
    public static function abi(Runtime_Callable_Abi $left, Runtime_Callable_Abi $right): bool {
        if ($left === $right) { return true; }
        if (($left->link_name !== $right->link_name) || ($left->calling_convention !== $right->calling_convention)
            || ($left->result_passing !== $right->result_passing) || ($left->parameter_count() !== $right->parameter_count())) { return false; }
        $a = $left->result; $b = $right->result;
        if ($a === null) { if ($b !== null) { return false; } }
        else {
            if ($b === null) { return false; }
            if (!Callable_Contracts::position($a,$b)) { return false; }
        }
        for ($index = 0; $index < $left->parameter_count(); $index++) {
            if (!Callable_Contracts::position($left->parameter_at($index),$right->parameter_at($index))) { return false; }
        }
        return true;
    }
    public static function same(Runtime_Callable $left, Runtime_Callable $right): bool {
        if ($left === $right) { return true; }
        if (($left->provider !== $right->provider) || ($left->id !== $right->id) || ($left->name !== $right->name)
            || ($left->namespace_name !== $right->namespace_name) || ($left->language_binding !== $right->language_binding)
            || ($left->default_literal !== $right->default_literal) || ($left->conversion_purpose !== $right->conversion_purpose)) { return false; }
        if (!Callable_Contracts::signature($left->signature,$right->signature)) { return false; }
        return Callable_Contracts::abi($left->abi,$right->abi);
    }
}
