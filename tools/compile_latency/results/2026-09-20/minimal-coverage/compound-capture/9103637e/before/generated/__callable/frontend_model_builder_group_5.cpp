#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_unary_minus_integer_literal_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_integer_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_unary_minus_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_unary_minus_local_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_minus_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_unary_minus_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_operand.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_operand.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_at_bool_literal.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_call_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_callee_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_call_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_unary_minus_integer_literal_expression_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> numericTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_unary_minus_integer_literal_expression_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[57]);
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(numericTypeRefId)));
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref(string_t("-"), typeRefId);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(operatorRow->arity), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(operatorRow->operator_id), cast<int_t<>>(__latency_fn_frontend_model_builder_operator_unary_minus_id()))))) {
		__latency_fn_parser_state_diagnostic(state);
		return __latency_fn_structure_row_ids_none_id();
	}
	TokenRow minusToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("-"));
	TokenRow literalToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_number(), string_t(""));
	string_t literalText = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, literalToken));
	if (static_cast<bool>(((php::str_contains(literalText, string_t(".")) || php::str_contains(literalText, string_t("e"))) || php::str_contains(literalText, string_t("E"))))) {
		__latency_fn_parser_state_diagnostic(state);
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<std::uint32_t> literalRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(minusToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(literalToken), counters));
	FrontendLiteralPayloadRow literalPayload = FrontendLiteralPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .literal_kind_id = __latency_fn_frontend_model_builder_literal_kind_integer_id(), .type_ref_id = typeRefId, .numeric_payload = __latency_fn_structure_row_ids_int32_from_int((-cast<int_t<>>(literalText))), .literal_status_id = __latency_fn_frontend_model_builder_literal_status_ready_id(), .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> literalPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_literal_payload(model, literalPayload, counters));
	FrontendNodeRow literalNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_literal_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_literal_id(), .payload_row_id = literalPayloadId, .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, literalNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_unary_minus_local_expression_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> numericTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_unary_minus_local_expression_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[58]);
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(numericTypeRefId)));
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref(string_t("-"), typeRefId);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(operatorRow->arity), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(operatorRow->operator_id), cast<int_t<>>(__latency_fn_frontend_model_builder_operator_unary_minus_id()))))) {
		__latency_fn_parser_state_diagnostic(state);
		return __latency_fn_structure_row_ids_none_id();
	}
	TokenRow minusToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("-"));
	int_t<std::uint32_t> zeroRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, minusToken, counters));
	int_t<std::uint32_t> zeroNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_synthetic_literal_expression(model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(zeroRangeId), cast<int_t<std::uint32_t>>(typeRefId), static_cast<int_t<> >(0), counters));
	int_t<std::uint32_t> variableNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_variable_expression_with_type(state, source, model, __latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(typeRefId), counters));
	return __latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type(model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(zeroNodeId), cast<int_t<std::uint32_t>>(variableNodeId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_frontend_model_builder_operator_minus_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_expression(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::consume_blocked_unary_logical_not_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[59]);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("!"));
	__latency_fn_parser_state_diagnostic(state);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("("))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
		__latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_operand(state, source);
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(")"));
		return __latency_fn_structure_row_ids_none_id();
	}
	__latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_operand(state, source);
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_operand(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::consume_blocked_unary_logical_not_operand", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[60]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("$"));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at_bool_literal(state, source)))) {
		if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("true")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("false"))))) {
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t(""));
		}
		else {
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
		}
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_number(), string_t(""))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_number(), string_t(""));
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_string_literal(), string_t(""))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_string_literal(), string_t(""));
		return;
	}
	__latency_fn_parser_state_diagnostic(state);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_call_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[61]);
	TokenRow calleeStartToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("\\"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("\\"));
	}
	TokenRow calleeLastToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	while (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("\\"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("\\"));
		calleeLastToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
	int_t<std::uint32_t> firstArgumentNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> previousArgumentNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<> argumentCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(")"))))) {
		while (static_cast<bool>(php::condition_truthy(static_cast<bool_t>(true)))) {
			int_t<std::uint32_t> argumentNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters));
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
			argumentCount = (argumentCount + static_cast<int_t<> >(1));
			if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(","))))) {
				__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(","));
			}
			else {
				break;
			}
		}
	}
	TokenRow closeCall = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(")"));
	int_t<std::uint32_t> calleeRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(calleeStartToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(calleeLastToken), counters));
	int_t<std::uint32_t> callRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(calleeStartToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(closeCall), counters));
	int_t<std::uint32_t> calleeNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_callee_name(model, cast<int_t<std::uint32_t>>(calleeRangeId), counters));
	FrontendExpressionPayloadRow callPayload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_call_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = calleeNameId, .left_node_id = __latency_fn_structure_row_ids_none_id(), .right_node_id = __latency_fn_structure_row_ids_none_id(), .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = firstArgumentNodeId, .argument_count = __latency_fn_structure_row_ids_uint16_from_int(argumentCount), .inferred_type_ref_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = callRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> callPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, callPayload, counters));
	FrontendNodeRow callNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_call_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = callPayloadId, .source_range_id = callRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> callNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, callNode, counters));
	if (static_cast<bool>((cast<int_t<>>(firstArgumentNodeId) > static_cast<int_t<> >(0)))) {
		int_t<std::uint32_t> argumentNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(firstArgumentNodeId));
		while (static_cast<bool>((cast<int_t<>>(argumentNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow argumentNode = __latency_fn_frontend_model_tables_node_by_id(model, argumentNodeId, counters);
			int_t<std::uint32_t> nextArgumentNodeId = required_cast<int_t<std::uint32_t>>(argumentNode->next_sibling_node_id);
			__latency_fn_frontend_model_tables_patch_node_links(model, argumentNodeId, callNodeId, __latency_fn_structure_row_ids_none_id(), nextArgumentNodeId, counters);
			argumentNodeId = cast<int_t<std::uint32_t>>(nextArgumentNodeId);
		}
		callNode = __latency_fn_frontend_model_tables_node_by_id(model, callNodeId, counters);
		callNode->first_child_node_id = firstArgumentNodeId;
		__latency_fn_frontend_model_tables_update_node(model, callNode, counters);
	}
	return cast<int_t<std::uint32_t>>(callNodeId);
}

}
