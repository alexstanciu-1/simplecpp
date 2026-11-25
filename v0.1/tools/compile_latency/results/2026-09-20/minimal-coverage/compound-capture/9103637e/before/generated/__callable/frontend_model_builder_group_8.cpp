#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_ternary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_ternary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_binary_operator_row_for_current.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_result_type_ref_for_left.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_rhs_type_ref_for_left.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_local_declaration.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_const_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_const_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_const_declaration_statement.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_const_keyword.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_ternary_expression_from_nodes_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> conditionNodeId, int_t<std::uint32_t> thenNodeId, int_t<std::uint32_t> elseNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_ternary_expression_from_nodes_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[77]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(conditionNodeId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(thenNodeId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(elseNodeId), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, conditionNodeId, counters);
	FrontendNodeRow thenNode = __latency_fn_frontend_model_tables_node_by_id(model, thenNodeId, counters);
	FrontendNodeRow elseNode = __latency_fn_frontend_model_tables_node_by_id(model, elseNodeId, counters);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(conditionNode->node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(thenNode->node_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(elseNode->node_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	SourceRangeRow conditionRange = __latency_fn_frontend_model_tables_source_range_by_id(model, conditionNode->source_range_id, counters);
	SourceRangeRow elseRange = __latency_fn_frontend_model_tables_source_range_by_id(model, elseNode->source_range_id, counters);
	int_t<std::uint32_t> ternaryRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(conditionRange->start_offset), (cast<int_t<>>(elseRange->start_offset) + cast<int_t<>>(elseRange->length)), counters));
	FrontendExpressionPayloadRow ternaryPayload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_ternary_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = __latency_fn_structure_row_ids_none_id(), .left_node_id = conditionNodeId, .right_node_id = thenNodeId, .third_node_id = elseNodeId, .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)), .source_range_id = ternaryRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> ternaryPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, ternaryPayload, counters));
	FrontendNodeRow ternaryNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = conditionNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_ternary_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = ternaryPayloadId, .source_range_id = ternaryRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> ternaryNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, ternaryNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, conditionNodeId, ternaryNodeId, __latency_fn_structure_row_ids_none_id(), thenNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, thenNodeId, ternaryNodeId, __latency_fn_structure_row_ids_none_id(), elseNodeId, counters);
	__latency_fn_frontend_model_tables_patch_node_links(model, elseNodeId, ternaryNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	return cast<int_t<std::uint32_t>>(ternaryNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_frontend_model_builder_binary_operator_row_for_current(shared_p<PhsParserState> state, const string_t& source, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::binary_operator_row_for_current", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[78]);
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(""))))) {
		shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
		return empty;
	}
	TokenRow operatorToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	string_t operatorSymbol = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, operatorToken));
	return __latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs(operatorSymbol, typeRefId, typeRefId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_expression_type_ref_from_node(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expression_type_ref_from_node", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[79]);
	FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(node->node_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_type_refs_unknown_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(node->payload_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_payload_kind_literal_id())))) {
		FrontendLiteralPayloadRow literal = __latency_fn_frontend_model_tables_literal_by_id(model, node->payload_row_id, counters);
		return literal->type_ref_id;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(node->payload_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_payload_kind_expression_id())))) {
		FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, node->payload_row_id, counters);
		return expression->inferred_type_ref_id;
	}
	return __latency_fn_type_refs_unknown_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_operator_result_type_ref_for_left(shared_p<SemanticOperatorLookupRow> operatorRow, int_t<std::uint32_t> leftTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::operator_result_type_ref_for_left", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[80]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(operatorRow->lhs_type_ref_id)) && php::identical(cast<int_t<>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(leftTypeRefId)), cast<int_t<>>(operatorRow->lhs_type_ref_id))))) {
		return cast<int_t<std::uint32_t>>(leftTypeRefId);
	}
	return operatorRow->result_type_ref_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_operator_rhs_type_ref_for_left(shared_p<SemanticOperatorLookupRow> operatorRow, int_t<std::uint32_t> leftTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::operator_rhs_type_ref_for_left", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[81]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(operatorRow->rhs_type_ref_id), cast<int_t<>>(operatorRow->lhs_type_ref_id)) && php::identical(cast<int_t<>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(leftTypeRefId)), cast<int_t<>>(operatorRow->lhs_type_ref_id))))) {
		return cast<int_t<std::uint32_t>>(leftTypeRefId);
	}
	return operatorRow->rhs_type_ref_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_local_declaration(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_local_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[82]);
	if (static_cast<bool>((!((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_identifier(), string_t(""))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_identifier(), string_t("")))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(3), __latency_fn_token_kinds_symbol(), string_t("="))))) {
		return bool_t(static_cast<bool_t>(true));
	}
	return __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(3), __latency_fn_token_kinds_symbol(), string_t("<"));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_const_keyword(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_const_keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[83]);
	return (__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("const")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("const")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
TokenRow __latency_fn_frontend_model_builder_expect_const_keyword(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expect_const_keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[84]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("const"))))) {
		return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("const"));
	}
	return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t("const"));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_const_declaration_statement(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_const_declaration_statement", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[85]);
	return ((__latency_fn_frontend_model_builder_at_const_keyword(state, source) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_identifier(), string_t(""))) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(2), __latency_fn_token_kinds_symbol(), string_t("=")));
}

}
