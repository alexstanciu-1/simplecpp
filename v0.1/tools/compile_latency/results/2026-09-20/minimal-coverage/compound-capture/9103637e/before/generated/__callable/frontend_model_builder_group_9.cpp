#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_is_synthetic_script_entry.hpp"
#include "__callable/__latency_fn_frontend_model_builder_node_is_synthetic_script_entry.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_bound_identifier_expression.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_compound_assignment_base_operator_at_offset.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_compound_assignment_operator_after_target.hpp"
#include "__callable/__latency_fn_frontend_model_builder_compound_assignment_base_operator_at_offset.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_assignment_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_compound_assignment_operator_after_target.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_double_symbol.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_double_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_prefix_update_variable_offset.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_echo_prefix_update_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_prefix_update_variable_offset.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_double_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_echo_postfix_update_statement.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_prefix_update_operator.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_node_is_synthetic_script_entry(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::node_is_synthetic_script_entry", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[86]);
	FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(node->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, node->payload_row_id, counters);
	return __latency_fn_frontend_model_builder_declaration_is_synthetic_script_entry(declaration);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_bound_identifier_expression(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_bound_identifier_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[87]);
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t(""))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("("))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	TokenRow nameToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	string_t nameText = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, nameToken));
	return bool_t((cast<int_t<>>(__latency_fn_parser_state_local_type_ref_id_for_name(state, nameText)) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
string_t __latency_fn_frontend_model_builder_compound_assignment_base_operator_at_offset(shared_p<PhsParserState> state, const string_t& source, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::compound_assignment_base_operator_at_offset", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[88]);
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("+=")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("+")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), string_t("=")))))) {
		return string_t("+");
	}
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("-=")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("-")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), string_t("=")))))) {
		return string_t("-");
	}
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("*=")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("*")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), string_t("=")))))) {
		return string_t("*");
	}
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("/=")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("/")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), string_t("=")))))) {
		return string_t("/");
	}
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("%=")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("%")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), string_t("=")))))) {
		return string_t("%");
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_compound_assignment_operator_after_target(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_compound_assignment_operator_after_target", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[89]);
	return bool_t(php::not_identical(__latency_fn_frontend_model_builder_compound_assignment_base_operator_at_offset(state, source, static_cast<int_t<> >(2)), string_t("")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_assignment_statement(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_assignment_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[90]);
	return ((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_identifier(), string_t(""))) && ((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_symbol(), string_t("=")) || ((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_symbol(), string_t("[")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(3), __latency_fn_token_kinds_symbol(), string_t("]"))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(4), __latency_fn_token_kinds_symbol(), string_t("=")))) || __latency_fn_frontend_model_builder_at_compound_assignment_operator_after_target(state, source)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_double_symbol(shared_p<PhsParserState> state, const string_t& source, int_t<> offset, const string_t& single, const string_t& combined) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_double_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[91]);
	return (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), combined) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), single) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (offset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_symbol(), single)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<> __latency_fn_frontend_model_builder_prefix_update_variable_offset(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::prefix_update_variable_offset", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[92]);
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("echo"))))) {
		return static_cast<int_t<> >(0);
	}
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("++")) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("--"))))) {
		return static_cast<int_t<> >(2);
	}
	if (static_cast<bool>((__latency_fn_frontend_model_builder_at_double_symbol(state, source, static_cast<int_t<> >(1), string_t("+"), string_t("++")) || __latency_fn_frontend_model_builder_at_double_symbol(state, source, static_cast<int_t<> >(1), string_t("-"), string_t("--"))))) {
		return static_cast<int_t<> >(3);
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_echo_prefix_update_statement(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_echo_prefix_update_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[93]);
	int_t<> variableOffset = required_cast<int_t<>>(__latency_fn_frontend_model_builder_prefix_update_variable_offset(state, source));
	return (((variableOffset > static_cast<int_t<> >(0)) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, variableOffset, __latency_fn_token_kinds_symbol(), string_t("$"))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, (variableOffset + static_cast<int_t<> >(1)), __latency_fn_token_kinds_identifier(), string_t("")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_echo_postfix_update_statement(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_echo_postfix_update_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[94]);
	return (((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("echo")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("$"))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_identifier(), string_t(""))) && (__latency_fn_frontend_model_builder_at_double_symbol(state, source, static_cast<int_t<> >(3), string_t("+"), string_t("++")) || __latency_fn_frontend_model_builder_at_double_symbol(state, source, static_cast<int_t<> >(3), string_t("-"), string_t("--"))));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
string_t __latency_fn_frontend_model_builder_consume_prefix_update_operator(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::consume_prefix_update_operator", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[95]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("++"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("++"));
		return string_t("+");
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("--"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("--"));
		return string_t("-");
	}
	if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("+")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("+"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("+"));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("+"));
		return string_t("+");
	}
	if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("-")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("-"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("-"));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("-"));
		return string_t("-");
	}
	__latency_fn_parser_state_diagnostic(state);
	return string_t("");
}

}
