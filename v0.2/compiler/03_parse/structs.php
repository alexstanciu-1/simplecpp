<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse struct members and preserve their declaration order and visibility. */
final class Struct_Syntax
{
	/** Parse members in source order. @param (ast_function_declaration|ast_constant_declaration|ast_global_declaration)[] $members */
	public static function read(Parser $parser, statement_grammar $grammar, array &$members /** list<ast_function_declaration|ast_constant_declaration|ast_global_declaration> */): ast_struct_declaration
	{
		$c = $parser->cursor;
		$flavor = $c->tokens->source->flavor;
		$start = $c->position();
		$c->take_keyword('struct');
		$name = $c->name(token_kind::identifier);
		if (type_registry::resolve($name) !== null || type_registry::generic_arity($name) !== null || $name === 'void') {
			throw new diagnostic($c->tokens->source, $c->tokens->tokens[$start + 1]->offset, 'Reserved type name: ' . $name);
		}
		$c->take(token_kind::lbrace, '{');
		$fields = [];
		$private_section = false;
		while (!$c->is(token_kind::rbrace)) {
			$field_start = $c->position();
			// JS permits private as an ordinary identifier; recognize a modifier only before a member.
			$js_private = $flavor === flavor::js && $c->is(token_kind::identifier)
				&& ($c->next_is(token_kind::identifier) || $c->next_is(token_kind::keyword))
				&& $c->tokens->text_at($field_start) === 'private';
			$private = $js_private || $c->keyword('private');
			if ($private || $c->keyword('public')) {
				$c->take($js_private ? token_kind::identifier : token_kind::keyword, $private ? 'private' : 'public');
				if ($flavor === flavor::cpp) {
					$c->take(token_kind::colon, ':');
					$private_section = $private;
					continue;
				}
			}
			if ($flavor === flavor::cpp) $private = $private_section;
			$static = $c->keyword('static');
			if ($static && $flavor === flavor::cpp && $c->next_is(token_kind::identifier)
				&& $c->tokens->text_at($c->position() + 1) === 'constexpr') {
				$members[] = Constant_Syntax::read($parser, $private);
				continue;
			}
			if ($static) $c->take_keyword('static');
			if (!$static && $flavor !== flavor::cpp && $c->keyword('const')) {
				$members[] = Constant_Syntax::read($parser, $private);
				continue;
			}
			$constructor = match ($flavor) {
				flavor::php => $c->keyword('function') && $c->tokens->text_at($c->position() + 1) === '__construct',
				flavor::cpp => $c->tokens->text_at($c->position()) === $name && $c->next_is(token_kind::lparen),
				flavor::js => $c->tokens->text_at($c->position()) === 'constructor' && $c->next_is(token_kind::lparen),
			};
			if ($constructor) {
				if ($static) throw new diagnostic($c->tokens->source, $c->offset(), 'Constructor cannot be static');
				if ($flavor === flavor::php) $c->take_keyword('function');
				$at = $c->position();
				$c->name(token_kind::identifier);
				$parameters = $parser->parameters();
				foreach ($parameters as $parameter) {
					if ($parameter->mode !== passing_mode::value || $parameter->default_value !== null) {
						throw new diagnostic($c->tokens->source, $c->offset(), 'Constructors require by-value parameters without defaults');
					}
				}
				$void = new ast_type_reference($at, $at, 'void');
				$method = new ast_function_declaration('__construct', $void, $field_start, $c->position(), false,
					$parser->source_namespace(), $parameters, private: $private);
			} else {
				$js_method = $flavor === flavor::js && $c->is(token_kind::identifier) && $c->next_is(token_kind::lparen);
				$method = $js_method ? $parser->named_function_header(false, $static, $private)
					: ($grammar->function_start($parser) ? $grammar->function_header($parser, $static, $private) : null);
				if ($method?->short_name === '__construct' || ($flavor === flavor::js && $method?->short_name === 'constructor')) {
					throw new diagnostic($c->tokens->source, $c->offset(), 'Expected a constructor declaration');
				}
			}
			if ($method !== null) {
				$members[] = $parser->callable_body($grammar, $method, $name);
				continue;
			}
			if ($static) {
				$members[] = Global_Syntax::read($parser, true, $private);
				continue;
			}
			if ($flavor === flavor::js) {
				$field = $c->name(token_kind::identifier);
				$c->take(token_kind::colon, ':');
				$type = $parser->named_type();
			} else {
				$type = $parser->named_type();
				$field = $c->name($flavor === flavor::php ? token_kind::variable : token_kind::identifier);
			}
			$end = $c->take(token_kind::semicolon, ';') + 1;
			$node = new ast_struct_field($field, $type, $field_start, $end, $private);
			if (isset($fields[$field])) {
				$tail = $fields[$field];
				while ($tail->duplicate_element() !== null) $tail = $tail->duplicate_element();
				$tail->link_duplicate($node);
			} else {
				$fields[$field] = $node;
			}
		}
		$c->take(token_kind::rbrace, '}');
		if ($flavor === flavor::cpp && $c->is(token_kind::semicolon)) $c->take(token_kind::semicolon, ';');
		return new ast_struct_declaration($name, $parser->source_namespace(), $fields, $start, $c->position(), $members);
	}
}
