<?php
declare(strict_types=1);

/*
 * Role: Interpret source parameter annotations as semantic passing contracts.
 * Used by: Signature_Resolver and Signature_Join
 * Call map: Parameter_Contracts::passing() -> Syntax_Access::parameter_parts()
 *   Parameter_Contracts::validate() -> fail() -> diagnostics\Source_Error
 */

namespace resolve_types;

class Parameter_Contracts
{
    /** Preserve the distinction between a copied value and call-scoped access to existing storage. */
    public static function passing(\parse\Syntax_Tree $tree, int $id): \type_model\argument_passing
    {
        return match (\parse\Syntax_Access::parameter_parts($tree, $id)->reference) {
            null => \type_model\argument_passing::value,
            \parse\syntax_kind::reference_annotation => \type_model\argument_passing::borrow_mutable,
            \parse\syntax_kind::const_reference_annotation => \type_model\argument_passing::borrow_const,
            default => throw new \LogicException('Invalid reference parameter annotation'),
        };
    }

    /** Validate source value/reference boundaries in both the private worker and its accepting join. */
    public static function validate(\collect_symbols\symbol_record $owner, int $node,
        \type_model\argument_passing $passing, \type_model\named_type_definition $definition): void
    {
        $shape = $definition->representation->kind;
        if ($passing->is_borrow())
        {
            if (!in_array($shape, [\type_model\representation_kind::structure, \type_model\representation_kind::opaque_inline], true)
                || (($passing !== \type_model\argument_passing::borrow_const)
                    && (($definition->lifetime?->copy !== \type_model\copy_kind::value)
                        || ($definition->lifetime?->cleanup !== \type_model\cleanup_kind::none)))) {
                self::fail($owner, $node, 'Source reference parameters require a plain record type or a const record borrow');
            }
            return;
        }

        // Borrow support does not authorize copying aggregate values across source call boundaries.
        $message = match ($shape) {
            \type_model\representation_kind::void_type => 'A parameter requires a value type; void has no value',
            \type_model\representation_kind::structure => 'Struct function parameters by value are unsupported',
            \type_model\representation_kind::opaque_inline => 'Unsupported inline object parameter in a source function; value argument passing is not implemented',
            default => null,
        };
        if ($message !== null) {
            self::fail($owner, $node, $message);
        }
    }

    /** Anchor parameter diagnostics in the current declaration without depending on template processing. */
    private static function fail(\collect_symbols\symbol_record $owner, int $node_id, string $message): never
    {
        $node = $owner->frontend->syntax->nodes[$node_id - 1];
        $source = $owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
