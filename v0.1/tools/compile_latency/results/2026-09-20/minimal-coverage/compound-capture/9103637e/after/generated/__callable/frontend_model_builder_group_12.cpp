#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_assignment_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_assignment_statement_with_terminator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_assignment_statement_from_nodes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_echo_statement_from_value.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_echo_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_optional_echo_trailing_string_argument.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_call_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_call_expression_with_argument_list.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_argument_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_argument_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_argument_list.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_patch_echo_argument_parent_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_optional_echo_trailing_string_argument.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_prefix_update_operator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_echo_prefix_update_statement_sequence.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_assignment_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_assignment_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[105]);
	return __latency_fn_frontend_model_builder_parse_assignment_statement_with_terminator(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), string_t(";"), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_assignment_statement_from_nodes(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> targetNodeId, int_t<std::uint32_t> valueNodeId, int_t<std::uint32_t> sourceRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_assignment_statement_from_nodes", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[106]);
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .target_node_id = targetNodeId, .value_node_id = valueNodeId, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = targetNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, targetNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), valueNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, valueNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_echo_statement_from_value(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> valueNodeId, int_t<std::uint32_t> sourceRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_echo_statement_from_value", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[107]);
	FrontendStatementPayloadRow statementPayload = FrontendStatementPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_echo_id(), .target_node_id = __latency_fn_structure_row_ids_none_id(), .value_node_id = valueNodeId, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_statement_payload(model, statementPayload, counters));
	FrontendNodeRow statementNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = valueNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_statement_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_echo_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_statement_id(), .payload_row_id = statementPayloadId, .source_range_id = sourceRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, statementNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, valueNodeId, statementNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(statementNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_consume_optional_echo_trailing_string_argument(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::consume_optional_echo_trailing_string_argument", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[108]);
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(","))))) {
		return;
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(","));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_string_literal(), string_t(""))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_string_literal(), string_t(""));
		return;
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("\""));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("\""));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_echo_argument_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_echo_argument_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[109]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_call_expression_with_argument_list(state, source)))) {
		return __latency_fn_frontend_model_builder_append_call_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), counters);
	}
	return __latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_parse_echo_argument_list(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t>& firstArgumentNodeId, int_t<std::uint16_t>& argumentCount, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_echo_argument_list", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[110]);
	int_t<std::uint32_t> previousArgumentNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(php::condition_truthy(static_cast<bool_t>(true)))) {
		int_t<std::uint32_t> argumentNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_echo_argument_expression(state, source, model, counters));
		if (static_cast<bool>(php::identical(cast<int_t<>>(argumentNodeId), static_cast<int_t<> >(0)))) {
			break;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(firstArgumentNodeId), static_cast<int_t<> >(0)))) {
			firstArgumentNodeId = cast<int_t<std::uint32_t>>(argumentNodeId);
		}
		if (static_cast<bool>((cast<int_t<>>(previousArgumentNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow previousArgumentNode = __latency_fn_frontend_model_tables_node_by_id(model, previousArgumentNodeId, counters);
			__latency_fn_frontend_model_tables_patch_node_links(model, previousArgumentNodeId, previousArgumentNode->parent_node_id, previousArgumentNode->first_child_node_id, argumentNodeId, counters);
		}
		previousArgumentNodeId = cast<int_t<std::uint32_t>>(argumentNodeId);
		count = (count + static_cast<int_t<> >(1));
		if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(","))))) {
			break;
		}
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(","));
	}
	argumentCount = __latency_fn_structure_row_ids_uint16_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_patch_echo_argument_parent_links(shared_p<FrontendModel> model, int_t<std::uint32_t> echoNodeId, int_t<std::uint32_t> firstArgumentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::patch_echo_argument_parent_links", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[111]);
	int_t<std::uint32_t> argumentNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(firstArgumentNodeId));
	while (static_cast<bool>((cast<int_t<>>(argumentNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow argumentNode = __latency_fn_frontend_model_tables_node_by_id(model, argumentNodeId, counters);
		int_t<std::uint32_t> nextArgumentNodeId = required_cast<int_t<std::uint32_t>>(argumentNode->next_sibling_node_id);
		__latency_fn_frontend_model_tables_patch_node_links(model, argumentNodeId, echoNodeId, argumentNode->first_child_node_id, nextArgumentNodeId, counters);
		argumentNodeId = cast<int_t<std::uint32_t>>(nextArgumentNodeId);
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_echo_prefix_update_statement_sequence(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_echo_prefix_update_statement_sequence", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[112]);
	__latency_fn_parser_state_diagnostic(state);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("echo"));
	__latency_fn_frontend_model_builder_consume_prefix_update_operator(state, source);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("$"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	__latency_fn_frontend_model_builder_consume_optional_echo_trailing_string_argument(state, source);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	return __latency_fn_structure_row_ids_none_id();
}

}
