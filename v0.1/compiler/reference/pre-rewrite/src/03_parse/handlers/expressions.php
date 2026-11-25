<?php
declare(strict_types=1);

/*
 * Role: Parse expressions and type syntax with one iterative continuation stack.
 * Used by: File_Parser (private trait methods on this owner)
 * Call map:
 *   type_syntax() / expression()
 *     -> expression_operand(); application_suffix(); binary_expression(); complete_expression()
 */

namespace parse;

use tokenize\token_kind;

/** @compiler-internal Shared syntax driver; names stay unresolved and every frame belongs to this file task. */
trait Expression_Parsing
{
    /** Parse a named type or template application through the common syntax driver. */
    private function type_syntax(string $description = 'type name'): int
    {
        if ($this->peek()->kind !== token_kind::identifier) {
            $this->fail('Expected ' . $description);
        }
        return $this->expression(expression_context::type);
    }

    /** Preserve nested calls, applications and grouping without recursive parsing or speculative AST copies. */
    private function expression(expression_context $context = expression_context::value): int
    {
        $pending = [new expression_cursor($context)];
        while (true)
        {
            $id = $this->expression_operand($pending);
            if ($id === null) {
                continue;
            }
            while (true)
            {
                $cursor = $pending[count($pending) - 1];
                $id = $this->application_suffix($pending, $cursor, $id);
                if ($id === null) {
                    continue 2;
                }

                // Type positions accept names/applications, never value operators or calls.
                if ($cursor->context === expression_context::type) {
                    return $id;
                }
                if ($cursor->context === expression_context::constructed_type) {
                    $id = $this->complete_construction($cursor, $id);
                    array_pop($pending);
                    continue;
                }

                // Postfix operations finish before the frame reduces binary operators by precedence.
                $id = $this->field_suffix($id);
                if (($this->tree->nodes[$id - 1]->kind === syntax_kind::field_expression)
                    && ($this->peek()->kind === token_kind::left_parenthesis)) {
                    $id = $this->open_application($pending, $id, syntax_kind::call_expression,
                        expression_context::call_arguments, token_kind::right_parenthesis);
                    if ($id === null) {
                        continue 2;
                    }
                }
                if ($this->peek()->kind === token_kind::left_bracket)
                {
                    $id = $this->open_application($pending, $id, syntax_kind::index_expression,
                        expression_context::index, token_kind::right_bracket);
                    if ($id === null) {
                        continue 2;
                    }
                    $this->fail('An array index requires one expression');
                }
                $cursor->operands[] = $id;
                $operator = Binary_Syntax::from_token($this->peek()->kind);
                while (($cursor->operators !== []) && (($operator === null)
                    || (Binary_Syntax::precedence($cursor->operators[count($cursor->operators) - 1]) >= Binary_Syntax::precedence($operator)))) {
                    $right = array_pop($cursor->operands);
                    $left = array_pop($cursor->operands);
                    $cursor->operands[] = $this->binary_expression(array_pop($cursor->operators), $left, $right);
                }
                if ($operator !== null) {
                    $cursor->operators[] = $operator;
                    $this->advance();
                    continue 2;
                }
                $id = array_pop($cursor->operands);
                if ($cursor->context === expression_context::value) {
                    return $id;
                }
                $id = $this->complete_expression($cursor, $id);
                if ($id === null) {
                    continue 2;
                }
                array_pop($pending);
            }
        }
    }

    /** Read one operand, or push a group/construction frame; no type or constant lookup is performed. */
    private function expression_operand(array &$pending): ?int
    {
        $context = $pending[count($pending) - 1]->context;
        if (in_array($context, [expression_context::type, expression_context::constructed_type], true)) {
            return $this->name('type name');
        }
        $token = $this->peek();
        $literal = match ($token->kind) {
            token_kind::integer_literal => syntax_kind::integer_literal,
            token_kind::string_literal => syntax_kind::string_literal,
            token_kind::boolean_literal => syntax_kind::boolean_literal,
            default => null,
        };
        if ($literal !== null) {
            $this->advance();
            return $this->node($literal, $token->start, $token->length);
        }
        if ($token->kind === token_kind::identifier) {
            return $this->name('name');
        }
        if ($token->kind === token_kind::variable_name) {
            return $this->variable();
        }
        if ($token->kind === token_kind::left_parenthesis) {
            $this->advance();
            $pending[] = new expression_cursor(expression_context::group);
            return null;
        }
        if ($token->kind === token_kind::new_keyword) {
            $node = $this->node(syntax_kind::construct_expression, $this->advance()->start);
            $pending[] = new expression_cursor(expression_context::constructed_type, $node);
            return null;
        }
        $this->fail('Expected literal, name, variable, grouped expression or named call');
    }

