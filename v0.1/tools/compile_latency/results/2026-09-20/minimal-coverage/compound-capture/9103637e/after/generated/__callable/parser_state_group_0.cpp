#include <scpp/lang/php.hpp>
#include "__types/ParserDiagnosticTable.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/parser_state.hpp"
#include "__callable/__latency_fn_parser_state_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_cursor_from_tokens.hpp"
#include "__callable/__latency_fn_parser_diagnostics_new_table.hpp"
#include "__callable/__latency_fn_parser_state_make.hpp"
#include "__callable/__latency_fn_parser_state_status_ready_id.hpp"
#include "__callable/__latency_fn_parser_state_clear_local_types.hpp"
#include "__callable/__latency_fn_parser_state_clear_parsed_statement_tail.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_parser_state_mark_parsed_statement_tail.hpp"
#include "__callable/__latency_fn_parser_state_parsed_statement_tail_or.hpp"
#include "__callable/__latency_fn_parser_state_record_local_type.hpp"
#include "__callable/__latency_fn_parser_state_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_diagnostics_append_expected_token.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_cursor_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_cursor_advance.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_token_kinds_comment.hpp"
#include "__callable/__latency_fn_parser_cursor_advance.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
bool_t parser_state::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == parser_state::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_parser_state_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("parser_state::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
shared_p<PhsParserState> __latency_fn_parser_state_make(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sourceBufferId, shared_p<TokenStream> tokens) {
	SCPP_CALL_DEPTH_GUARD("parser_state::make", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[1]);
	shared_p<PhsParserState> state = create<PhsParserState>();
	state->source_unit_id = sourceUnit->source_unit_id;
	state->source_buffer_id = sourceBufferId;
	state->source_length = sourceUnit->source_length;
	state->status_id = __latency_fn_parser_state_status_ready_id();
	state->tokens = tokens;
	state->cursor = __latency_fn_parser_cursor_from_tokens(tokens);
	state->diagnostics = __latency_fn_parser_diagnostics_new_table(static_cast<int_t<> >(1));
	return state;
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
void __latency_fn_parser_state_clear_local_types(shared_p<PhsParserState> state) {
	SCPP_CALL_DEPTH_GUARD("parser_state::clear_local_types", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[2]);
	vector_t<string_t> emptyNames = {};
	vector_t<int_t<>> emptyTypeRefIds = {};
	state->local_names = emptyNames;
	state->local_type_ref_ids = emptyTypeRefIds;
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
void __latency_fn_parser_state_clear_parsed_statement_tail(shared_p<PhsParserState> state) {
	SCPP_CALL_DEPTH_GUARD("parser_state::clear_parsed_statement_tail", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[3]);
	state->parsed_statement_tail_node_id = __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
void __latency_fn_parser_state_mark_parsed_statement_tail(shared_p<PhsParserState> state, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("parser_state::mark_parsed_statement_tail", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[4]);
	state->parsed_statement_tail_node_id = nodeId;
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_parser_state_parsed_statement_tail_or(shared_p<PhsParserState> state, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("parser_state::parsed_statement_tail_or", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[5]);
	if (static_cast<bool>((cast<int_t<>>(state->parsed_statement_tail_node_id) > static_cast<int_t<> >(0)))) {
		return state->parsed_statement_tail_node_id;
	}
	return cast<int_t<std::uint32_t>>(nodeId);
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
void __latency_fn_parser_state_record_local_type(shared_p<PhsParserState> state, const string_t& name, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("parser_state::record_local_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[6]);
	(void) state->local_names.append(name);
	{
	auto __latency_local_0 = cast<int_t<>>(typeRefId);
	(void) state->local_type_ref_ids.append(__latency_local_0);
	}
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_parser_state_local_type_ref_id_for_name(shared_p<PhsParserState> state, const string_t& name) {
	SCPP_CALL_DEPTH_GUARD("parser_state::local_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[7]);
	int_t<> index = required_cast<int_t<>>((php::count(state->local_names) - static_cast<int_t<> >(1)));
	while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
		if (static_cast<bool>(php::identical(state->local_names[index], name))) {
			return __latency_fn_structure_row_ids_uint32_from_int(state->local_type_ref_ids[index]);
		}
		index = (index - static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
void __latency_fn_parser_state_diagnostic(shared_p<PhsParserState> state) {
	SCPP_CALL_DEPTH_GUARD("parser_state::diagnostic", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[8]);
	TokenRow token = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	__latency_fn_parser_diagnostics_append_expected_token(state->diagnostics, state->source_unit_id, state->source_buffer_id, state->cursor, token);
	state->diagnostic_count = state->diagnostics->row_count;
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
bool_t __latency_fn_parser_state_at(shared_p<PhsParserState> state, const string_t& source, int_t<std::uint16_t> kindId, const string_t& literal) {
	SCPP_CALL_DEPTH_GUARD("parser_state::at", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[9]);
	return __latency_fn_parser_cursor_at(source, state->tokens, state->cursor, kindId, literal);
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
void __latency_fn_parser_state_skip_comments(shared_p<PhsParserState> state) {
	SCPP_CALL_DEPTH_GUARD("parser_state::skip_comments", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[10]);
	while (static_cast<bool>(php::identical(__latency_fn_parser_cursor_current(state->tokens, state->cursor)->kind_id, __latency_fn_token_kinds_comment()))) {
		state->cursor = __latency_fn_parser_cursor_advance(state->cursor);
	}
}

}

namespace scpp { extern const int __latency_lines_parser_state[]; }
namespace scpp {
TokenRow __latency_fn_parser_state_expect(shared_p<PhsParserState> state, const string_t& source, int_t<std::uint16_t> kindId, const string_t& literal) {
	SCPP_CALL_DEPTH_GUARD("parser_state::expect", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_state.phs", __latency_lines_parser_state[11]);
	TokenRow token = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, cast<int_t<std::uint16_t>>(kindId), literal)))) {
		__latency_fn_parser_state_diagnostic(state);
		return token;
	}
	state->cursor = __latency_fn_parser_cursor_advance(state->cursor);
	return token;
}

}
