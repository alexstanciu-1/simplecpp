#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_scalar_expression_start_at_offset.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_call_expression_with_argument_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_scalar_expression_start_at_offset.hpp"
#include "__callable/__latency_fn_parser_cursor_peek.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_function_parameter.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_mark_expression_node_flag.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_function_parameter_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_parameter_default_literal_if_present.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_flag_by_reference_parameter_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_mark_expression_node_flag.hpp"
#include "__callable/__latency_fn_frontend_model_builder_retag_expression_node_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_scalar_expression_start_at_offset(shared_p<PhsParserState> state, const string_t& source, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_scalar_expression_start_at_offset", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[64]);
	return (((((((((((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_number(), string_t("")) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_string_literal(), string_t(""))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("$"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("-"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("!"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t("("))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_identifier(), string_t("true"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_identifier(), string_t("false"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_identifier(), string_t("null"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_keyword(), string_t("true"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_keyword(), string_t("false"))) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_keyword(), string_t("null")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_call_expression_with_argument_list(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_call_expression_with_argument_list", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[65]);
	if (static_cast<bool>(((!__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(0), __latency_fn_token_kinds_identifier(), string_t(""))) || (!__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("(")))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(2));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t(")"))))) {
		return bool_t(static_cast<bool_t>(true));
	}
	while (static_cast<bool>(php::condition_truthy(static_cast<bool_t>(true)))) {
		if (static_cast<bool>((!__latency_fn_frontend_model_builder_at_scalar_expression_start_at_offset(state, source, offset)))) {
			return bool_t(static_cast<bool_t>(false));
		}
		offset = (offset + static_cast<int_t<> >(1));
		while (static_cast<bool>(((!__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t(")"))) && (!__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t(",")))))) {
			TokenRow token = __latency_fn_parser_cursor_peek(state->tokens, state->cursor, offset);
			if (static_cast<bool>(php::identical(cast<int_t<>>(token->kind_id), cast<int_t<>>(__latency_fn_token_kinds_eof())))) {
				return bool_t(static_cast<bool_t>(false));
			}
			offset = (offset + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, offset, __latency_fn_token_kinds_symbol(), string_t(")"))))) {
			return bool_t(static_cast<bool_t>(true));
		}
		offset = (offset + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_function_parameter(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_function_parameter", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[66]);
	return ((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(0), __latency_fn_token_kinds_identifier(), string_t("")) && (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("$")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("&")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_symbol(), string_t("$"))))) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_mark_expression_node_flag(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, int_t<std::uint16_t> flagId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::mark_expression_node_flag", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[67]);
	FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(node->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
		return;
	}
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, node->payload_row_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(expression->payload_id), static_cast<int_t<> >(0)))) {
		return;
	}
	expression->flags = flagId;
	node->flags = flagId;
	__latency_fn_frontend_model_tables_update_expression_payload(model, expression, counters);
	__latency_fn_frontend_model_tables_update_node(model, node, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_function_parameter_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t>& parameterTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_function_parameter_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[68]);
	parameterTypeRefId = __latency_fn_structure_row_ids_none_id();
	int_t<std::uint32_t> parameterNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(0), __latency_fn_token_kinds_identifier(), string_t("")) && (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("$")) || (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("&")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_symbol(), string_t("$"))))))) {
		TokenRow parameterTypeToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
		parameterTypeRefId = __latency_fn_frontend_model_builder_source_type_ref_id_for_name(__latency_fn_phs_tokenizer_token_text(source, parameterTypeToken));
		if (static_cast<bool>(php::identical(cast<int_t<>>(parameterTypeRefId), static_cast<int_t<> >(0)))) {
			__latency_fn_parser_state_diagnostic(state);
		}
		bool_t isByReference = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("&"))))) {
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("&"));
			isByReference = bool_t(static_cast<bool_t>(true));
		}
		parameterNodeId = __latency_fn_frontend_model_builder_append_variable_expression_with_type(state, source, model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(parameterTypeRefId), counters);
		if (static_cast<bool>(php::condition_truthy(isByReference))) {
			__latency_fn_frontend_model_builder_mark_expression_node_flag(model, cast<int_t<std::uint32_t>>(parameterNodeId), __latency_fn_frontend_model_builder_expression_flag_by_reference_parameter_id(), counters);
		}
		__latency_fn_frontend_model_builder_append_parameter_default_literal_if_present(state, source, model, cast<int_t<std::uint32_t>>(parameterNodeId), cast<int_t<std::uint32_t>>(parameterTypeRefId), counters);
		return cast<int_t<std::uint32_t>>(parameterNodeId);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$"))))) {
		parameterNodeId = __latency_fn_frontend_model_builder_append_variable_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), counters);
		TokenRow parameterTypeToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
		parameterTypeRefId = __latency_fn_frontend_model_builder_source_type_ref_id_for_name(__latency_fn_phs_tokenizer_token_text(source, parameterTypeToken));
		if (static_cast<bool>(php::identical(cast<int_t<>>(parameterTypeRefId), static_cast<int_t<> >(0)))) {
			__latency_fn_parser_state_diagnostic(state);
		}
		__latency_fn_frontend_model_builder_retag_expression_node_type(model, cast<int_t<std::uint32_t>>(parameterNodeId), cast<int_t<std::uint32_t>>(parameterTypeRefId), counters);
		__latency_fn_frontend_model_builder_append_parameter_default_literal_if_present(state, source, model, cast<int_t<std::uint32_t>>(parameterNodeId), cast<int_t<std::uint32_t>>(parameterTypeRefId), counters);
		return cast<int_t<std::uint32_t>>(parameterNodeId);
	}
	__latency_fn_parser_state_diagnostic(state);
	return __latency_fn_structure_row_ids_none_id();
}

}
