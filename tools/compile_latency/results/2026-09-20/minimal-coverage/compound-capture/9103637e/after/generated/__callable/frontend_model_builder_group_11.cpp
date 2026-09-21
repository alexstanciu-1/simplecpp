#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_empty_initializer_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_local_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_retag_expression_node_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id.hpp"
#include "__callable/__latency_fn_parser_cursor_peek.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_parser_state_record_local_type.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_const_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_const_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_flag_const_binding_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_parser_state_record_local_type.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_from_existing_node_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_compound_assignment_operator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_assignment_statement_with_terminator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_retag_expression_node_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_flag_append_target_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_parser_cursor_peek.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_parser_state_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_parser_state_record_local_type.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_local_declaration_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_local_declaration_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[102]);
	TokenRow startToken = __latency_fn_parser_cursor_peek(state->tokens, state->cursor, static_cast<int_t<> >(0));
	TokenRow targetNameToken = __latency_fn_parser_cursor_peek(state->tokens, state->cursor, static_cast<int_t<> >(1));
	int_t<std::uint32_t> targetNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_variable_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), counters));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_type_ref_id(state, source));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("="));
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0)))) {
		__latency_fn_parser_state_diagnostic(state);
	}
	__latency_fn_frontend_model_builder_retag_expression_node_type(model, cast<int_t<std::uint32_t>>(targetNodeId), cast<int_t<std::uint32_t>>(typeRefId), counters);
	__latency_fn_parser_state_record_local_type(state, __latency_fn_phs_tokenizer_token_text(source, targetNameToken), typeRefId);
	int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("["))))) {
		valueNodeId = __latency_fn_frontend_model_builder_append_empty_initializer_expression_with_type(state, source, model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(typeRefId), counters);
	}
	else {
		valueNodeId = __latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(typeRefId)), counters);
	}
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(startToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(semicolonToken), counters));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id(), .target_node_id = targetNodeId, .value_node_id = valueNodeId, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = statementRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = targetNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = statementRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, targetNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), valueNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, valueNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_const_declaration_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_const_declaration_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[103]);
	TokenRow startToken = __latency_fn_frontend_model_builder_expect_const_keyword(state, source);
	TokenRow targetNameToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("="));
	__latency_fn_parser_state_diagnostic(state);
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_refs_unknown_id());
	int_t<std::uint32_t> targetNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type(state, source, model, __latency_fn_structure_row_ids_none_id(), targetNameToken, cast<int_t<std::uint32_t>>(typeRefId), counters));
	int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(typeRefId), counters));
	__latency_fn_parser_state_record_local_type(state, __latency_fn_phs_tokenizer_token_text(source, targetNameToken), typeRefId);
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(startToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(semicolonToken), counters));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .target_node_id = targetNodeId, .value_node_id = valueNodeId, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = statementRangeId, .flags = __latency_fn_frontend_model_builder_statement_flag_const_binding_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = targetNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = statementRangeId, .flags = __latency_fn_frontend_model_builder_statement_flag_const_binding_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, targetNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), valueNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, valueNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_assignment_statement_with_terminator(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, const string_t& terminator, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_assignment_statement_with_terminator", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[104]);
	TokenRow startToken = __latency_fn_parser_cursor_peek(state->tokens, state->cursor, static_cast<int_t<> >(0));
	TokenRow targetNameToken = __latency_fn_parser_cursor_peek(state->tokens, state->cursor, static_cast<int_t<> >(1));
	string_t targetName = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, targetNameToken));
	int_t<std::uint32_t> knownTargetTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_parser_state_local_type_ref_id_for_name(state, targetName));
	int_t<std::uint32_t> targetNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_variable_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), counters));
	int_t<std::uint32_t> assignmentTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, cast<int_t<std::uint32_t>>(targetNodeId), counters));
	int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> statementFlags = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("["))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("["));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("]"));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("="));
		statementFlags = __latency_fn_frontend_model_builder_statement_flag_append_target_id();
		__latency_fn_parser_state_diagnostic(state);
		valueNodeId = __latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters);
	}
	else {
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("="))))) {
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("="));
			valueNodeId = __latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(assignmentTypeRefId), counters);
			int_t<std::uint32_t> valueTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, cast<int_t<std::uint32_t>>(valueNodeId), counters));
			if (static_cast<bool>((php::identical(cast<int_t<>>(knownTargetTypeRefId), static_cast<int_t<> >(0)) && (cast<int_t<>>(valueTypeRefId) > static_cast<int_t<> >(0))))) {
				assignmentTypeRefId = cast<int_t<std::uint32_t>>(valueTypeRefId);
				__latency_fn_frontend_model_builder_retag_expression_node_type(model, cast<int_t<std::uint32_t>>(targetNodeId), cast<int_t<std::uint32_t>>(assignmentTypeRefId), counters);
			}
			if (static_cast<bool>((php::not_identical(targetName, string_t("")) && (cast<int_t<>>(assignmentTypeRefId) > static_cast<int_t<> >(0))))) {
				__latency_fn_parser_state_record_local_type(state, targetName, assignmentTypeRefId);
			}
		}
		else {
			int_t<std::uint16_t> operatorId = required_cast<int_t<std::uint16_t>>(__latency_fn_frontend_model_builder_consume_compound_assignment_operator(state, source, cast<int_t<std::uint32_t>>(assignmentTypeRefId)));
			int_t<std::uint32_t> rightNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(assignmentTypeRefId), counters));
			int_t<std::uint32_t> leftNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_variable_expression_from_existing_node_with_type(model, cast<int_t<std::uint32_t>>(targetNodeId), __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(assignmentTypeRefId), counters));
			valueNodeId = __latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type(model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(leftNodeId), cast<int_t<std::uint32_t>>(rightNodeId), cast<int_t<std::uint32_t>>(assignmentTypeRefId), cast<int_t<std::uint16_t>>(operatorId), counters);
			if (static_cast<bool>(php::identical(cast<int_t<>>(valueNodeId), static_cast<int_t<> >(0)))) {
				__latency_fn_parser_state_diagnostic(state);
			}
		}
	}
	TokenRow terminatorToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), terminator);
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(startToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(terminatorToken), counters));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .target_node_id = targetNodeId, .value_node_id = valueNodeId, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = statementRangeId, .flags = statementFlags};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = targetNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = statementRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, targetNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), valueNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, valueNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}
