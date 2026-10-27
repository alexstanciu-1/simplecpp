#include "tokenize/scanner.hpp"

namespace scpp::compiler
{
	/**
	 * Classify fixed spellings without constructing strings or changing token boundaries.
	 */
	token_kind classify_word(const token_buffer &source, uint64 start, uint64 length)
	{
		if (length == 2)
		{
			if (source.matches(start, length, "as")) {
				return token_kind::kw_as;
			}
			if (source.matches(start, length, "do")) {
				return token_kind::kw_do;
			}
			if (source.matches(start, length, "fn")) {
				return token_kind::kw_fn;
			}
			if (source.matches(start, length, "if")) {
				return token_kind::kw_if;
			}
			if (source.matches(start, length, "or")) {
				return token_kind::kw_or;
			}
		}
		if (length == 3)
		{
			if (source.matches(start, length, "and")) {
				return token_kind::kw_and;
			}
			if (source.matches(start, length, "for")) {
				return token_kind::kw_for;
			}
			if (source.matches(start, length, "new")) {
				return token_kind::kw_new;
			}
			if (source.matches(start, length, "set")) {
				return token_kind::kw_set;
			}
			if (source.matches(start, length, "try")) {
				return token_kind::kw_try;
			}
			if (source.matches(start, length, "use")) {
				return token_kind::kw_use;
			}
			if (source.matches(start, length, "xor")) {
				return token_kind::kw_xor;
			}
		}
		if (length == 4)
		{
			if (source.matches(start, length, "case")) {
				return token_kind::kw_case;
			}
			if (source.matches(start, length, "data")) {
				return token_kind::kw_data;
			}
			if (source.matches(start, length, "echo")) {
				return token_kind::kw_echo;
			}
			if (source.matches(start, length, "else")) {
				return token_kind::kw_else;
			}
			if (source.matches(start, length, "enum")) {
				return token_kind::kw_enum;
			}
			if (source.matches(start, length, "lock")) {
				return token_kind::kw_lock;
			}
			if (source.matches(start, length, "move")) {
				return token_kind::kw_move;
			}
			if (source.matches(start, length, "null")) {
				return token_kind::kw_null;
			}
			if (source.matches(start, length, "take")) {
				return token_kind::kw_take;
			}
			if (source.matches(start, length, "true")) {
				return token_kind::kw_true;
			}
			if (source.matches(start, length, "void")) {
				return token_kind::kw_void;
			}
			if (source.matches(start, length, "weak")) {
				return token_kind::kw_weak;
			}
		}
		if (length == 5)
		{
			if (source.matches(start, length, "async")) {
				return token_kind::kw_async;
			}
			if (source.matches(start, length, "await")) {
				return token_kind::kw_await;
			}
			if (source.matches(start, length, "break")) {
				return token_kind::kw_break;
			}
			if (source.matches(start, length, "catch")) {
				return token_kind::kw_catch;
			}
			if (source.matches(start, length, "class")) {
				return token_kind::kw_class;
			}
			if (source.matches(start, length, "clean")) {
				return token_kind::kw_clean;
			}
			if (source.matches(start, length, "const")) {
				return token_kind::kw_const;
			}
			if (source.matches(start, length, "empty")) {
				return token_kind::kw_empty;
			}
			if (source.matches(start, length, "false")) {
				return token_kind::kw_false;
			}
			if (source.matches(start, length, "final")) {
				return token_kind::kw_final;
			}
			if (source.matches(start, length, "isset")) {
				return token_kind::kw_isset;
			}
			if (source.matches(start, length, "match")) {
				return token_kind::kw_match;
			}
			if (source.matches(start, length, "throw")) {
				return token_kind::kw_throw;
			}
			if (source.matches(start, length, "trait")) {
				return token_kind::kw_trait;
			}
			if (source.matches(start, length, "union")) {
				return token_kind::kw_union;
			}
			if (source.matches(start, length, "unset")) {
				return token_kind::kw_unset;
			}
			if (source.matches(start, length, "while")) {
				return token_kind::kw_while;
			}
		}
		if (length == 6)
		{
			if (source.matches(start, length, "elseif")) {
				return token_kind::kw_elseif;
			}
			if (source.matches(start, length, "public")) {
				return token_kind::kw_public;
			}
			if (source.matches(start, length, "return")) {
				return token_kind::kw_return;
			}
			if (source.matches(start, length, "static")) {
				return token_kind::kw_static;
			}
			if (source.matches(start, length, "struct")) {
				return token_kind::kw_struct;
			}
			if (source.matches(start, length, "switch")) {
				return token_kind::kw_switch;
			}
		}
		if (length == 7)
		{
			if (source.matches(start, length, "default")) {
				return token_kind::kw_default;
			}
			if (source.matches(start, length, "extends")) {
				return token_kind::kw_extends;
			}
			if (source.matches(start, length, "finally")) {
				return token_kind::kw_finally;
			}
			if (source.matches(start, length, "foreach")) {
				return token_kind::kw_foreach;
			}
			if (source.matches(start, length, "private")) {
				return token_kind::kw_private;
			}
		}
		if (length == 8)
		{
			if (source.matches(start, length, "abstract")) {
				return token_kind::kw_abstract;
			}
			if (source.matches(start, length, "continue")) {
				return token_kind::kw_continue;
			}
			if (source.matches(start, length, "function")) {
				return token_kind::kw_function;
			}
			if (source.matches(start, length, "readonly")) {
				return token_kind::kw_readonly;
			}
			if (source.matches(start, length, "template")) {
				return token_kind::kw_template;
			}
			if (source.matches(start, length, "typename")) {
				return token_kind::kw_typename;
			}
		}
		if (length == 9)
		{
			if (source.matches(start, length, "interface")) {
				return token_kind::kw_interface;
			}
			if (source.matches(start, length, "namespace")) {
				return token_kind::kw_namespace;
			}
			if (source.matches(start, length, "protected")) {
				return token_kind::kw_protected;
			}
		}
		if (length == 10)
		{
			if (source.matches(start, length, "implements")) {
				return token_kind::kw_implements;
			}
			if (source.matches(start, length, "instanceof")) {
				return token_kind::kw_instanceof;
			}
		}
		if (length == 11)
		{
			if (source.matches(start, length, "object_cast")) {
				return token_kind::kw_object_cast;
			}
		}
		return token_kind::identifier;
	}

