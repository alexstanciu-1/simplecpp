<?php
declare(strict_types=1);

/*
 * Role: Concrete callable task identity and original declaration provenance.
 * Used by: signature/local selection, workers and joins
 * Call map: Callable_Inputs::all(); owner(); instance(); id(); external() [fixed prepared method associations]
 */
namespace resolve_types;

final class Callable_Inputs
{
    public static function all(\collect_symbols\Symbol_Store $symbols, ?\instantiate\Instance_Set $instances): array
    {
        return [...$symbols->records(), ...($instances?->functions() ?? [])];
    }

    /** Require exact membership in the fixed declaration and concrete-instance snapshots. */
    public static function is_current(\collect_symbols\symbol_record|\instantiate\instance_context $input,
        \collect_symbols\Symbol_Store $symbols, ?\instantiate\Instance_Set $instances): bool
    {
        $owner = self::owner($input);
        return $symbols->contains($owner->symbol_id) && ($symbols->symbol_by_id($owner->symbol_id) === $owner)
            && (!($input instanceof \instantiate\instance_context)
                || (($instances?->contexts[$input->context_id] ?? null) === $input));
    }

    /** Resolve implementation provenance without changing the semantic declaration or creating an AST. */
    public static function external(\collect_symbols\symbol_record $symbol, ?\instantiate\instance_context $instance,
        array $prepared = []): ?\type_model\runtime_callable
    {
        if ($symbol->external instanceof \type_model\family_method) {
            return $prepared[$instance?->context_id ?? 0]
                ?? throw new \LogicException('Missing prepared family method callable');
        }
        return $symbol->external instanceof \type_model\runtime_callable ? $symbol->external : null;
    }

    public static function owner(\collect_symbols\symbol_record|\instantiate\instance_context $input): \collect_symbols\symbol_record
    {
        return $input instanceof \instantiate\instance_context ? $input->definition : $input;
    }

    public static function instance(\collect_symbols\symbol_record|\instantiate\instance_context $input): ?\instantiate\instance_context
    {
        return $input instanceof \instantiate\instance_context ? $input : null;
    }

    public static function id(\collect_symbols\symbol_record|\instantiate\instance_context $input): int
    {
        return $input instanceof \instantiate\instance_context ? $input->context_id : $input->symbol_id;
    }
}
