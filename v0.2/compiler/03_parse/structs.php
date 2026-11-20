<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse record members and preserve their declaration order and visibility. */
final class Record_Syntax
{
	/** Parse members in source order. @param (ast_function_declaration|ast_constant_declaration|ast_global_declaration)[] $members */
	public static function read(Parser $parser, statement_grammar $grammar, array &$members /** list<ast_function_declaration|ast_constant_declaration|ast_global_declaration> */): ast_record_declaration|ast_trait_declaration
	{
		$c = $parser->cursor;
		$flavor = $c->tokens->source->flavor;
		$start = $c->position();
		$abstract = false;
		$final = false;
		while (!$c->at_end() && ($c->keyword('abstract') || $c->tokens->text_at($c->position()) === 'final')) {
			$modifier = $c->keyword('abstract') ? 'abstract' : 'final';
			if ($modifier === 'abstract' ? $abstract : $final) throw new diagnostic($c->tokens->source, $c->offset(), 'Duplicate class modifier: ' . $modifier);
			if ($modifier === 'abstract') $abstract = true;
			else $final = true;
			$c->take($c->tokens->tokens[$c->position()]->kind, $modifier);
		}
		$trait = $c->keyword('trait');
		$kind = $c->keyword('interface') ? record_kind::interface_type
			: ($c->keyword('class') ? record_kind::class_type : record_kind::structure);
		$c->take_keyword($trait ? 'trait' : match ($kind) {
			record_kind::interface_type => 'interface',
			record_kind::class_type => 'class',
			default => 'struct',
		});
		if ($kind === record_kind::interface_type) $abstract = true;
		$name = $c->name(token_kind::identifier);
		if ($flavor === flavor::cpp && !$c->at_end() && $c->tokens->text_at($c->position()) === 'final') {
			if ($final) throw new diagnostic($c->tokens->source, $c->offset(), 'Duplicate class modifier: final');
			$c->take($c->tokens->tokens[$c->position()]->kind, 'final');
			$final = true;
		}
		if ($final && ($abstract || $kind !== record_kind::class_type)) throw new diagnostic($c->tokens->source, $c->offset(), 'Final requires a non-abstract class');
		if (type_registry::resolve($name) !== null || type_registry::generic_arity($name) !== null || $name === 'void' || $name === Iterator_Contract::NAME) {
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
		$interfaces = [];
		if ($kind === record_kind::interface_type && $base !== null) throw new diagnostic($c->tokens->source, $c->offset(), 'Interface inheritance is deferred');
		if ($c->keyword('implements') || ($flavor === flavor::cpp && $c->is(token_kind::comma))) {
			if ($c->keyword('implements')) $c->take_keyword('implements');
			else $c->take(token_kind::comma, ',');
			do {
				if ($flavor === flavor::cpp && $c->keyword('public')) $c->take_keyword('public');
				$interfaces[] = $parser->named_type();
				if (!$c->is(token_kind::comma)) break;
				$c->take(token_kind::comma, ',');
			} while (true);
		}
		if ($kind === record_kind::interface_type && $interfaces !== []) throw new diagnostic($c->tokens->source, $c->offset(), 'Interface inheritance is deferred');
		if ($trait && ($abstract || $final || $base !== null || $interfaces !== [])) throw new diagnostic($c->tokens->source, $c->offset(), 'Traits require an ordinary member body');
		$body_start = $c->position();
		$uses = [];
		$aliases = [];
		$fields = self::members($parser, $grammar, $name, $kind, $base !== null || $trait, $trait, $members, $uses, $aliases);
		if ($trait) return new ast_trait_declaration($name, $parser->source_namespace(), $start, $c->position(), $body_start, $fields, $members, $uses);
		$hooks = [];
		$parser->contribute($name, $uses, $aliases, $fields, $members, $hooks);
		$record = new ast_record_declaration($name, $parser->source_namespace(), $fields, $start, $c->position(), $members, $base, $kind, $abstract, $interfaces, $final, $hooks);
		foreach ($uses as $use) $use->attach($record);
		return $record;
	}

	/**
	 * @param ast_node[] $members
	 * @param array<string,list<array{string,string,int}>> $aliases Original name, private alias and adaptation token position.
	 * @param ast_type_reference[] $uses
	 * @return ast_instance_field[]
	 */
	public static function members(Parser $parser, statement_grammar $grammar, string $name, record_kind $kind,
		bool $derived, bool $trait, array &$members /** list<ast_node> */, array &$uses /** array<string,ast_type_reference> */, array &$aliases /** array<string,list<array{string,string,int}>> */ = [], ?string $origin_name = null): array /** array<string,ast_instance_field> */
	{
		$c = $parser->cursor;
		$flavor = $c->tokens->source->flavor;
		$c->take(token_kind::lbrace, '{');
		$fields = [];
		$private_section = false;
		$protected_section = false;
		while (!$c->is(token_kind::rbrace)) {
			if ($c->keyword('use')) {
				if ($kind === record_kind::interface_type) throw new diagnostic($c->tokens->source, $c->offset(), 'Interfaces cannot compose traits');
				$c->take_keyword('use');
				$group = false;
				do {
					$use = $parser->named_type();
					if ($use->first_child !== null) throw new diagnostic($c->tokens->source, $c->offset(), 'Expected a trait name');
					if (isset($uses[$use->name])) throw new diagnostic($c->tokens->source, $c->offset(), 'Duplicate trait use: ' . $use->name);
					$uses[$use->name] = $use;
					if (!$c->is(token_kind::comma)) break;
					$c->take(token_kind::comma, ',');
					$group = true;
				} while (true);
				if ($c->is(token_kind::lbrace)) {
					if ($trait || $group) throw new diagnostic($c->tokens->source, $c->offset(), 'Trait aliases require one direct use in a class or struct');
					$c->take(token_kind::lbrace, '{');
					while (!$c->is(token_kind::rbrace)) {
						$at = $c->position();
						$original = $c->name(token_kind::identifier);
						foreach (['as', 'private'] as $word) {
							if ($c->at_end() || $c->tokens->text_at($c->position()) !== $word) throw new diagnostic($c->tokens->source, $c->offset(), 'Expected ' . $word . ' in trait alias');
							$c->take($c->tokens->tokens[$c->position()]->kind, $word);
						}
						$alias = $c->name(token_kind::identifier);
						if (in_array($alias, ['__construct', '__destruct'], true) || ($flavor === flavor::js && in_array($alias, ['constructor', 'destructor'], true)) || ($flavor === flavor::cpp && $alias === $name)) throw new diagnostic($c->tokens->source, $c->offset(), 'Expected ordinary trait method alias');
						$aliases[$use->name][] = [$original, $alias, $at];
						$c->take(token_kind::semicolon, '; after trait alias');
					}
					$c->take(token_kind::rbrace, '}');
				} else $c->take(token_kind::semicolon, '; after trait use');
				continue;
			}
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
			if ($kind === record_kind::interface_type && ($static || $private || $protected || $c->keyword('const'))) throw new diagnostic($c->tokens->source, $c->offset(), 'Interfaces admit only public instance method contracts');
			$visibility = $private ? member_visibility::private : ($protected ? member_visibility::protected : member_visibility::public);
			if ($static && $flavor === flavor::cpp && $c->next_is(token_kind::identifier)
				&& $c->tokens->text_at($c->position() + 1) === 'constexpr') {
				$members[] = Constant_Syntax::read($parser, $visibility);
				continue;
			}
			if ($static) $c->take_keyword('static');
			self::modifiers($c, $modifiers, false);
			if (!$static && $flavor !== flavor::cpp && $c->keyword('const')) {
				$members[] = Constant_Syntax::read($parser, $visibility);
				continue;
			}
			$readonly = $c->tokens->text_at($c->position()) === 'readonly';
			if ($readonly) {
				$c->take($c->is(token_kind::keyword) ? token_kind::keyword : token_kind::identifier, 'readonly');
				if ($static) throw new diagnostic($c->tokens->source, $c->offset(), 'Readonly static properties are deferred');
			}
			$constructor = match ($flavor) {
				flavor::php => $c->keyword('function') && $c->tokens->text_at($c->position() + 1) === '__construct',
				flavor::cpp => $c->tokens->text_at($c->position()) === ($origin_name ?? $name) && $c->next_is(token_kind::lparen),
				flavor::js => $c->tokens->text_at($c->position()) === 'constructor' && $c->next_is(token_kind::lparen),
			};
			$destructor = match ($flavor) {
				flavor::php => $c->keyword('function') && $c->tokens->text_at($c->position() + 1) === '__destruct',
				flavor::cpp => $c->tokens->text_at($c->position()) === '~',
				flavor::js => $c->tokens->text_at($c->position()) === 'destructor' && $c->next_is(token_kind::lparen),
			};
			if ($constructor && $kind === record_kind::interface_type) throw new diagnostic($c->tokens->source, $c->offset(), 'Interface constructors are forbidden');
			if ($destructor) {
				if ($static || $kind === record_kind::interface_type || $modifiers !== []) throw new diagnostic($c->tokens->source, $c->offset(), 'Expected ordinary instance destructor');
				if ($flavor === flavor::php) $c->take_keyword('function');
				elseif ($flavor === flavor::cpp) $c->take($c->tokens->tokens[$c->position()]->kind, '~');
				$at = $c->position();
				$written = $c->name(token_kind::identifier);
				if ($flavor === flavor::cpp && $written !== ($origin_name ?? $name)) throw new diagnostic($c->tokens->source, $c->offset(), 'Destructor must name the declaring record or trait');
				$c->take(token_kind::lparen, '(');
				$c->take(token_kind::rparen, ') after zero destructor parameters');
				$method = new ast_function_declaration('__destruct', new ast_type_reference($at, $at, 'void'),
					$field_start, $c->position(), false, $parser->source_namespace(), private: $private, protected: $protected);
			} elseif ($constructor) {
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
				$method = $js_method ? $parser->named_function_header(false, $static, $private, $protected)
					: (($kind === record_kind::interface_type && $flavor === flavor::cpp || $grammar->function_start($parser)) ? $grammar->function_header($parser, $static, $private, $protected) : null);
				if ($method?->short_name === '__destruct') throw new diagnostic($c->tokens->source, $c->offset(), 'Expected a destructor declaration');
				if ($method?->short_name === '__construct' || ($flavor === flavor::js && $method?->short_name === 'constructor')) {
					throw new diagnostic($c->tokens->source, $c->offset(), 'Expected a constructor declaration');
				}
			}
			if ($method !== null) {
				if ($readonly) throw new diagnostic($c->tokens->source, $c->offset(), 'Readonly applies to instance fields');
				if ($flavor === flavor::cpp) self::modifiers($c, $modifiers, false);
				$method->is_abstract = isset($modifiers['abstract']);
				$method->is_virtual = isset($modifiers['virtual']);
				$method->is_override = isset($modifiers['override']);
				$method->is_final = isset($modifiers['final']);
				$method->modifier_start = $modifiers === [] ? null : min($modifiers);
				if ($flavor === flavor::cpp && $method->is_abstract) throw new diagnostic($c->tokens->source, $c->offset(), 'Use = 0 for C++ abstract methods');
				if ($flavor === flavor::cpp && $c->is(token_kind::assign)) {
					if (!$method->is_virtual && !$method->is_override) throw new diagnostic($c->tokens->source, $c->offset(), 'Pure virtual method requires virtual or override');
					$c->take(token_kind::assign, '=');
					if (!$c->is(token_kind::integer) || $c->tokens->text_at($c->position()) !== '0') throw new diagnostic($c->tokens->source, $c->offset(), 'Pure virtual declaration requires = 0');
					$c->take(token_kind::integer, '0');
					$method->is_abstract = true;
				}
				if ($trait) {
					$allowed = $method->is_abstract ? ($flavor === flavor::cpp ? 'virtual' : 'abstract') : null;
					if (($method->is_abstract && ($private || $static || $constructor))
						|| ($modifiers !== [] && array_keys($modifiers) !== [$allowed])) {
						throw new diagnostic($c->tokens->source, $c->offset(), 'Trait hooks require public or protected abstract instance methods');
					}
				}
				if ($kind === record_kind::interface_type) $method->is_abstract = true;
				if ($method->is_abstract) {
					$method->complete_abstract($c->take(token_kind::semicolon, '; after abstract method') + 1);
					$members[] = $method;
				} else $members[] = $parser->callable_body($grammar, $method, $name, $constructor && $derived);
				continue;
			}
			if ($kind === record_kind::interface_type) throw new diagnostic($c->tokens->source, $c->offset(), 'Interface fields are forbidden');
			if ($modifiers !== []) throw new diagnostic($c->tokens->source, $c->offset(), 'Method modifier requires a method');
			if ($static) {
				$members[] = Global_Syntax::read($parser, true, $visibility);
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
			$node = new ast_instance_field($field, $type, $field_start, $end, $private, $initializer, $protected, $readonly);
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
		return $fields;
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
				if (!in_array($name, ['virtual', 'override', 'final', 'abstract'], true)) return;
				$c->take($c->is(token_kind::keyword) ? token_kind::keyword : token_kind::identifier, $name);
			}
			if (isset($modifiers[$name])) throw new diagnostic($c->tokens->source, $c->tokens->tokens[$at]->offset, 'Duplicate method modifier: ' . $name);
			$modifiers[$name] = $at;
		}
	}

}
