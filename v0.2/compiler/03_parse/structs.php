<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse record members and preserve their declaration order and visibility. */
final class Record_Syntax
{
	/** Parse members in source order. @param (ast_function_declaration|ast_constant_declaration|ast_global_declaration)[] $members */
	public static function read(Parser $parser, statement_grammar $grammar, array &$members /** list<ast_function_declaration|ast_constant_declaration|ast_global_declaration> */): ast_record_declaration
	{
		$c = $parser->cursor;
		$flavor = $c->tokens->source->flavor;
		$start = $c->position();
		$kind = $c->keyword('class') ? record_kind::class_type : record_kind::structure;
		$c->take_keyword($kind === record_kind::class_type ? 'class' : 'struct');
		$name = $c->name(token_kind::identifier);
		if (type_registry::resolve($name) !== null || type_registry::generic_arity($name) !== null || $name === 'void') {
			throw new diagnostic($c->tokens->source, $c->tokens->tokens[$start + 1]->offset, 'Reserved type name: ' . $name);
		}
		$base = null;
		if ($flavor === flavor::cpp ? $c->is(token_kind::colon) : $c->keyword('extends')) {
			if ($flavor === flavor::cpp) {
				$c->take(token_kind::colon, ':');
				if ($c->keyword('public')) $c->take_keyword('public');
			} else $c->take_keyword('extends');
			$base = $parser->named_type();
		}
		$c->take(token_kind::lbrace, '{');
		$fields = [];
		$private_section = false;
		$protected_section = false;
		while (!$c->is(token_kind::rbrace)) {
			$field_start = $c->position();
			$modifiers = [];
			self::modifiers($c, $modifiers, true);
			// JS visibility spellings are contextual identifiers; recognize them only before a member.
			$js_visibility = $flavor === flavor::js && $c->is(token_kind::identifier)
				&& ($c->next_is(token_kind::identifier) || $c->next_is(token_kind::keyword))
				&& in_array($c->tokens->text_at($field_start), ['private', 'protected'], true);
			$protected = $c->keyword('protected') || ($js_visibility && $c->tokens->text_at($field_start) === 'protected');
			$private = $c->keyword('private') || ($js_visibility && !$protected);
			if ($private || $protected || $c->keyword('public')) {
				$c->take($js_visibility ? token_kind::identifier : token_kind::keyword, 'visibility');
				if ($flavor === flavor::cpp) {
					$c->take(token_kind::colon, ':');
					$private_section = $private;
					$protected_section = $protected;
					continue;
				}
			}
			if ($flavor === flavor::cpp) {
				$private = $private_section;
				$protected = $protected_section;
			}
			self::modifiers($c, $modifiers, false);
			$static = $c->keyword('static');
			if ($protected && ($static || $c->keyword('const'))) {
				throw new diagnostic($c->tokens->source, $c->offset(), 'Protected static members are deferred');
			}
			if ($static && $flavor === flavor::cpp && $c->next_is(token_kind::identifier)
				&& $c->tokens->text_at($c->position() + 1) === 'constexpr') {
				$members[] = Constant_Syntax::read($parser, $private);
				continue;
			}
			if ($static) $c->take_keyword('static');
			self::modifiers($c, $modifiers, false);
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
					if ($parameter->mode !== passing_mode::value) {
						throw new diagnostic($c->tokens->source, $c->offset(), 'Constructors require by-value parameters');
					}
				}
				$void = new ast_type_reference($at, $at, 'void');
				$method = new ast_function_declaration('__construct', $void, $field_start, $c->position(), false,
					$parser->source_namespace(), $parameters, private: $private, protected: $protected);
			} else {
				$js_method = $flavor === flavor::js && $c->is(token_kind::identifier) && $c->next_is(token_kind::lparen);
				$method = $js_method ? $parser->named_function_header(false, $static, $private)
					: ($grammar->function_start($parser) ? $grammar->function_header($parser, $static, $private) : null);
				if ($method?->short_name === '__construct' || ($flavor === flavor::js && $method?->short_name === 'constructor')) {
					throw new diagnostic($c->tokens->source, $c->offset(), 'Expected a constructor declaration');
				}
			}
			if ($method !== null) {
				if ($flavor === flavor::cpp) self::modifiers($c, $modifiers, false);
				$method->is_virtual = isset($modifiers['virtual']);
				$method->is_override = isset($modifiers['override']);
				$method->is_final = isset($modifiers['final']);
				$method->modifier_start = $modifiers === [] ? null : min($modifiers);
				if ($protected && !$constructor) throw new diagnostic($c->tokens->source, $c->offset(), 'Protected ordinary methods are deferred');
				$members[] = $parser->callable_body($grammar, $method, $name, $constructor && $base !== null);
				continue;
			}
			if ($modifiers !== []) throw new diagnostic($c->tokens->source, $c->offset(), 'Method modifier requires a method');
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
			$initializer = null;
			if ($c->is(token_kind::assign)) {
				$c->take(token_kind::assign, '=');
				$initializer = $parser->literal_initializer('Field initializer requires a literal or enum case', allow_case: true);
			}
			$end = $c->take(token_kind::semicolon, ';') + 1;
			$node = new ast_instance_field($field, $type, $field_start, $end, $private, $initializer, $protected);
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
		return new ast_record_declaration($name, $parser->source_namespace(), $fields, $start, $c->position(), $members, $base, $kind);
	}
	/** Recognize only the bounded method markers; no general attribute interpretation. @param int[] $modifiers */
	private static function modifiers(Syntax_Cursor $c, array &$modifiers /** array<string,uint32> */, bool $attributes): void
	{
		while (true) {
			$at = $c->position();
			if ($attributes && $c->is(token_kind::attribute)) {
				$c->take(token_kind::attribute, '#[');
				if ($c->is(token_kind::backslash)) $c->take(token_kind::backslash, '\\');
				$name = $c->name(token_kind::identifier);
				if (!in_array($name, ['Virtual', 'Override'], true)) throw new diagnostic($c->tokens->source, $c->offset(), 'Unsupported method attribute');
				if ($c->is(token_kind::lparen)) throw new diagnostic($c->tokens->source, $c->offset(), 'Method attributes take no arguments');
				$c->take(token_kind::rbracket, ']');
				$name = strtolower($name);
			} else {
				$name = $c->tokens->text_at($at);
				if (!in_array($name, ['virtual', 'override', 'final'], true)) return;
				$c->take($c->is(token_kind::keyword) ? token_kind::keyword : token_kind::identifier, $name);
			}
			if (isset($modifiers[$name])) throw new diagnostic($c->tokens->source, $c->tokens->tokens[$at]->offset, 'Duplicate method modifier: ' . $name);
			$modifiers[$name] = $at;
		}
	}

}
