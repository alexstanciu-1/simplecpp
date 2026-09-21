#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ParserDiagnosticTable.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_control_transfer_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_assignment_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_call_expression_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_const_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_echo_postfix_update_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_echo_prefix_update_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_local_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_node_is_synthetic_script_entry.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_assignment_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_body_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_const_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_control_transfer_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_postfix_update_statement_sequence.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_prefix_update_statement_sequence.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_expression_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_for_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_if_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_local_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_return_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_while_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_break_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_continue_id.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_clear_parsed_statement_tail.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_body_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_parsed_statement_tail_or.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_implicit_return_after_statement_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_return_literal_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_list_last_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_node_is_return.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_control_transfer_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, const string_t& keyword, int_t<std::uint16_t> statementKindId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_control_transfer_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[123]);
	TokenRow startToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), keyword);
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = statementKindId, .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = __latency_fn_structure_row_ids_none_id(), .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = statementKindId, .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = __latency_fn_structure_row_ids_none_id(), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	int_t<std::uint32_t> statementRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(startToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(semicolonToken), counters));
	statementPayload = __latency_fn_frontend_model_tables_statement_by_id(model, statementPayloadId, counters);
	statementPayload->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_statement_payload(model, statementPayload, counters);
	statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
	statementNode->source_range_id = statementRangeId;
	__latency_fn_frontend_model_tables_update_node(model, statementNode, counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_body_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, bool_t allowNestedIf, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_body_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[124]);
	__latency_fn_parser_state_clear_parsed_statement_tail(state);
	__latency_fn_parser_state_skip_comments(state);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_local_declaration(state, source)))) {
		return __latency_fn_frontend_model_builder_parse_local_declaration_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>((__latency_fn_frontend_model_builder_node_is_synthetic_script_entry(model, cast<int_t<std::uint32_t>>(parentNodeId), counters) && __latency_fn_frontend_model_builder_at_const_declaration_statement(state, source)))) {
		return __latency_fn_frontend_model_builder_parse_const_declaration_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_assignment_statement(state, source)))) {
		return __latency_fn_frontend_model_builder_parse_assignment_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_echo_prefix_update_statement(state, source)))) {
		return __latency_fn_frontend_model_builder_parse_echo_prefix_update_statement_sequence(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_echo_postfix_update_statement(state, source)))) {
		return __latency_fn_frontend_model_builder_parse_echo_postfix_update_statement_sequence(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("echo"))))) {
		return __latency_fn_frontend_model_builder_parse_echo_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>((allowNestedIf && __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("if"))))) {
		return __latency_fn_frontend_model_builder_parse_if_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters);
	}
	if (static_cast<bool>((allowNestedIf && __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("while"))))) {
		return __latency_fn_frontend_model_builder_parse_while_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters);
	}
	if (static_cast<bool>((allowNestedIf && __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("for"))))) {
		return __latency_fn_frontend_model_builder_parse_for_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("break"))))) {
		return __latency_fn_frontend_model_builder_parse_control_transfer_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), string_t("break"), __latency_fn_frontend_model_tables_row_kind_statement_break_id(), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("continue"))))) {
		return __latency_fn_frontend_model_builder_parse_control_transfer_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), string_t("continue"), __latency_fn_frontend_model_tables_row_kind_statement_continue_id(), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("return"))))) {
		return __latency_fn_frontend_model_builder_parse_return_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_call_expression_statement(state, source)))) {
		return __latency_fn_frontend_model_builder_parse_expression_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	__latency_fn_parser_state_diagnostic(state);
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_statement_list_until_close(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, bool_t allowNestedIf, int_t<std::uint32_t> expectedReturnTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_statement_list_until_close", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[125]);
	int_t<std::uint32_t> firstStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> previousStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	__latency_fn_parser_state_skip_comments(state);
	while (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("}"))))) {
		int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_body_statement(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), bool_t(allowNestedIf), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), counters));
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
	return cast<int_t<std::uint32_t>>(firstStatementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_append_implicit_return_after_statement_list(shared_p<PhsParserState> state, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, int_t<> implicitReturnOffset, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_implicit_return_after_statement_list", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[126]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(firstStatementNodeId), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(state->diagnostics->error_count), static_cast<int_t<> >(0))))) {
		return;
	}
	int_t<std::uint32_t> lastStatementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_statement_list_last_node_id(model, cast<int_t<std::uint32_t>>(firstStatementNodeId), counters));
	if (static_cast<bool>((php::identical(cast<int_t<>>(lastStatementNodeId), static_cast<int_t<> >(0)) || __latency_fn_frontend_model_builder_statement_node_is_return(model, cast<int_t<std::uint32_t>>(lastStatementNodeId), counters)))) {
		return;
	}
	int_t<std::uint32_t> implicitRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, implicitReturnOffset, implicitReturnOffset, counters));
	int_t<std::uint32_t> returnNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_synthetic_return_literal_statement(model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(implicitRangeId), cast<int_t<std::uint32_t>>(expectedReturnTypeRefId), static_cast<int_t<> >(0), counters));
	FrontendNodeRow lastNode = __latency_fn_frontend_model_tables_node_by_id(model, lastStatementNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, lastStatementNodeId, lastNode->parent_node_id, lastNode->first_child_node_id, returnNodeId, counters);
}

}
