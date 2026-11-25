<?php
declare(strict_types=1);

/*
 * Role: Interpret bound syntax in a concrete argument context, without execution.
 * Used by: application/record/signature/local workers; body checking
 * Call map: Bindings::type(); value() -> fixed declarations, arguments and literal constants
 */
namespace instantiate;

use parse\syntax_kind;
use resolve_symbols\reference_kind;
use type_model\named_type_definition;

final class Bindings
{
    /** Read a concrete annotation; null denotes a record or type-application prerequisite. */
    public static function type(instance_context $context, int $node, \resolve_symbols\Resolution_Set $names,
        \type_model\Type_Catalog|\resolve_types\Definition_View $definitions, Instance_View $instances): ?named_type_definition
    {
        $owner = $context->definition;
        if ($owner->frontend->syntax->nodes[$node - 1]->kind === syntax_kind::template_application) {
            return $instances->type_for($context, $node);
        }
        $binding = $names->for_symbol($owner->symbol_id)->name_for($node);
        if ($binding->kind === reference_kind::template_parameter) {
            $argument = $context->arguments[$binding->target] ?? null;
            if (($argument === null) || ($argument->value !== null)) {
                throw new \LogicException('Missing concrete type parameter');
            }
            return $argument->type;
        }
        return \resolve_types\Annotation_Types::bound_definition($owner, $node, $definitions, $names);
    }

    /** Integer literal/constant/parameter reading only; no expression or function execution. */
    public static function value(instance_context $context, int $node, \resolve_symbols\Resolution_Set $names,
        \type_model\Type_Catalog $catalog, Instance_View $instances): template_argument
    {
        $owner = $context->definition;
        $syntax = $owner->frontend->syntax->nodes[$node - 1];
        if ($syntax->kind === syntax_kind::integer_literal) {
            return self::literal($owner, $node, $catalog->integer_literal_type);
        }
        if ($syntax->kind === syntax_kind::name)
        {
            $binding = $names->for_symbol($owner->symbol_id)->name_for($node);
            $argument = match ($binding->kind) {
                reference_kind::template_parameter => $context->arguments[$binding->target] ?? null,
                reference_kind::project_constant => $instances->constant_for($binding->target),
                default => null,
            };
            if (($argument !== null) && ($argument->value !== null)) {
                return $argument;
            }
        }
        self::fail($owner, $node, 'Only integer literals, global integer constants and integer template parameters are supported; constant evaluation is not implemented');
    }

    /** Share exact decimal decoding and range rules with ordinary integer operands. */
    public static function literal(\collect_symbols\symbol_record $owner, int $node, named_type_definition $type): template_argument
    {
        $syntax = $owner->frontend->syntax->nodes[$node - 1];
        if ($syntax->kind !== syntax_kind::integer_literal) {
            self::fail($owner, $node, 'Constant initializer must be an integer literal; constant evaluation is not implemented');
        }
        $text = substr($owner->frontend->tokens->source->content, $syntax->start, $syntax->length);
        try {
            return new template_argument($type, \check_bodies\Integer_Literals::resolve($text, $type));
        }
        catch (\RangeException $error) {
            self::fail($owner, $node, $error->getMessage());
        }
    }

    public static function fail(\collect_symbols\symbol_record $owner, int $node, string $message): never
    {
        $syntax = $owner->frontend->syntax->nodes[$node - 1];
        $source = $owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $syntax->start, $syntax->length, $message);
    }
}
