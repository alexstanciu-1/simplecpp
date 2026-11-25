<?php
declare(strict_types=1);

/*
 * Role: Select and resolve local type annotations.
 * Used by: Type_Resolver::run()
 * Call map:
 *   Local_Type_Resolver::resolve()
 *     -> Annotation_Types::definition()
 */

namespace resolve_types;

use type_model\Type_Store;
use type_model\representation_kind;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;
use type_model\Type_Catalog;
use parse\Syntax_Access;
use resolve_symbols\Resolution_Set;

/**
 * @compiler-api Body-local annotation process driven by compile; requests retain exact name results.
 * Workers resolve body-local annotations only; join takes parameter IDs from the
 * completed signature associations, then appends body-local IDs in binding order.
 */
class Local_Type_Resolver
{
    /**
     * @compiler-api Select participating owners with locals when full or stale name/type identities.
     * Requires fixed current name bindings; does not allocate shared type records.
     * @return list<symbol_record|\instantiate\instance_context> Fixed callable tasks, selected before worker execution.
     */
    public static function select(Symbol_Store $symbols, Resolution_Set $names, Type_Store $types,
        ?Type_Resolution $previous, bool $full_rebuild, entry_contract $entry, ?\instantiate\Instance_Set $instances = null): array
    {
        $tasks = [];
        foreach (Callable_Inputs::all($symbols, $instances) as $input)
        {
            $owner = Callable_Inputs::owner($input);
            $instance = Callable_Inputs::instance($input);
            if (!Signature_Resolver::body_participates($owner, $entry, $instance)) {
                continue;
            }
            $bindings = Local_Type_Validity::names_for($owner, $names);
            if ($bindings->locals === []) {
                continue;
            }
            if (($full_rebuild) || (!Local_Type_Validity::is_current($previous, $types, $bindings, $instance))) {
                $tasks[] = $input;
            }
        }
        return $tasks;
    }

    /**
     * @compiler-api Read each body-local annotation and return ordered shared definition requests.
     * Void/unknown types throw Source_Error; no shared writes or type ID allocation.
     */
    public static function resolve(Symbol_Store $symbols, Resolution_Set $names,
        Type_Catalog|Definition_View $catalog, symbol_record|\instantiate\instance_context $input,
        ?\instantiate\Instance_Set $instances = null): local_type_request
    {
        $owner = Callable_Inputs::owner($input);
        $instance = Callable_Inputs::instance($input);
        if (!Callable_Inputs::is_current($input, $symbols, $instances)) {
            throw new \LogicException('Stale local type task');
        }
        $bindings = Local_Type_Validity::names_for($owner, $names);
        $definitions = [];
        for ($row = $bindings->parameter_count; $row < count($bindings->locals); ++$row)
        {
            $local = $bindings->locals[$row];
            $annotation = Syntax_Access::local_declaration_parts($bindings->syntax, $local->declaration_node_id)->type_syntax_id;
            $definition = Annotation_Types::definition($owner, $annotation, $catalog, 'local', $names, $instance, $instances);
            if ($definition->representation->kind === representation_kind::void_type) {
                $node = $bindings->syntax->nodes[$annotation - 1];
                $source = $owner->frontend->tokens->source;
                throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length,
                    'A local requires a value type; void has no value');
            }
            $definitions[] = $definition;
        }
        return new local_type_request($owner, $bindings, $definitions, $instance);
    }
}
