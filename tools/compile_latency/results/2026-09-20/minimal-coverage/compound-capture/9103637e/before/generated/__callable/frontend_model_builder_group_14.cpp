#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_assignment_statement_with_terminator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_for_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_local_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_list_last_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_call_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_qualified_zero_arg_call_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_return_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_at_bool_literal.hpp"
#include "__callable/__latency_fn_parser_state_at_null_literal.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_return_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_return_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_return_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_call_expression_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_call_expression_with_argument_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_qualified_zero_arg_call_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_call_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_expression_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_for_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_for_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[118]);
	TokenRow forToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("for"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
	int_t<std::uint32_t> initNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_local_declaration_statement(state, source, model, __latency_fn_structure_row_ids_none_id(), counters));
	int_t<std::uint32_t> conditionNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> updateNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_assignment_statement_with_terminator(state, source, model, __latency_fn_structure_row_ids_none_id(), string_t(")"), counters));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("{"));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_for_id(), .target_node_id = initNodeId, .value_node_id = updateNodeId, .condition_node_id = conditionNodeId, .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = initNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_for_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	int_t<std::uint32_t> firstBodyNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_close(state, source, model, cast<int_t<std::uint32_t>>(statementNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
	TokenRow closeBrace = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("}"));
	int_t<std::uint32_t> bodyLastNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_statement_list_last_node_id(model, cast<int_t<std::uint32_t>>(firstBodyNodeId), counters));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(forToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(closeBrace), counters));
	statementPayload = __latency_fn_frontend_model_tables_statement_by_id(model, statementPayloadId, counters);
	statementPayload->body_first_node_id = firstBodyNodeId;
	statementPayload->body_last_node_id = bodyLastNodeId;
	statementPayload->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, statementPayload, counters);
	statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	statementNode->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_node(model, statementNode, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, initNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), conditionNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, conditionNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), updateNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, updateNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), firstBodyNodeId, counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_return_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> returnNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_return_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[119]);
	if (static_cast<bool>((((((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_number(), string_t("")) || __latency_fn_parser_state_at_bool_literal(state, source)) || __latency_fn_parser_state_at_null_literal(state, source)) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$"))) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("-"))) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("!"))))) {
		int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, cast<int_t<std::uint32_t>>(returnNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
		int_t<std::uint32_t> valueTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, cast<int_t<std::uint32_t>>(valueNodeId), counters));
		if (static_cast<bool>((((cast<int_t<>>(expectedReturnTypeRefId) > static_cast<int_t<> >(0)) && (cast<int_t<>>(valueTypeRefId) > static_cast<int_t<> >(0))) && (!__latency_fn_type_refs_return_value_type_compatible_without_conversion(expectedReturnTypeRefId, valueTypeRefId))))) {
			__latency_fn_parser_state_diagnostic(state);
		}
		return cast<int_t<std::uint32_t>>(valueNodeId);
	}
	if (static_cast<bool>(((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("("))) || __latency_fn_frontend_model_builder_at_qualified_zero_arg_call_expression(state, source)))) {
		return __latency_fn_frontend_model_builder_append_call_expression(state, source, model, cast<int_t<std::uint32_t>>(returnNodeId), counters);
	}
	__latency_fn_parser_state_diagnostic(state);
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_return_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_return_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[120]);
	TokenRow returnToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("return"));
	FrontendStatementPayloadRow returnPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_return_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = __latency_fn_structure_row_ids_none_id(), .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> returnPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, returnPayload, counters));
	FrontendNodeRow returnNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_return_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = returnPayloadId, .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> returnNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, returnNode, counters));
	int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_return_expression(state, source, model, cast<int_t<std::uint32_t>>(returnNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> returnRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(returnToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(semicolonToken), counters));
	returnPayload = __latency_fn_frontend_model_tables_statement_by_id(model, returnPayloadId, counters);
	returnPayload->value_node_id = valueNodeId;
	returnPayload->source_range_id = returnRangeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, returnPayload, counters);
	returnNode = __latency_fn_frontend_model_tables_node_by_id(model, returnNodeId, counters);
	returnNode->first_child_node_id = valueNodeId;
	returnNode->source_range_id = returnRangeId;
	__latency_fn_frontend_model_tables_update_node(model, returnNode, counters);
	return cast<int_t<std::uint32_t>>(returnNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_call_expression_statement(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_call_expression_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[121]);
	return (__latency_fn_frontend_model_builder_at_call_expression_with_argument_list(state, source) || __latency_fn_frontend_model_builder_at_qualified_zero_arg_call_expression(state, source));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_expression_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_expression_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[122]);
	TokenRow startToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_expression_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = __latency_fn_structure_row_ids_none_id(), .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_expression_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_call_expression(state, source, model, cast<int_t<std::uint32_t>>(statementNodeId), counters));
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(startToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(semicolonToken), counters));
	statementPayload = __latency_fn_frontend_model_tables_statement_by_id(model, statementPayloadId, counters);
	statementPayload->value_node_id = valueNodeId;
	statementPayload->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, statementPayload, counters);
	statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	statementNode->first_child_node_id = valueNodeId;
	statementNode->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_node(model, statementNode, counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}
