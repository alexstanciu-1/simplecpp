#include <scpp/lang/php.hpp>
#include "__types/PhsParserCursor.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/parser_cursor.hpp"
#include "__callable/__latency_fn_parser_cursor_from_tokens.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_tables_row_count.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_token_tables_row_count.hpp"
#include "__callable/__latency_fn_token_tables_token_by_index.hpp"
#include "__callable/__latency_fn_parser_cursor_peek.hpp"
#include "__callable/__latency_fn_token_tables_row_count.hpp"
#include "__callable/__latency_fn_token_tables_token_by_index.hpp"
#include "__callable/__latency_fn_parser_cursor_advance.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_parser_cursor_at.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text_equals.hpp"
#include "__callable/__latency_fn_parser_cursor_peek.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text_equals.hpp"
namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
bool_t parser_cursor::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == parser_cursor::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
PhsParserCursor __latency_fn_parser_cursor_from_tokens(shared_p<TokenStream> tokens) {
	SCPP_CALL_DEPTH_GUARD("parser_cursor::from_tokens", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_cursor.phs", __latency_lines_parser_cursor[0]);
	PhsParserCursor cursor = PhsParserCursor{};
	cursor->index = __latency_fn_structure_row_ids_none_id();
	cursor->token_count = __latency_fn_token_tables_row_count(tokens);
	return cursor;
}

}

namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
TokenRow __latency_fn_parser_cursor_current(shared_p<TokenStream> tokens, PhsParserCursor cursor) {
	SCPP_CALL_DEPTH_GUARD("parser_cursor::current", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_cursor.phs", __latency_lines_parser_cursor[1]);
	int_t<> index = required_cast<int_t<>>(cast<int_t<>>(cursor->index));
	int_t<> tokenCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_token_tables_row_count(tokens)));
	if (static_cast<bool>(((index >= static_cast<int_t<> >(0)) && (index < tokenCount)))) {
		return __latency_fn_token_tables_token_by_index(tokens, index);
	}
	if (static_cast<bool>((tokenCount > static_cast<int_t<> >(0)))) {
		return __latency_fn_token_tables_token_by_index(tokens, (tokenCount - static_cast<int_t<> >(1)));
	}
	TokenRow empty = TokenRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
TokenRow __latency_fn_parser_cursor_peek(shared_p<TokenStream> tokens, PhsParserCursor cursor, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("parser_cursor::peek", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_cursor.phs", __latency_lines_parser_cursor[2]);
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(cursor->index) + offset));
	int_t<> tokenCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_token_tables_row_count(tokens)));
	if (static_cast<bool>(((index >= static_cast<int_t<> >(0)) && (index < tokenCount)))) {
		return __latency_fn_token_tables_token_by_index(tokens, index);
	}
	if (static_cast<bool>((tokenCount > static_cast<int_t<> >(0)))) {
		return __latency_fn_token_tables_token_by_index(tokens, (tokenCount - static_cast<int_t<> >(1)));
	}
	TokenRow empty = TokenRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
PhsParserCursor __latency_fn_parser_cursor_advance(PhsParserCursor cursor) {
	SCPP_CALL_DEPTH_GUARD("parser_cursor::advance", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_cursor.phs", __latency_lines_parser_cursor[3]);
	int_t<> index = required_cast<int_t<>>(cast<int_t<>>(cursor->index));
	int_t<> tokenCount = required_cast<int_t<>>(cast<int_t<>>(cursor->token_count));
	if (static_cast<bool>((index < (tokenCount - static_cast<int_t<> >(1))))) {
		cursor->index = __latency_fn_structure_row_ids_uint32_from_int((index + static_cast<int_t<> >(1)));
	}
	return cursor;
}

}

namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
bool_t __latency_fn_parser_cursor_at(const string_t& source, shared_p<TokenStream> tokens, PhsParserCursor cursor, int_t<std::uint16_t> kindId, const string_t& literal) {
	SCPP_CALL_DEPTH_GUARD("parser_cursor::at", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_cursor.phs", __latency_lines_parser_cursor[4]);
	TokenRow token = __latency_fn_parser_cursor_current(tokens, cursor);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(token->kind_id, kindId)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::identical(literal, string_t("")))) {
		return bool_t(static_cast<bool_t>(true));
	}
	return __latency_fn_phs_tokenizer_token_text_equals(source, token, literal);
}

}

namespace scpp { extern const int __latency_lines_parser_cursor[]; }
namespace scpp {
bool_t __latency_fn_parser_cursor_peek_at(const string_t& source, shared_p<TokenStream> tokens, PhsParserCursor cursor, int_t<> offset, int_t<std::uint16_t> kindId, const string_t& literal) {
	SCPP_CALL_DEPTH_GUARD("parser_cursor::peek_at", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_cursor.phs", __latency_lines_parser_cursor[5]);
	TokenRow token = __latency_fn_parser_cursor_peek(tokens, cursor, offset);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(token->kind_id, kindId)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::identical(literal, string_t("")))) {
		return bool_t(static_cast<bool_t>(true));
	}
	return __latency_fn_phs_tokenizer_token_text_equals(source, token, literal);
}

}