	/**
	 * Test only the alternatives with the current leading byte, longest first.
	 */
	token_kind scan_operator(const token_buffer &source, uint64 start, uint64 &length)
	{
		const auto first = source.peek(start);
		const auto next = source.peek(start + 1);
		const auto third = source.peek(start + 2);
		if (first == 35)
		{
			if (next == 91) {
				length = 2;
				return token_kind::attribute;
			}
		}
		if (first == 46)
		{
			if (next == 46 && third == 46) {
				length = 3;
				return token_kind::ellipsis;
			}
			if (next == 61) {
				length = 2;
				return token_kind::concat_assign;
			}
			length = 1;
			return token_kind::dot;
		}
		if (first == 42)
		{
			if (next == 42 && third == 61) {
				length = 3;
				return token_kind::power_assign;
			}
			if (next == 42) {
				length = 2;
				return token_kind::power;
			}
			if (next == 61) {
				length = 2;
				return token_kind::star_assign;
			}
			length = 1;
			return token_kind::star;
		}
		if (first == 60)
		{
			if (next == 60 && third == 61) {
				length = 3;
				return token_kind::left_assign;
			}
			if (next == 61 && third == 62) {
				length = 3;
				return token_kind::spaceship;
			}
			if (next == 61) {
				length = 2;
				return token_kind::less_equal;
			}
			length = 1;
			return token_kind::less;
		}
		if (first == 62)
		{
			if (next == 62 && third == 61) {
				length = 3;
				return token_kind::right_assign;
			}
			if (next == 61) {
				length = 2;
				return token_kind::greater_equal;
			}
			length = 1;
			return token_kind::greater;
		}
		if (first == 61)
		{
			if (next == 61 && third == 61) {
				length = 3;
				return token_kind::identical;
			}
			if (next == 61) {
				length = 2;
				return token_kind::equal;
			}
			if (next == 62) {
				length = 2;
				return token_kind::arrow;
			}
			length = 1;
			return token_kind::assign;
		}
		if (first == 33)
		{
			if (next == 61 && third == 61) {
				length = 3;
				return token_kind::not_identical;
			}
			if (next == 61) {
				length = 2;
				return token_kind::not_equal;
			}
			length = 1;
			return token_kind::bang;
		}
		if (first == 45)
		{
			if (next == 62) {
				length = 2;
				return token_kind::member;
			}
			if (next == 45) {
				length = 2;
				return token_kind::decrement;
			}
			if (next == 61) {
				length = 2;
				return token_kind::minus_assign;
			}
			length = 1;
			return token_kind::minus;
		}
		if (first == 58)
		{
			if (next == 58) {
				length = 2;
				return token_kind::scope;
			}
			length = 1;
			return token_kind::colon;
		}
		if (first == 63)
		{
			if (next == 63) {
				length = 2;
				return token_kind::coalesce;
			}
			length = 1;
			return token_kind::question;
		}
		if (first == 38)
		{
			if (next == 38) {
				length = 2;
				return token_kind::logical_and;
			}
			if (next == 61) {
				length = 2;
				return token_kind::and_assign;
			}
			length = 1;
			return token_kind::ampersand;
		}
		if (first == 124)
		{
			if (next == 124) {
				length = 2;
				return token_kind::logical_or;
			}
			if (next == 61) {
				length = 2;
				return token_kind::or_assign;
			}
			length = 1;
			return token_kind::pipe;
		}
		if (first == 43)
		{
			if (next == 43) {
				length = 2;
				return token_kind::increment;
			}
			if (next == 61) {
				length = 2;
				return token_kind::plus_assign;
			}
			length = 1;
			return token_kind::plus;
		}
		if (first == 47)
		{
			if (next == 61) {
				length = 2;
				return token_kind::slash_assign;
			}
			length = 1;
			return token_kind::slash;
		}
		if (first == 37)
		{
			if (next == 61) {
				length = 2;
				return token_kind::percent_assign;
			}
			length = 1;
			return token_kind::percent;
		}
		if (first == 94)
		{
			if (next == 61) {
				length = 2;
				return token_kind::xor_assign;
			}
			length = 1;
			return token_kind::caret;
		}
		if (first == 59)
		{
			length = 1;
			return token_kind::semicolon;
		}
		if (first == 40)
		{
			length = 1;
			return token_kind::lparen;
		}
		if (first == 41)
		{
			length = 1;
			return token_kind::rparen;
		}
		if (first == 123)
		{
			length = 1;
			return token_kind::lbrace;
		}
		if (first == 125)
		{
			length = 1;
			return token_kind::rbrace;
		}
		if (first == 44)
		{
			length = 1;
			return token_kind::comma;
		}
		if (first == 91)
		{
			length = 1;
			return token_kind::lbracket;
		}
		if (first == 93)
		{
			length = 1;
			return token_kind::rbracket;
		}
		if (first == 126)
		{
			length = 1;
			return token_kind::tilde;
		}
		if (first == 92)
		{
			length = 1;
			return token_kind::backslash;
		}
		return token_kind::reserved;
	}
}
