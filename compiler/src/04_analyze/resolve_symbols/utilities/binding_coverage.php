<?php
declare(strict_types=1);

/*
 * Role: Validate structural coverage and lexical ownership of new binding results.
 * Used by: Resolution_Join::join()
 * Call map: Binding_Coverage::complete() -> [action] traverse syntax roles without global lookup
 */
namespace resolve_symbols;

use parse\syntax_kind;
use collect_symbols\symbol_record;

/** Retained results need dependency checks; only new worker outputs require this structural acceptance walk. */
final class Binding_Coverage
{
    /** Require every free name, application and local declaration exactly once in its actual lexical scope. */
    public static function complete(Symbol_Resolution $result, symbol_record $owner): bool
    {
        // Index worker claims; the walk consumes each occurrence exactly once.
        $bound = [];
        foreach ([...$result->bindings, ...$result->name_bindings] as $binding) {
            if (isset($bound[$binding->use_node_id])) {
                return false;
            }
            $bound[$binding->use_node_id] = $binding;
        }
        $applications = [];
        foreach ($result->applications as $application) {
            if (isset($applications[$application->use_node_id])) {
                return false;
            }
            $applications[$application->use_node_id] = $application;
        }
        $members = [];
        foreach ($result->members as $member) {
            $members[$member->use_node_id] = $member;
        }
        $declarations = [];
        foreach ([...$result->locals, ...$result->constants] as $declaration) {
            if (isset($declarations[$declaration->declaration_node_id])) {
                return false;
            }
            $declarations[$declaration->declaration_node_id] = $declaration->scope_id;
        }
        $scopes = [];
        foreach ($result->scopes as $position => $scope) {
            $scopes[$scope->block_node_id] = $position + 1;
        }

        // Follow syntax iteratively, carrying scope and declaration-order visibility.
        $tree = $result->syntax;
        $root = $owner->declaration_node_id ?: $owner->body_node_id;
        $pending = [[$root, false, 0]];
        if ($owner->owner_symbol_id !== 0) {
            unset($declarations[$owner->declaration_node_id]);
            if ($owner->template_parameters_node_id !== 0) {
                $pending[] = [$owner->template_parameters_node_id, false, 0];
            }
        }
        $constants = [];
        while ($pending !== [])
        {
            [$id, $siblings, $scope] = array_pop($pending);
            if ($id === 0) {
                continue;
            }
            $node = $tree->nodes[$id - 1];
            if ($siblings) {
                $pending[] = [$node->next_sibling_id, true, $scope];
            }
            if ($node->kind === syntax_kind::name)
            {
                $binding = $bound[$id] ?? null;
                if (($binding === null) || !self::constant_use($result, $constants, $binding, $scope, $owner)) {
                    return false;
                }
                unset($bound[$id]);
                continue;
            }
            if ($node->kind === syntax_kind::template_application)
            {
                $target = $bound[$node->first_child_id] ?? null;
                $target_id = $target instanceof symbol_binding ? $target->target_symbol_id : $target?->target;
                if (!isset($applications[$id]) || ($target_id !== $applications[$id]->definition->symbol_id)) {
                    return false;
                }
                unset($applications[$id]);
            }
            if (($node->kind === syntax_kind::call_expression)
                && ($tree->nodes[$node->first_child_id - 1]->kind === syntax_kind::field_expression)) {
                if (!isset($members[$node->first_child_id])) {
                    return false;
                }
                unset($members[$node->first_child_id]);
            }
            if ($node->kind === syntax_kind::block)
            {
                $nested = $scopes[$id] ?? 0;
                if (($nested === 0) || ($result->scopes[$nested - 1]->parent_scope_id !== $scope)) {
                    return false;
                }
                unset($scopes[$id]);
                $scope = $nested;
            }
            if (in_array($node->kind, [syntax_kind::local_declaration, syntax_kind::parameter_declaration,
                syntax_kind::constant_declaration], true) && ($id !== $owner->declaration_node_id))
            {
                $expected_scope = $node->kind === syntax_kind::parameter_declaration ? 1 : $scope;
                if (($declarations[$id] ?? 0) !== $expected_scope) {
                    return false;
                }
                unset($declarations[$id]);
                if ($node->kind === syntax_kind::constant_declaration) {
                    $constants[$scope][self::text($owner, $node->first_child_id)] = $id;
                }
            }
            // Declaration names and member names are not free-name occurrences.
            $child = $node->first_child_id;
            $follow = true;
            switch ($node->kind)
            {
                case syntax_kind::method_declaration:
                    $child = $id === $owner->declaration_node_id ? $node->first_child_id : 0;
                    $follow = false;
                    break;
                case syntax_kind::type_parameter_declaration:
                    $child = 0;
                    break;
                case syntax_kind::field_declaration:
                    $extent = \parse\Syntax_Access::field_declaration_parts($tree, $id)->extent_id;
                    if ($extent !== 0) {
                        $pending[] = [$extent, false, $scope];
                    }
                    $follow = false;
                    break;
                case syntax_kind::value_parameter_declaration:
                case syntax_kind::field_expression:
                    $follow = false;
                    break;
                case syntax_kind::function_declaration:
                case syntax_kind::struct_declaration:
                case syntax_kind::constant_declaration:
                    $child = $tree->nodes[$child - 1]->next_sibling_id;
                    break;
            }
            if ($child !== 0) {
                $pending[] = [$child, $follow, $scope];
            }
        }
        return ($bound === []) && ($applications === []) && ($members === []) && ($declarations === []) && ($scopes === []);
    }

    /** Check nearest preceding constant visibility; this needs no type, value or project-name lookup. */
    private static function constant_use(Symbol_Resolution $result, array $constants, symbol_binding|name_binding $binding,
        int $scope, symbol_record $owner): bool
    {
        $name = self::text($owner, $binding->use_node_id);
        $target = 0;
        for ($id = $scope; $id !== 0; $id = $result->scopes[$id - 1]->parent_scope_id) {
            $target = $constants[$id][$name] ?? 0;
            if ($target !== 0) {
                break;
            }
        }
        if ($target === 0) {
            return !($binding instanceof name_binding) || ($binding->kind !== reference_kind::local_constant);
        }
        return ($binding instanceof name_binding) && ($binding->kind === reference_kind::local_constant)
            && ($binding->target === $target);
    }

    private static function text(symbol_record $owner, int $id): string
    {
        $node = $owner->frontend->syntax->nodes[$id - 1];
        return substr($owner->frontend->tokens->source->content, $node->start, $node->length);
    }
}
