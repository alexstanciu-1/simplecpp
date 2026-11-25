<?php
declare(strict_types=1);

/*
 * Role: ABI-free family contract invariants shared by native and compiler adapters.
 * Call map: Family_Contracts::validate() -> reference()
 *           [each operation] -> declared requirements and element-effect checks
 */
namespace type_model;

final class Family_Contracts
{
    /** Concrete substitution never expands the permissions promised by the formal declaration. */
    public static function validate(family_definition $family): void
    {
        if (($family->provider === '') || ($family->id === '') || ($family->parameters === [])
            || !array_is_list($family->parameters)) {
            throw new \RuntimeException('Invalid family identity or formal parameter list');
        }
        self::exposure($family->language_type);
        $names = [];
        foreach ($family->parameters as $parameter) {
            if ((!($parameter instanceof family_parameter)) || isset($names[$parameter->name])) {
                throw new \RuntimeException('Invalid or duplicate formal parameter');
            }
            $names[$parameter->name] = true;
        }
        foreach ($family->lifecycle as $operation) {
            if (!isset($family->operations[$operation])) {
                throw new \RuntimeException('Missing declared family lifecycle operation');
            }
        }
        $members = [];
        foreach ($family->operations as $id => $operation)
        {
            if ((!($operation instanceof family_operation)) || ($operation->id !== $id)) {
                throw new \RuntimeException('Invalid family operation identity');
            }
            self::exposure($operation->expose_as);
            if ($operation->expose_as !== null)
            {
                $name = $operation->expose_as->name;
                if (($family->language_type === null) || ($operation->receiver === null)
                    || ($operation->expose_as->namespace_name !== '') || isset($members[$name])
                    || in_array($id, $family->lifecycle, true)) {
                    throw new \RuntimeException('Invalid or duplicate family member exposure');
                }
                $members[$name] = true;
            }
            $parameters = $operation->signature->parameters;
            foreach ($parameters as $parameter) {
                self::reference($parameter->type, $family);
            }
            self::reference($operation->signature->result->type, $family);
            foreach ($operation->requirements as $requirement) {
                $contract = $family->parameters[$requirement->slot]->contract ?? null;
                if (($contract === null) || !$contract->permits($requirement->operation)) {
                    throw new \RuntimeException('Operation requirement exceeds declared generic permission');
                }
            }
            if ($operation->receiver !== null) {
                $receiver = $parameters[$operation->receiver] ?? null;
                if (($receiver === null) || (!($receiver->type instanceof family_type_reference)) || !$receiver->passing->is_borrow()) {
                    throw new \RuntimeException('Invalid semantic receiver');
                }
            }
            $effects = [];
            foreach ($operation->effects as $effect)
            {
                $receiver = $parameters[$effect->receiver] ?? null;
                if (($receiver === null) || (!($receiver->type instanceof family_type_reference))
                    || ($receiver->passing !== argument_passing::borrow_mutable)) {
                    throw new \RuntimeException('Invalid element-effect receiver');
                }
                if ($effect->kind === element_effect_kind::safe_element_input) {
                    $input = $parameters[$effect->safe_input ?? -1] ?? null;
                    if (($input === null) || ($input->passing !== argument_passing::borrow_const)
                        || (!($input->type instanceof parameter_type_reference))) {
                        throw new \RuntimeException('Invalid safe element-input relationship');
                    }
                }
                elseif ($effect->safe_input !== null) {
                    throw new \RuntimeException('Invalidation must not contain an input-overlap claim');
                }
                $key = json_encode([$effect->kind, $effect->receiver, $effect->safe_input], JSON_THROW_ON_ERROR);
                if (isset($effects[$key])) {
                    throw new \RuntimeException('Duplicate family effect');
                }
                $effects[$key] = true;
            }
        }
    }

    /** Validate source spelling separately from provider identity; members have an owner, not a namespace. */
    private static function exposure(?named_type_reference $name): void
    {
        if (($name !== null) && (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name->name)
            || (($name->namespace_name !== '') && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)*$/D', $name->namespace_name)))) {
            throw new \RuntimeException('Invalid family source exposure');
        }
    }

    /** Only catalog names, this definition's formals and its exact self application are supported. */
    private static function reference(type_reference $reference, family_definition $family): void
    {
        if ($reference instanceof provider_type_reference) {
            if (($reference->provider !== $family->provider) || ($reference->id === '')) {
                throw new \RuntimeException('Foreign or empty provider type reference');
            }
            return;
        }
        if ($reference instanceof parameter_type_reference) {
            if (($reference->owner !== $family->key()) || !isset($family->parameters[$reference->slot])) {
                throw new \RuntimeException('Foreign formal owner or slot');
            }
            return;
        }
        if (($reference instanceof family_type_reference) && ($reference->family === $family->key())
            && (count($reference->arguments) === count($family->parameters)) && array_is_list($reference->arguments))
        {
            foreach ($reference->arguments as $slot => $argument) {
                if ((!($argument instanceof parameter_type_reference)) || ($argument->slot !== $slot)) {
                    throw new \RuntimeException('Family self application has reordered or substituted slots');
                }
                self::reference($argument, $family);
            }
            return;
        }
        throw new \RuntimeException('Unsupported family declaration reference');
    }
}
