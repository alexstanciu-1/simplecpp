<?php
declare(strict_types=1);
namespace parse;

/** Explicit continuation frames preserve nested expressions without C++ recursion. */
trait Expression_Parsing {
    private function expression(int $context): int {
        $this->push($context, 0);
        $id /** int */ = 0;
        $need_operand = true;
        while (true) {
            if ($need_operand) {
                $id = $this->operand();
                if ($id === 0) { continue; }
                $need_operand = false;
            }
            $frame = $this->frame();
            $id = $this->application($frame, $id);
            if ($id === 0) { $need_operand = true; continue; }
            if ($frame->context === \parse\EXPR_TYPE) { $this->depth = $this->depth - 1; return $id; }
            if ($frame->context === \parse\EXPR_CONSTRUCTED) {
                $this->result->tree->child($frame->node, $id);
                $token = $this->expect(\tokenize\TOKEN_LEFT_PARENTHESIS, 'construction opening parenthesis');
                $token = $this->expect(\tokenize\TOKEN_RIGHT_PARENTHESIS, 'empty constructor arguments');
                $this->result->tree->finish($frame->node, $this->end);
                $id = $frame->node;
                $this->depth = $this->depth - 1;
                continue;
            }
            $id = $this->fields($id);
            if ($this->node_kind($id) === \parse\SYNTAX_FIELD_EXPRESSION) {
                if ($this->kind() === \tokenize\TOKEN_LEFT_PARENTHESIS) {
                    $id = $this->open($id, \parse\SYNTAX_CALL_EXPRESSION, \parse\EXPR_CALL, \tokenize\TOKEN_RIGHT_PARENTHESIS);
                    if ($id === 0) { $need_operand = true; continue; }
                }
            }
            if ($this->kind() === \tokenize\TOKEN_LEFT_BRACKET) {
                $id = $this->open($id, \parse\SYNTAX_INDEX_EXPRESSION, \parse\EXPR_INDEX, \tokenize\TOKEN_RIGHT_BRACKET);
                if ($id === 0) { $need_operand = true; continue; }
                $this->fail('An array index requires one expression');
            }
            $frame->operands->push($id);
            $operation_kind = Binary_Syntax::from_token($this->kind());
            while (!$frame->operators->empty()) {
                if ($operation_kind !== 0) {
                    if (Binary_Syntax::precedence($frame->operators->top()) < Binary_Syntax::precedence($operation_kind)) { break; }
                }
                $right = $frame->operands->pop();
                $left = $frame->operands->pop();
                $binary = $this->result->tree->add($frame->operators->pop(), $this->node_start($left), 0);
                $this->result->tree->child($binary, $left);
                $this->result->tree->child($binary, $right);
                $this->result->tree->finish($binary, $this->end);
                $frame->operands->push($binary);
            }
            if ($operation_kind !== 0) {
                $frame->operators->push($operation_kind);
                $token = $this->advance();
                $need_operand = true;
                continue;
            }
            $id = $frame->operands->pop();
            if ($frame->context === \parse\EXPR_VALUE) { $this->depth = $this->depth - 1; return $id; }
            if ($frame->context === \parse\EXPR_GROUP) {
                $token = $this->expect(\tokenize\TOKEN_RIGHT_PARENTHESIS, 'group closing parenthesis');
            } else {
                $this->result->tree->child($frame->node, $id);
                if ($frame->context === \parse\EXPR_INDEX) {
                    $token = $this->expect(\tokenize\TOKEN_RIGHT_BRACKET, 'index closing bracket');
                } else {
                    if ($this->kind() === \tokenize\TOKEN_COMMA) {
                        $token = $this->advance();
                        $need_operand = true;
                        continue;
                    }
                    $close = $frame->context === \parse\EXPR_TEMPLATE ? \tokenize\TOKEN_RIGHT_ANGLE : \tokenize\TOKEN_RIGHT_PARENTHESIS;
                    $token = $this->expect($close, 'argument closing delimiter');
                }
                $this->result->tree->finish($frame->node, $this->end);
                $id = $frame->node;
            }
            $this->depth = $this->depth - 1;
        }
        return 0;
    }
    private function operand(): int {
        $frame = $this->frame();
        if (($frame->context === \parse\EXPR_TYPE) || ($frame->context === \parse\EXPR_CONSTRUCTED)) { return $this->name(); }
        $token = $this->peek();
        $kind = (int)$token->kind;
        $literal /** int */ = 0;
        if ($kind === \tokenize\TOKEN_INTEGER_LITERAL) { $literal = \parse\SYNTAX_INTEGER_LITERAL; }
        if ($kind === \tokenize\TOKEN_STRING_LITERAL) { $literal = \parse\SYNTAX_STRING_LITERAL; }
        if ($kind === \tokenize\TOKEN_BOOLEAN_LITERAL) { $literal = \parse\SYNTAX_BOOLEAN_LITERAL; }
        if ($kind === \tokenize\TOKEN_VARIABLE_NAME) { $literal = \parse\SYNTAX_VARIABLE_NAME; }
        if ($kind === \tokenize\TOKEN_IDENTIFIER) { $literal = \parse\SYNTAX_NAME; }
        if ($literal !== 0) {
            $token = $this->advance();
            return $this->result->tree->add($literal, (int)$token->start, (int)$token->length);
        }
        if ($kind === \tokenize\TOKEN_LEFT_PARENTHESIS) {
            $token = $this->advance();
            $this->push(\parse\EXPR_GROUP, 0);
            return 0;
        }
        if ($kind === \tokenize\TOKEN_NEW_KEYWORD) {
            $token = $this->advance();
            $node = $this->result->tree->add(\parse\SYNTAX_CONSTRUCT_EXPRESSION, (int)$token->start, 0);
            $this->push(\parse\EXPR_CONSTRUCTED, $node);
            return 0;
        }
        $this->fail('Expected expression operand');
        return 0;
    }
    private function application(Expression_Frame $frame, int $target): int {
        $kind = $this->node_kind($target);
        $type = ($frame->context === \parse\EXPR_TYPE) || ($frame->context === \parse\EXPR_CONSTRUCTED);
        if ($kind === \parse\SYNTAX_NAME) {
            if ($this->kind() === \tokenize\TOKEN_LEFT_ANGLE) {
                $is_template = $type || ($frame->context === \parse\EXPR_TEMPLATE);
                if (isset($this->angle_ends[$this->cursor])) { $is_template = true; }
                if ($is_template) { return $this->open($target, \parse\SYNTAX_TEMPLATE_APPLICATION, \parse\EXPR_TEMPLATE, \tokenize\TOKEN_RIGHT_ANGLE); }
            }
        }
        if (($kind === \parse\SYNTAX_NAME) || ($kind === \parse\SYNTAX_TEMPLATE_APPLICATION)) {
            if (!$type) {
                if ($this->kind() === \tokenize\TOKEN_LEFT_PARENTHESIS) {
                    return $this->open($target, \parse\SYNTAX_CALL_EXPRESSION, \parse\EXPR_CALL, \tokenize\TOKEN_RIGHT_PARENTHESIS);
                }
            }
        }
        return $target;
    }
    private function open(int $target, int $kind, int $context, int $close): int {
        $node = $this->result->tree->add($kind, $this->node_start($target), 0);
        $this->result->tree->child($node, $target);
        $token = $this->advance();
        if ($this->kind() !== $close) { $this->push($context, $node); return 0; }
        if ($context === \parse\EXPR_TEMPLATE) { $this->fail('Expected template argument'); }
        $token = $this->advance();
        $this->result->tree->finish($node, $this->end);
        return $node;
    }
    private function fields(int $base): int {
        while ($this->kind() === \tokenize\TOKEN_FIELD_ARROW) {
            $token = $this->advance();
            $node = $this->result->tree->add(\parse\SYNTAX_FIELD_EXPRESSION, $this->node_start($base), 0);
            $this->result->tree->child($node, $base);
            $this->result->tree->child($node, $this->name());
            $this->result->tree->finish($node, $this->end);
            $base = $node;
        }
        return $base;
    }
}
