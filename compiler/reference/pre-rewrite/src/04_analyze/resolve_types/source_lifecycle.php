<?php
declare(strict_types=1);

/*
 * Role: Interpret source lifecycle declarations without owning their checked bodies.
 * Call map: Record_Preparation / Record_Join -> bodies(); Signature_Resolver / Signature_Join -> validate()
 * Member_Worker -> role() to reject explicit lifecycle invocation.
 * Output: stable declaration IDs; concrete receiver binding happens after record acceptance.
 */
namespace resolve_types;

final class Source_Lifecycle
{
    /** Lifecycle spelling is a source-language rule, independent of type names and runtime providers. */
    public static function role(\collect_symbols\symbol_record $symbol): ?\type_model\lifecycle_operation_kind
    {
        return (($symbol->owner_symbol_id === 0) || ($symbol->external !== null)) ? null : self::name_role($symbol->name);
    }

    /** Map reserved lifecycle spellings to distinct semantic operations. */
    private static function name_role(string $name): ?\type_model\lifecycle_operation_kind
    {
        return match ($name) {
            '__construct' => \type_model\lifecycle_operation_kind::default_construct,
            '__destruct' => \type_model\lifecycle_operation_kind::destroy,
            '__copy_assign' => \type_model\lifecycle_operation_kind::copy_assign,
            '__copy_construct' => \type_model\lifecycle_operation_kind::copy_construct,
            default => null,
        };
    }

    /** Bind custom bodies by allocated source symbol ID; no AST or body contents enter type contracts. */
    public static function bodies(record_task $task): array
    {
        $owner = $task->input;
        $tree = $owner->frontend->syntax;
        $bodies = [0, 0, 0, 0];
        $member_cursor = \parse\Syntax_Access::struct_members($tree, $owner->declaration_node_id, \parse\syntax_kind::method_declaration);
        while ($member_cursor->advance())
        {
            $member = $member_cursor->current();
            $parts = \parse\Syntax_Access::function_parts($tree, \parse\Syntax_Access::underlying_declaration($tree, $member));
            $name = \collect_symbols\Declaration_Syntax::name_text($owner->frontend, $parts->name_id);
            $role = self::name_role($name);
            if ($role === null) {
                continue;
            }
            $kind = $owner->is_template() ? \collect_symbols\symbol_kind::template_function : \collect_symbols\symbol_kind::function_symbol;
            $id = $task->symbols?->find_symbol($name, $owner->namespace_name, $kind, $owner->symbol_id) ?? 0;
            if ($id === 0) {
                throw new \LogicException('Lifecycle normalization requires its fixed member symbols');
            }
            $symbol = $task->symbols->symbol_by_id($id);
            $has_source = $role->has_source();
            $parameter = \parse\Syntax_Access::first_parameter($tree, $parts->parameters_id);
            if ($symbol->receiver_const || ($parts->body_id === 0)
                || ((!$has_source) && ($parameter !== 0))) {
                self::fail($symbol, 'Custom lifecycle requires a mutable receiver, a body and no explicit parameters except the copy source');
            }
            if (($has_source) && (($parameter === 0) || ($tree->nodes[$parameter - 1]->next_sibling_id !== 0)
                || (\parse\Syntax_Access::parameter_parts($tree, $parameter)->reference !== \parse\syntax_kind::const_reference_annotation))) {
                self::fail($symbol, 'Custom copy operation requires one const reference source');
            }
            $position = match ($role) {
                \type_model\lifecycle_operation_kind::default_construct => 0,
                \type_model\lifecycle_operation_kind::destroy => 1,
                \type_model\lifecycle_operation_kind::copy_construct => 2,
                \type_model\lifecycle_operation_kind::copy_assign => 3,
            };
            $bodies[$position] = $id;
        }
        return $bodies;
    }

    /** Validate receiver, result and the exact same-type const source of a custom copy body. */
    public static function validate(signature_request $request): void
    {
        $role = self::role($request->symbol);
        if ($role === null) {
            return;
        }
        $has_source = $role->has_source();
        $passing = [\type_model\argument_passing::borrow_mutable];
        if ($has_source) {
            $passing[] = \type_model\argument_passing::borrow_const;
        }
        if (($request->definition->representation->kind !== \type_model\representation_kind::void_type)
            || (count($request->parameter_definitions) !== count($passing))
            || ($request->parameter_passing !== $passing)
            || (($has_source) && (($request->parameter_definitions[1] ?? null) !== $request->instance?->receiver_type))
            || ($request->instance?->receiver_type !== $request->parameter_definitions[0])
            || ($request->symbol->body_node_id === 0)) {
            self::fail($request->symbol, 'Custom lifecycle body must return void and borrow its mutable receiver plus an exact const source for copying');
        }
    }

    private static function fail(\collect_symbols\symbol_record $symbol, string $message): never
    {
        $node = \collect_symbols\Declaration_Syntax::name_anchor($symbol);
        $source = $symbol->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
