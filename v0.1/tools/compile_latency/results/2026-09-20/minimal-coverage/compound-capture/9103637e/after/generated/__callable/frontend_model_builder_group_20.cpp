#include <scpp/lang/php.hpp>
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_zero_arg_call_expression.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_qualified_zero_arg_call_expression.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_zero_arg_call_expression(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_zero_arg_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[159]);
	return ((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(0), __latency_fn_token_kinds_identifier(), string_t("")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("("))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_symbol(), string_t(")")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_qualified_zero_arg_call_expression(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_qualified_zero_arg_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[160]);
	int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("\\"))))) {
		offset = (offset + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>((!__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_identifier(), string_t(""))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	offset = (offset + static_cast<int_t<> >(1));
	bool_t qualified = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	while (static_cast<bool>(php::condition_truthy(__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("\\"))))) {
		qualified = bool_t(static_cast<bool_t>(true));
		offset = (offset + static_cast<int_t<> >(1));
		if (static_cast<bool>((!__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_identifier(), string_t(""))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		offset = (offset + static_cast<int_t<> >(1));
	}
	return ((qualified && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("("))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), string_t(")")));
}

}
