#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_literal_expression_with_numeric_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_parameter_default_literal_if_present.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_plus_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_plus_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_minus_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_minus_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_generated_binary_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_id_for_symbol_and_type_refs.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_primary_expression.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_index_access_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_index_access_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_empty_initializer_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_empty_initializer_id.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_append_parameter_default_literal_if_present(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parameterNodeId, int_t<std::uint32_t> parameterTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_parameter_default_literal_if_present", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[69]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(parameterNodeId), static_cast<int_t<> >(0)) || (!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("=")))))) {
		return;
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("="));
	int_t<std::uint32_t> defaultNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_literal_expression_with_numeric_type(state, source, model, cast<int_t<std::uint32_t>>(parameterNodeId), cast<int_t<std::uint32_t>>(parameterTypeRefId), counters));
	if (static_cast<bool>(php::identical(cast<int_t<>>(defaultNodeId), static_cast<int_t<> >(0)))) {
		return;
	}
	FrontendNodeRow parameterNode = __latency_fn_frontend_model_tables_node_by_id(model, parameterNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, parameterNodeId, parameterNode->parent_node_id, defaultNodeId, parameterNode->next_sibling_node_id, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_binary_plus_expression_from_left_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> leftNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_binary_plus_expression_from_left_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[70]);
	return __latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(leftNodeId), cast<int_t<std::uint32_t>>(inferredTypeRefId), __latency_fn_frontend_model_builder_operator_plus_id(), string_t("+"), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_binary_minus_expression_from_left_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> leftNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_binary_minus_expression_from_left_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[71]);
	return __latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(leftNodeId), cast<int_t<std::uint32_t>>(inferredTypeRefId), __latency_fn_frontend_model_builder_operator_minus_id(), string_t("-"), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_generated_binary_expression_from_left_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> leftNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_generated_binary_expression_from_left_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[72]);
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(""))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)));
	TokenRow operatorToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	string_t operatorSymbol = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, operatorToken));
	int_t<std::uint16_t> operatorId = required_cast<int_t<std::uint16_t>>(__latency_fn_semantic_operator_lookup_operator_id_for_symbol_and_type_refs(operatorSymbol, typeRefId, typeRefId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(operatorId), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	return __latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(leftNodeId), cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::uint16_t>>(operatorId), operatorSymbol, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_binary_expression_from_left_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> leftNodeId, int_t<std::uint32_t> inferredTypeRefId, int_t<std::uint16_t> operatorId, const string_t& operatorSymbol, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_binary_expression_from_left_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[73]);
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), operatorSymbol);
	int_t<std::uint32_t> rightNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_primary_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)), counters));
	return __latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type(model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(leftNodeId), cast<int_t<std::uint32_t>>(rightNodeId), __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)), cast<int_t<std::uint16_t>>(operatorId), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> leftNodeId, int_t<std::uint32_t> rightNodeId, int_t<std::uint32_t> inferredTypeRefId, int_t<std::uint16_t> operatorId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_binary_expression_from_nodes_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[74]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(leftNodeId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(rightNodeId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(operatorId), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, leftNodeId, counters);
	FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, rightNodeId, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(leftNode->node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(rightNode->node_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	SourceRangeRow leftRange = __latency_fn_frontend_model_tables_source_range_by_id(model, leftNode->source_range_id, counters);
	SourceRangeRow rightRange = __latency_fn_frontend_model_tables_source_range_by_id(model, rightNode->source_range_id, counters);
	int_t<std::uint32_t> binaryRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(leftRange->start_offset), (cast<int_t<>>(rightRange->start_offset) + cast<int_t<>>(rightRange->length)), counters));
	FrontendExpressionPayloadRow binaryPayload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_binary_id(), .operator_id = operatorId, .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = __latency_fn_structure_row_ids_none_id(), .left_node_id = leftNodeId, .right_node_id = rightNodeId, .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)), .source_range_id = binaryRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> binaryPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, binaryPayload, counters));
	FrontendNodeRow binaryNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = leftNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_binary_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = binaryPayloadId, .source_range_id = binaryRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> binaryNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, binaryNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, leftNodeId, binaryNodeId, __latency_fn_structure_row_ids_none_id(), rightNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, rightNodeId, binaryNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(binaryNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_index_access_expression_from_nodes_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> subjectNodeId, int_t<std::uint32_t> indexNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_index_access_expression_from_nodes_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[75]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(subjectNodeId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(indexNodeId), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	FrontendNodeRow subjectNode = __latency_fn_frontend_model_tables_node_by_id(model, subjectNodeId, counters);
	FrontendNodeRow indexNode = __latency_fn_frontend_model_tables_node_by_id(model, indexNodeId, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(subjectNode->node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(indexNode->node_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	SourceRangeRow subjectRange = __latency_fn_frontend_model_tables_source_range_by_id(model, subjectNode->source_range_id, counters);
	SourceRangeRow indexRange = __latency_fn_frontend_model_tables_source_range_by_id(model, indexNode->source_range_id, counters);
	int_t<std::uint32_t> rangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(subjectRange->start_offset), ((cast<int_t<>>(indexRange->start_offset) + cast<int_t<>>(indexRange->length)) + static_cast<int_t<> >(1)), counters));
	FrontendExpressionPayloadRow payload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_index_access_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = __latency_fn_structure_row_ids_none_id(), .left_node_id = subjectNodeId, .right_node_id = indexNodeId, .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = inferredTypeRefId, .source_range_id = rangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> payloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, payload, counters));
	FrontendNodeRow node = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = subjectNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_index_access_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = payloadId, .source_range_id = rangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> nodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, node, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, subjectNodeId, nodeId, __latency_fn_structure_row_ids_none_id(), indexNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, indexNodeId, nodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(nodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_empty_initializer_expression_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> typeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_empty_initializer_expression_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[76]);
	TokenRow openToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("["));
	TokenRow closeToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("]"));
	int_t<std::uint32_t> rangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(openToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(closeToken), counters));
	FrontendExpressionPayloadRow payload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_empty_initializer_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = __latency_fn_structure_row_ids_none_id(), .left_node_id = __latency_fn_structure_row_ids_none_id(), .right_node_id = __latency_fn_structure_row_ids_none_id(), .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = typeRefId, .source_range_id = rangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> payloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, payload, counters));
	FrontendNodeRow node = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_empty_initializer_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = payloadId, .source_range_id = rangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, node, counters);
}

}
