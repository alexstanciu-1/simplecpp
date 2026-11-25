<?php
declare(strict_types=1);

/*
 * Role: Select and resolve callable type annotations.
 * Used by: Type_Resolver::run()
 * Call map:
 *   Signature_Resolver::resolve()
 *     -> Callable_Inputs::external(); Annotation_Types::definition() / referenced_definition() [source/imported]
 */

namespace resolve_types;

use type_model\Type_Store;

use parse\Syntax_Access;
use collect_symbols\Symbol_Store;
use collect_symbols\symbol_kind;
use collect_symbols\symbol_record;
use type_model\Type_Catalog;

/**
 * @compiler-api Declared-callable type process driven by compile. Workers read fixed symbol/catalog
 * inputs and request definitions; only join writes the private type-store candidate.
 * Return and ordered parameter annotations form one signature; the selected entry also participates.
 */
class Signature_Resolver
{
    /**
     * @compiler-api Select participating concrete callables on full or stale syntax/signature/type-record identity.
     * No materialization; reuse depends on shared records, not equal numeric IDs alone.
     * @return list<symbol_record|\instantiate\instance_context>
     */
    public static function select(Symbol_Store $symbols, ?Type_Resolution $previous, Type_Store $types, bool $full_rebuild, entry_contract $entry, ?\instantiate\Instance_Set $instances = null, array $prepared = []): array
    {
        $tasks = [];
        foreach (Callable_Inputs::all($symbols, $instances) as $input)
        {
            $symbol = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            if (!self::participates($symbol, $entry, $instance)) {
                continue;
            }
            if (($full_rebuild) || (!Signature_Validity::is_current($previous, $types, $symbol, $instance, $prepared))) {
                $tasks[] = $input;
            }
        }
        return $tasks;
    }

    /**
     * @compiler-internal Shared type-stage participation rule: functions and the exact selected file entry.
     * Other phases consume the completed signatures instead of duplicating this rule.
     */
    public static function participates(symbol_record $symbol, entry_contract $entry, ?\instantiate\instance_context $instance = null): bool
    {
        return match ($symbol->kind) {
            symbol_kind::function_symbol => ($symbol->owner_symbol_id === 0) || ($instance?->definition === $symbol),
            symbol_kind::template_function => $instance?->definition === $symbol,
            symbol_kind::struct_symbol, symbol_kind::template_struct, symbol_kind::constant_symbol => false,
            symbol_kind::file_entry => $symbol === $entry->symbol,
            default => throw new \LogicException('Unsupported callable signature owner'),
        };
    }

    /** @compiler-internal Local analysis requires a source body in addition to a callable signature. */
    public static function body_participates(symbol_record $symbol, entry_contract $entry, ?\instantiate\instance_context $instance = null): bool
    {
        return ($symbol->body_node_id !== 0) && self::participates($symbol, $entry, $instance);
    }

    /**
     * @compiler-api Read one current declaration and return its authoritative return and parameter definition request.
     * Unknown annotations or void parameters throw Source_Error; no shared writes.
     */
    public static function resolve(Symbol_Store $symbols, Type_Catalog|Definition_View $catalog, symbol_record|\instantiate\instance_context $input, entry_contract $entry, \resolve_symbols\Resolution_Set $names,
        ?\instantiate\Instance_Set $instances = null, array $prepared = []): signature_request
    {
        $symbol = Callable_Inputs::owner($input);
        $instance = Callable_Inputs::instance($input);
        if (!Callable_Inputs::is_current($input, $symbols, $instances)
            || (!self::participates($symbol, $entry, $instance))) {
            throw new \LogicException('Stale signature task');
        }
        if ($symbol === $entry->symbol) {
            return new signature_request($symbol, 0, $entry->return_type);
        }
        if ($symbol->external instanceof \type_model\storage_function) {
            return Storage_Definitions::signature($instance, $catalog);
        }
        $external = Callable_Inputs::external($symbol, $instance, $prepared);
        if ($external !== null) {
            $parameters = array_map(static fn($reference) => self::referenced_definition($catalog, $reference->type), $external->signature->parameters);
            return new signature_request($symbol, 0, self::referenced_definition($catalog, $external->signature->result->type), $parameters, $instance,
                array_map($external->passing_for(...), array_keys($parameters)));
        }
        if (($instance !== null) && ($symbol->owner_symbol_id === 0)) {
            $wrapped = Syntax_Access::template_parts($symbol->frontend->syntax, $symbol->declaration_node_id)->declaration_id;
            if (in_array($symbol->frontend->syntax->nodes[$wrapped - 1]->kind,
                [\parse\syntax_kind::constexpr_declaration, \parse\syntax_kind::consteval_declaration], true)) {
                \instantiate\Bindings::fail($symbol, $wrapped, 'Compile-time function execution is not implemented');
            }
        }
        $parts = Syntax_Access::function_parts($symbol->frontend->syntax, Syntax_Access::underlying_declaration($symbol->frontend->syntax, $symbol->declaration_node_id));
        $annotation = $parts->return_type_id;
        $definition = Annotation_Types::definition($symbol, $annotation, $catalog, 'return', $names, $instance, $instances);
        $parameters = $instance?->receiver_type === null ? [] : [$instance->receiver_type];
        $passing_modes = $parameters === [] ? [] : [$symbol->receiver_const
            ? \type_model\argument_passing::borrow_const : \type_model\argument_passing::borrow_mutable];
        $tree = $symbol->frontend->syntax;
        for ($id = Syntax_Access::first_parameter($tree, $parts->parameters_id); $id !== 0; $id = $tree->nodes[$id - 1]->next_sibling_id)
        {
            $type_node = Syntax_Access::parameter_parts($tree, $id)->type_syntax_id;
            $passing = Parameter_Contracts::passing($tree, $id);
            $parameter = Annotation_Types::definition($symbol, $type_node, $catalog,
                'parameter', $names, $instance, $instances);
            Parameter_Contracts::validate($symbol, $type_node, $passing, $parameter);
            $passing_modes[] = $passing;
            $parameters[] = $parameter;
        }
        $request = new signature_request($symbol, $annotation, $definition, $parameters, $instance, $passing_modes);
        Source_Lifecycle::validate($request);
        return $request;
    }

    /** Resolve imported names only after record acceptance has fixed the shared definition view. */
    private static function referenced_definition(Type_Catalog|Definition_View $catalog,
        \type_model\named_type_reference $reference): \type_model\named_type_definition
    {
        return $catalog->find_type($reference->name, $reference->namespace_name)
            ?? throw new \LogicException('Unresolved provider signature type: ' . $reference->name);
    }
}
