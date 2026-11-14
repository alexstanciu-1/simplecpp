<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Normalize PHP case declarations and C++/JS comma-separated cases without resolving values. */
final class Enum_Syntax
{
	/** Retain cases and source callables. @param ast_function_declaration[] $members */
	public static function read(Parser $parser, statement_grammar $grammar, array &$members /** list<ast_function_declaration> */): ast_enum_declaration
	{
		$c = $parser->cursor;
		$start = $c->position();
		$flavor = $c->tokens->source->flavor;
		$c->take_keyword('enum');
		$name = $c->name(token_kind::identifier);
		if (type_registry::resolve($name) !== null || type_registry::generic_arity($name) !== null || $name === 'void') {
			throw new diagnostic($c->tokens->source, $c->offset(), 'Reserved type name: ' . $name);
		}
		$backing = null;
		if ($c->is(token_kind::colon)) {
			$c->take(token_kind::colon, ':');
			$backing = $parser->named_type();
		}
		$c->take(token_kind::lbrace, '{');
		$cases = [];
		while (!$c->is(token_kind::rbrace)) {
			$at = $c->position();
			if ($c->keyword('public')) {
				$c->take_keyword('public');
				if ($flavor === flavor::cpp) {
					$c->take(token_kind::colon, ':'); continue;
				}
			}
			if ($c->keyword('private') || $c->keyword('static') || $c->keyword('protected')) {
				throw new diagnostic($c->tokens->source, $c->offset(), 'Enum methods must be public instance methods');
			}
			$js_method = $flavor === flavor::js && $c->is(token_kind::identifier) && $c->next_is(token_kind::lparen);
			$method = $js_method ? $parser->named_function_header(false, false, false)
				: ($grammar->function_start($parser) ? $grammar->function_header($parser, false, false) : null);
			if ($method !== null) {
				if (in_array($method->short_name, ['__construct', 'constructor'], true)) {
					throw new diagnostic($c->tokens->source, $c->offset(), 'Enums do not have constructors');
				}
				$members[] = $parser->callable_body($grammar, $method, $name);
				continue;
			}
			if ($flavor === flavor::php) $c->take_keyword('case');
			$case_name = $c->name(token_kind::identifier);
			$c->take(token_kind::assign, '= (explicit enum value)');
			$value = $parser->literal_initializer('Enum case requires an integer literal or string literal');
			if (!($value instanceof ast_integer_expression) && !($value instanceof ast_string_expression)) {
				throw new diagnostic($c->tokens->source, $c->tokens->tokens[$value->start]->offset, 'Enum case requires an integer literal or string literal');
			}
			$end = $c->position();
			if ($flavor === flavor::php) $end = $c->take(token_kind::semicolon, ';') + 1;
			elseif (!$c->is(token_kind::rbrace)) {
				if ($c->is(token_kind::semicolon)) $c->take(token_kind::semicolon, ';');
				else $c->take(token_kind::comma, ',');
			}
			$case = new ast_enum_case($case_name, $value, $at, $end);
			if (isset($cases[$case_name])) {
				$tail = $cases[$case_name];
				while ($tail->duplicate_element() !== null) $tail = $tail->duplicate_element();
				$tail->link_duplicate($case);
			} else $cases[$case_name] = $case;
		}
		$c->take(token_kind::rbrace, '}');
		if ($flavor === flavor::cpp && $c->is(token_kind::semicolon)) $c->take(token_kind::semicolon, ';');
		return new ast_enum_declaration($name, $parser->source_namespace(), $backing, $cases, $start, $c->position(), $members);
	}
}
