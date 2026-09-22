<?php
declare(strict_types=1);

/*
 * Role: Follow accepted annotation bindings into prepared concrete definitions.
 * Used by: record, signature and local workers/joins; Type_Resolution
 * Call map:
 *   Annotation_Types::definition()
 *     -> bound_definition(); [action] enforce source representation/passing rules
 */

namespace resolve_types;

use type_model\representation_kind;

/** @compiler-internal Shared bound-annotation consumption; spelling lookup belongs to resolve_symbols. */
class Annotation_Types
{
    /** Resolve a source annotation and reject internal operand shapes that have no source storage contract. */
    public static function definition(\collect_symbols\symbol_record $owner, int $node_id,
        \type_model\Type_Catalog|Definition_View $catalog, string $role, \resolve_symbols\Resolution_Set $names,
        ?\instantiate\instance_context $instance = null, ?\instantiate\Instance_View $instances = null): \type_model\named_type_definition
    {
        $node = $owner->frontend->syntax->nodes[$node_id - 1] ?? null;
        $source = $owner->frontend->tokens->source;
        $definition = $instances === null
            ? self::bound_definition($owner, $node_id, $catalog, $names)
            : \instantiate\Bindings::type($instance ?? new \instantiate\instance_context($owner), $node_id, $names, $catalog, $instances);
        if ($definition === null) {
            throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length,
                "Bound $role type requires unsupported concrete preparation");
        }
        if ($definition->representation->kind === representation_kind::byte_span) {
            throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length,
                'Byte spans are only supported as literal call operands');
        }
        return $definition;
    }

    /** Follow an accepted declaration binding; concrete preparation never repeats source spelling lookup. */
    public static function bound_definition(\collect_symbols\symbol_record $owner, int $node_id,
        \type_model\Type_Catalog|Definition_View $catalog, \resolve_symbols\Resolution_Set $names): ?\type_model\named_type_definition
    {
        $bindings = $names->for_symbol($owner->symbol_id);
        if ($bindings?->syntax !== $owner->frontend->syntax) {
            throw new \LogicException('Missing or stale annotation bindings');
        }
        if ($owner->frontend->syntax->nodes[$node_id - 1]->kind === \parse\syntax_kind::template_application) {
            \instantiate\Bindings::fail($owner, $node_id, 'Template annotation requires instance preparation');
        }
        $binding = $bindings->name_for($node_id);
        if ($binding->role !== \resolve_symbols\name_role::type) {
            throw new \LogicException('Expected bound type annotation');
        }
        if ($binding->kind === \resolve_symbols\reference_kind::source_type) {
            $target = $names->declaration_for($binding->target);
        }
        elseif (in_array($binding->kind, [\resolve_symbols\reference_kind::provided_type,
            \resolve_symbols\reference_kind::provided_record], true)) {
            $target = $binding->target;
        }
        else {
            throw new \LogicException('Dependent annotation reached concrete preparation');
        }
        $definition = $catalog->find_type($target->name, $target->namespace_name);
        if (($binding->kind === \resolve_symbols\reference_kind::provided_type) && ($definition !== $target)) {
            throw new \LogicException('Stale provided type binding');
        }
        return $definition;
    }
}
