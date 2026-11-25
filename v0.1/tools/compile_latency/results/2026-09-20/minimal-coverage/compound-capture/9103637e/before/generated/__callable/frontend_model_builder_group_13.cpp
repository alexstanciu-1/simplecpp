#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_optional_echo_trailing_string_argument.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_prefix_update_operator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_postfix_update_statement_sequence.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_argument_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_patch_echo_argument_parent_links.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_echo_id.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_list_last_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_if_statement.hpp"
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
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_while_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_list_last_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_echo_postfix_update_statement_sequence(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_echo_postfix_update_statement_sequence", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[113]);
	__latency_fn_parser_state_diagnostic(state);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("echo"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("$"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	__latency_fn_frontend_model_builder_consume_prefix_update_operator(state, source);
	__latency_fn_frontend_model_builder_consume_optional_echo_trailing_string_argument(state, source);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_echo_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_echo_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[114]);
	TokenRow echoToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("echo"));
	int_t<std::uint32_t> valueNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> argumentCount = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	__latency_fn_frontend_model_builder_parse_echo_argument_list(state, source, model, valueNodeId, argumentCount, counters);
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(echoToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(semicolonToken), counters));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_echo_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = valueNodeId, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = statementRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = valueNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_echo_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = statementRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	__latency_fn_frontend_model_builder_patch_echo_argument_parent_links(model, cast<int_t<std::uint32_t>>(statementNodeId), cast<int_t<std::uint32_t>>(valueNodeId), counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_statement_list_last_node_id(shared_p<FrontendModel> model, int_t<std::uint32_t> firstNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::statement_list_last_node_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[115]);
	int_t<std::uint32_t> lastNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> nodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(firstNodeId));
	while (static_cast<bool>((cast<int_t<>>(nodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(node->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		lastNodeId = node->node_id;
		nodeId = node->next_sibling_node_id;
	}
	return cast<int_t<std::uint32_t>>(lastNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_if_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_if_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[116]);
	TokenRow ifToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("if"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
	int_t<std::uint32_t> conditionNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(")"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("{"));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_if_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = __latency_fn_structure_row_ids_none_id(), .condition_node_id = conditionNodeId, .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = conditionNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_if_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	int_t<std::uint32_t> firstBodyNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_close(state, source, model, cast<int_t<std::uint32_t>>(statementNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
	TokenRow closeBrace = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("}"));
	int_t<std::uint32_t> bodyLastNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_statement_list_last_node_id(model, cast<int_t<std::uint32_t>>(firstBodyNodeId), counters));
	TokenRow lastToken = closeBrace;
	int_t<std::uint32_t> elseBodyFirstNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> elseBodyLastNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	__latency_fn_parser_state_skip_comments(state);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("else"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("else"));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("{"));
		elseBodyFirstNodeId = __latency_fn_frontend_model_builder_parse_statement_list_until_close(state, source, model, cast<int_t<std::uint32_t>>(statementNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters);
		elseBodyLastNodeId = __latency_fn_frontend_model_builder_statement_list_last_node_id(model, cast<int_t<std::uint32_t>>(elseBodyFirstNodeId), counters);
		lastToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("}"));
	}
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(ifToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(lastToken), counters));
	statementPayload = __latency_fn_frontend_model_tables_statement_by_id(model, statementPayloadId, counters);
	statementPayload->body_first_node_id = firstBodyNodeId;
	statementPayload->body_last_node_id = bodyLastNodeId;
	statementPayload->else_body_first_node_id = elseBodyFirstNodeId;
	statementPayload->else_body_last_node_id = elseBodyLastNodeId;
	statementPayload->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, statementPayload, counters);
	statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	statementNode->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_node(model, statementNode, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, conditionNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), firstBodyNodeId, counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_while_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_while_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[117]);
	TokenRow whileToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("while"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
	int_t<std::uint32_t> conditionNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(")"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("{"));
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_while_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = __latency_fn_structure_row_ids_none_id(), .condition_node_id = conditionNodeId, .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = conditionNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_while_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	int_t<std::uint32_t> firstBodyNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_close(state, source, model, cast<int_t<std::uint32_t>>(statementNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
	TokenRow closeBrace = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("}"));
	int_t<std::uint32_t> bodyLastNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_statement_list_last_node_id(model, cast<int_t<std::uint32_t>>(firstBodyNodeId), counters));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(whileToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(closeBrace), counters));
	statementPayload = __latency_fn_frontend_model_tables_statement_by_id(model, statementPayloadId, counters);
	statementPayload->body_first_node_id = firstBodyNodeId;
	statementPayload->body_last_node_id = bodyLastNodeId;
	statementPayload->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, statementPayload, counters);
	statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	statementNode->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_node(model, statementNode, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, conditionNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), firstBodyNodeId, counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}
