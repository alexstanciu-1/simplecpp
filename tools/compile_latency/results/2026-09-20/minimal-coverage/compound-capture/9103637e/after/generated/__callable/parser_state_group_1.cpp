#include <scpp/lang/php.hpp>
#include "__types/PhsParserState.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_at_bool_literal.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_at_null_literal.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
bool_t __latency_fn_parser_state_at_bool_literal(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("parser_state::at_bool_literal", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[12]);
	return (((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("true")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("false"))) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("true"))) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("false")));
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
bool_t __latency_fn_parser_state_at_null_literal(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("parser_state::at_null_literal", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[13]);
	return (__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("null")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("null")));
}

}