    /** Type contexts accept template applications; value names require a balanced template suffix. */
    private function application_suffix(array &$pending, expression_cursor $cursor, int $target): ?int
    {
        $kind = $this->tree->nodes[$target - 1]->kind;
        $token = $this->peek()->kind;
        if (($kind === syntax_kind::name) && ($token === token_kind::left_angle)
            && (in_array($cursor->context, [expression_context::type, expression_context::constructed_type, expression_context::template_arguments], true)
                || isset($this->angle_ends[$this->cursor]))) {
            return $this->open_application($pending, $target, syntax_kind::template_application,
                expression_context::template_arguments, token_kind::right_angle);
        }
        if (in_array($kind, [syntax_kind::name, syntax_kind::template_application], true)
            && ($token === token_kind::left_parenthesis)
            && !in_array($cursor->context, [expression_context::type, expression_context::constructed_type], true)) {
            return $this->open_application($pending, $target, syntax_kind::call_expression,
                expression_context::call_arguments, token_kind::right_parenthesis);
        }
        return $target;
    }

    /** Attach an existing target, consume its opening delimiter and schedule arguments when present. */
    private function open_application(array &$pending, int $target, syntax_kind $kind,
        expression_context $context, token_kind $close): ?int
    {
        $node = $this->node($kind, $this->tree->nodes[$target - 1]->start);
        $last = 0;
        $this->child($node, $last, $target);
        $this->advance();
        if ($this->peek()->kind !== $close) {
            $pending[] = new expression_cursor($context, $node, $target);
            return null;
        }
        if ($context === expression_context::template_arguments) {
            $this->fail('Expected at least one template argument; empty applications are unsupported');
        }
        $this->advance();
        $this->finish($node);
        return $node;
    }

    /** Preserve a constructed type and its empty argument list; construction semantics remain downstream. */
    private function complete_construction(expression_cursor $cursor, int $type): int
    {
        $this->child($cursor->node_id, $cursor->last_child_id, $type);
        $this->expect(token_kind::left_parenthesis, "'(' after constructed type");
        $this->expect(token_kind::right_parenthesis, "')'; constructor arguments are unsupported");
        $this->finish($cursor->node_id);
        return $cursor->node_id;
    }

    /** Close a group/application, or reset its accumulation after an argument separator. */
    private function complete_expression(expression_cursor $cursor, int $id): ?int
    {
        if ($cursor->context === expression_context::group) {
            $this->expect(token_kind::right_parenthesis, "')' after grouped expression");
            return $id;
        }
        $this->child($cursor->node_id, $cursor->last_child_id, $id);
        if ($cursor->context === expression_context::index) {
            $this->expect(token_kind::right_bracket, "']' after index");
            $this->finish($cursor->node_id);
            return $cursor->node_id;
        }
        if ($this->peek()->kind === token_kind::comma) {
            $this->advance();
            return null;
        }
        $template = $cursor->context === expression_context::template_arguments;
        $this->expect($template ? token_kind::right_angle : token_kind::right_parenthesis,
            $template ? "'>' after template arguments" : "')' after call arguments");
        $this->finish($cursor->node_id);
        return $cursor->node_id;
    }

    /** Attach ordered binary operands without copying syntax; the kind identifies the operator. */
    private function binary_expression(syntax_kind $kind, int $left, int $right): int
    {
        $binary = $this->node($kind, $this->tree->nodes[$left - 1]->start);
        $last = 0;
        $this->child($binary, $last, $left);
        $this->child($binary, $last, $right);
        // Reduction always ends at the current operand, including its closing group delimiters.
        $this->finish($binary);
        return $binary;
    }

    /** Keep field selection attached to its base before surrounding operators consume it. */
    private function field_suffix(int $base): int
    {
        while ($this->peek()->kind === token_kind::field_arrow)
        {
            $this->advance();
            $node = $this->node(syntax_kind::field_expression, $this->tree->nodes[$base - 1]->start);
            $last = 0;
            $this->child($node, $last, $base);
            $this->child($node, $last, $this->name('field name'));
            $this->finish($node);
            $base = $node;
        }
        return $base;
    }
}
