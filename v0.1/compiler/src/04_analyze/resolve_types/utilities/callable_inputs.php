<?php
declare(strict_types=1);
namespace resolve_types;
final class Callable_Inputs {
    public static function all(\collect_symbols\Symbol_Store $symbols, \instantiate\Instance_View $instances): array /** vector<Callable_Input> */ {
        $out /** vector<Callable_Input> */ = [];
        for ($i = 0; $i < $symbols->size(); $i++) { $out[] = new Callable_Input($symbols->record_at($i)); }
        foreach ($instances->contexts() as $context) {
            if (($context->definition->kind() === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION) || ($context->definition->owner_symbol_id !== 0)) {
                $out[] = new Callable_Input($context->definition,$context);
            }
        }
        return $out;
    }
    public static function is_current(Callable_Input $input, \collect_symbols\Symbol_Store $symbols, \instantiate\Instance_View $instances): bool {
        if (!$symbols->contains($input->owner->symbol_id)) { return false; }
        if ($symbols->symbol_by_id($input->owner->symbol_id) !== $input->owner) { return false; }
        if ($input->instance !== null) { return $instances->context_for($input->callable_id) === $input->instance; }
        return true;
    }
    /** Prepared family methods are accepted associations supplied by their preparation owner. */
    public static function external(Callable_Input $input, array $prepared /** hash<\type_model\Runtime_Callable,int> */): ?\type_model\Runtime_Callable {
        $owner = $input->owner; if ($owner->is_source()) { return null; }
        $provider = $owner->provider();
        if ($provider->kind() === \collect_symbols\PROVIDER_METHOD) {
            if ($input->instance === null) { throw new \LogicException('Missing concrete family method'); }
            if (!isset($prepared[$input->callable_id])) { throw new \LogicException('Missing prepared family method callable'); }
            return $prepared[$input->callable_id];
        }
        if ($provider->kind() === \collect_symbols\PROVIDER_CALLABLE) { return $provider->callable(); }
        return null;
    }
}
