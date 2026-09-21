#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_implicit_return_after_statement_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close_with_implicit_return.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_integer_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_bool_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_bool_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_bool_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_return_literal_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_node_is_return.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_implicit_return_after_statement_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_body_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_eof_with_implicit_return.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_parsed_statement_tail_or.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_function_body.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_record_function_parameter_local_types.hpp"
#include "__callable/__latency_fn_parser_state_clear_local_types.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_text.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_record_function_parameter_local_types.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_parser_state_record_local_type.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_statement_list_until_close_with_implicit_return(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, bool_t allowNestedIf, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_statement_list_until_close_with_implicit_return", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[127]);
	int_t<std::uint32_t> firstStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_close(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), bool_t(allowNestedIf), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
	TokenRow currentToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	__latency_fn_frontend_model_builder_append_implicit_return_after_statement_list(state, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(firstStatementNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), cast<int_t<>>(currentToken->start_offset), counters);
	return cast<int_t<std::uint32_t>>(firstStatementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_synthetic_literal_expression(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> sourceRangeId, int_t<std::uint32_t> typeRefId, int_t<> value, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_synthetic_literal_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[128]);
	FrontendLiteralPayloadRow literalPayload = FrontendLiteralPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .literal_kind_id = __latency_fn_frontend_model_builder_literal_kind_integer_id(), .type_ref_id = typeRefId, .numeric_payload = __latency_fn_structure_row_ids_int32_from_int(value), .literal_status_id = __latency_fn_frontend_model_builder_literal_status_ready_id(), .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> literalPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_literal_payload(model, literalPayload, counters));
	FrontendNodeRow literalNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_literal_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_literal_id(), .payload_row_id = literalPayloadId, .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, literalNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_synthetic_bool_literal_expression(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> sourceRangeId, bool_t value, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_synthetic_bool_literal_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[129]);
	int_t<> numericPayload = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(value))) {
		numericPayload = static_cast<int_t<> >(1);
	}
	FrontendLiteralPayloadRow literalPayload = FrontendLiteralPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .literal_kind_id = __latency_fn_frontend_model_builder_literal_kind_bool_id(), .type_ref_id = __latency_fn_frontend_model_builder_primitive_bool_type_ref_id(), .numeric_payload = __latency_fn_structure_row_ids_int32_from_int(numericPayload), .literal_status_id = __latency_fn_frontend_model_builder_literal_status_ready_id(), .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> literalPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_literal_payload(model, literalPayload, counters));
	FrontendNodeRow literalNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_literal_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_literal_id(), .payload_row_id = literalPayloadId, .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, literalNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_synthetic_return_literal_statement(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> sourceRangeId, int_t<std::uint32_t> typeRefId, int_t<> value, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_synthetic_return_literal_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[130]);
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_return_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = __latency_fn_structure_row_ids_none_id(), .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_return_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	int_t<std::uint32_t> literalNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_synthetic_literal_expression(model, cast<int_t<std::uint32_t>>(statementNodeId), cast<int_t<std::uint32_t>>(sourceRangeId), cast<int_t<std::uint32_t>>(typeRefId), value, counters));
	statementPayload = __latency_fn_frontend_model_tables_statement_by_id(model, statementPayloadId, counters);
	statementPayload->value_node_id = literalNodeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, statementPayload, counters);
	statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	statementNode->first_child_node_id = literalNodeId;
	__latency_fn_frontend_model_tables_update_node(model, statementNode, counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_statement_node_is_return(shared_p<FrontendModel> model, int_t<std::uint32_t> statementNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::statement_node_is_return", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[131]);
	FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	return (php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id())));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_statement_list_until_eof_with_implicit_return(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_statement_list_until_eof_with_implicit_return", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[132]);
	int_t<std::uint32_t> firstStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> previousStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	__latency_fn_parser_state_skip_comments(state);
	while (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_eof(), string_t(""))))) {
		int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_body_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
		if (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
			if (static_cast<bool>(php::identical(cast<int_t<>>(firstStatementNodeId), static_cast<int_t<> >(0)))) {
				firstStatementNodeId = cast<int_t<std::uint32_t>>(statementNodeId);
			}
			if (static_cast<bool>((cast<int_t<>>(previousStatementNodeId) > static_cast<int_t<> >(0)))) {
				FrontendNodeRow previousNode = __latency_fn_frontend_model_tables_node_by_id(model, previousStatementNodeId, counters);
				__latency_fn_frontend_model_tables_patch_node_links(model, previousStatementNodeId, previousNode->parent_node_id, previousNode->first_child_node_id, statementNodeId, counters);
			}
			previousStatementNodeId = __latency_fn_parser_state_parsed_statement_tail_or(state, statementNodeId);
		}
		else {
			break;
		}
		__latency_fn_parser_state_skip_comments(state);
	}
	__latency_fn_frontend_model_builder_append_implicit_return_after_statement_list(state, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(firstStatementNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), cast<int_t<>>(state->source_length), counters);
	return cast<int_t<std::uint32_t>>(firstStatementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_function_body(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> declarationNodeId, int_t<std::uint32_t> returnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_function_body", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[133]);
	__latency_fn_parser_state_clear_local_types(state);
	__latency_fn_frontend_model_builder_record_function_parameter_local_types(state, source, model, cast<int_t<std::uint32_t>>(declarationNodeId), counters);
	int_t<std::uint32_t> firstStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_close(state, source, model, cast<int_t<std::uint32_t>>(declarationNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(returnTypeRefId), counters));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("}"));
	return cast<int_t<std::uint32_t>>(firstStatementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_record_function_parameter_local_types(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> declarationNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::record_function_parameter_local_types", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[134]);
	FrontendNodeRow declarationNode = __latency_fn_frontend_model_tables_node_by_id(model, declarationNodeId, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(declarationNode->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(declarationNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id()))))) {
		return;
	}
	FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationNode->payload_row_id, counters);
	int_t<std::uint32_t> parameterNodeId = required_cast<int_t<std::uint32_t>>(declaration->parameter_list_node_id);
	while (static_cast<bool>((cast<int_t<>>(parameterNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow parameterNode = __latency_fn_frontend_model_tables_node_by_id(model, parameterNodeId, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(parameterNode->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(parameterNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
			return;
		}
		string_t parameterName = required_cast<string_t>(__latency_fn_frontend_body_summaries_variable_name_text(model, source, parameterNode, counters));
		int_t<std::uint32_t> parameterTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, parameterNode->node_id, counters));
		if (static_cast<bool>((php::not_identical(parameterName, string_t("")) && (cast<int_t<>>(parameterTypeRefId) > static_cast<int_t<> >(0))))) {
			__latency_fn_parser_state_record_local_type(state, parameterName, parameterTypeRefId);
		}
		parameterNodeId = parameterNode->next_sibling_node_id;
		if (static_cast<bool>((cast<int_t<>>(parameterNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow nextParameterNode = __latency_fn_frontend_model_tables_node_by_id(model, parameterNodeId, counters);
			if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(nextParameterNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
				return;
			}
		}
	}
}

}
