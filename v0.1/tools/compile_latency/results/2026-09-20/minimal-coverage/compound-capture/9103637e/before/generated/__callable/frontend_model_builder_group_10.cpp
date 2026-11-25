#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_compound_assignment_base_operator_at_offset.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_compound_assignment_operator.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_id_for_symbol_and_type_refs.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_retag_expression_node_type.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_ternary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression_with_min_precedence.hpp"
#include "__callable/__latency_fn_frontend_model_builder_ternary_result_type_ref.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_ternary_result_type_ref.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_identifier_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_index_access_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_literal_expression_with_numeric_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_null_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_string_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_unary_minus_integer_literal_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_unary_minus_local_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_bound_identifier_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_primary_expression.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_at_bool_literal.hpp"
#include "__callable/__latency_fn_parser_state_at_null_literal.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_binary_operator_row_for_current.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_result_type_ref_for_left.hpp"
#include "__callable/__latency_fn_frontend_model_builder_operator_rhs_type_ref_for_left.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_expression_with_min_precedence.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_scalar_primary_expression.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_consume_compound_assignment_operator(shared_p<PhsParserState> state, const string_t& source, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::consume_compound_assignment_operator", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[96]);
	string_t operatorSymbol = required_cast<string_t>(__latency_fn_frontend_model_builder_compound_assignment_base_operator_at_offset(state, source, static_cast<int_t<> >(0)));
	if (static_cast<bool>(php::identical(operatorSymbol, string_t("")))) {
		__latency_fn_parser_state_diagnostic(state);
		return __latency_fn_structure_row_ids_none_kind_id();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), (cast<string_t>(operatorSymbol) + string_t("=")))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), (cast<string_t>(operatorSymbol) + string_t("=")));
	}
	else {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), operatorSymbol);
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("="));
	}
	int_t<std::uint16_t> operatorId = required_cast<int_t<std::uint16_t>>(__latency_fn_semantic_operator_lookup_operator_id_for_symbol_and_type_refs(operatorSymbol, __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(typeRefId)), __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(typeRefId))));
	if (static_cast<bool>(php::identical(cast<int_t<>>(operatorId), static_cast<int_t<> >(0)))) {
		__latency_fn_parser_state_diagnostic(state);
	}
	return cast<int_t<std::uint16_t>>(operatorId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_retag_expression_node_type(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, int_t<std::uint32_t> typeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::retag_expression_node_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[97]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0)))) {
		return;
	}
	FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
		return;
	}
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, node->payload_row_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(expression->payload_id), static_cast<int_t<> >(0)))) {
		return;
	}
	expression->inferred_type_ref_id = typeRefId;
	__latency_fn_frontend_model_tables_update_expression_payload(model, expression, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_scalar_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_scalar_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[98]);
	int_t<std::uint32_t> expressionTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(expectedTypeRefId)));
	int_t<std::uint32_t> conditionNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression_with_min_precedence(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), counters));
	if (static_cast<bool>((php::identical(cast<int_t<>>(conditionNodeId), static_cast<int_t<> >(0)) || (!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("?")))))) {
		return cast<int_t<std::uint32_t>>(conditionNodeId);
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("?"));
	int_t<std::uint32_t> thenNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(":"));
	int_t<std::uint32_t> elseNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters));
	int_t<std::uint32_t> ternaryTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_ternary_result_type_ref(model, cast<int_t<std::uint32_t>>(thenNodeId), cast<int_t<std::uint32_t>>(elseNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters));
	return __latency_fn_frontend_model_builder_append_ternary_expression_from_nodes_with_type(model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(conditionNodeId), cast<int_t<std::uint32_t>>(thenNodeId), cast<int_t<std::uint32_t>>(elseNodeId), cast<int_t<std::uint32_t>>(ternaryTypeRefId), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_ternary_result_type_ref(shared_p<FrontendModel> model, int_t<std::uint32_t> thenNodeId, int_t<std::uint32_t> elseNodeId, int_t<std::uint32_t> fallbackTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::ternary_result_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[99]);
	int_t<std::uint32_t> thenTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, cast<int_t<std::uint32_t>>(thenNodeId), counters));
	int_t<std::uint32_t> elseTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, cast<int_t<std::uint32_t>>(elseNodeId), counters));
	if (static_cast<bool>(((cast<int_t<>>(thenTypeRefId) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(thenTypeRefId), cast<int_t<>>(elseTypeRefId))))) {
		return cast<int_t<std::uint32_t>>(thenTypeRefId);
	}
	return __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(fallbackTypeRefId));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_scalar_primary_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_scalar_primary_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[100]);
	int_t<std::uint32_t> expressionTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(expectedTypeRefId)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("("))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
		int_t<std::uint32_t> innerNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters));
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(")"));
		return cast<int_t<std::uint32_t>>(innerNodeId);
	}
	if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("-")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_number(), string_t(""))))) {
		return __latency_fn_frontend_model_builder_append_unary_minus_integer_literal_expression_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters);
	}
	if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("-")) && __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_symbol(), string_t("$"))))) {
		return __latency_fn_frontend_model_builder_append_unary_minus_local_expression_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("!"))))) {
		return __latency_fn_frontend_model_builder_consume_blocked_unary_logical_not_expression(state, source);
	}
	if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_number(), string_t("")) || __latency_fn_parser_state_at_bool_literal(state, source)))) {
		return __latency_fn_frontend_model_builder_append_literal_expression_with_numeric_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at_null_literal(state, source)))) {
		return __latency_fn_frontend_model_builder_append_null_literal_expression(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_string_literal(), string_t(""))))) {
		return __latency_fn_frontend_model_builder_append_string_literal_expression(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), counters);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("$"))))) {
		int_t<std::uint32_t> subjectNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_variable_expression_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("["))))) {
			int_t<std::uint32_t> elementTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_refs_unknown_id());
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("["));
			__latency_fn_parser_state_diagnostic(state);
			int_t<std::uint32_t> indexNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression(state, source, model, __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_unknown_id(), counters));
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("]"));
			return __latency_fn_frontend_model_builder_append_index_access_expression_from_nodes_with_type(model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(subjectNodeId), cast<int_t<std::uint32_t>>(indexNodeId), cast<int_t<std::uint32_t>>(elementTypeRefId), counters);
		}
		return cast<int_t<std::uint32_t>>(subjectNodeId);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_bound_identifier_expression(state, source)))) {
		return __latency_fn_frontend_model_builder_append_identifier_expression_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters);
	}
	__latency_fn_parser_state_diagnostic(state);
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_scalar_expression_with_min_precedence(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> expectedTypeRefId, int_t<std::uint16_t> minPrecedence, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_scalar_expression_with_min_precedence", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[101]);
	int_t<std::uint32_t> expressionTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(expectedTypeRefId)));
	int_t<std::uint32_t> leftNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_primary_expression(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(expressionTypeRefId), counters));
	if (static_cast<bool>(php::identical(cast<int_t<>>(leftNodeId), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<std::uint32_t> leftTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, cast<int_t<std::uint32_t>>(leftNodeId), counters));
	while (static_cast<bool>(php::condition_truthy(static_cast<bool_t>(true)))) {
		shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_frontend_model_builder_binary_operator_row_for_current(state, source, cast<int_t<std::uint32_t>>(leftTypeRefId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0)) || (cast<int_t<>>(operatorRow->precedence) < cast<int_t<>>(minPrecedence))))) {
			return cast<int_t<std::uint32_t>>(leftNodeId);
		}
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), operatorRow->operator_symbol);
		int_t<std::uint32_t> rightTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_operator_rhs_type_ref_for_left(operatorRow, cast<int_t<std::uint32_t>>(leftTypeRefId)));
		int_t<std::uint32_t> resultTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_operator_result_type_ref_for_left(operatorRow, cast<int_t<std::uint32_t>>(leftTypeRefId)));
		int_t<std::uint32_t> rightNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_scalar_expression_with_min_precedence(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(rightTypeRefId), __latency_fn_structure_row_ids_uint16_from_int((cast<int_t<>>(operatorRow->precedence) + static_cast<int_t<> >(1))), counters));
		if (static_cast<bool>(php::identical(cast<int_t<>>(rightNodeId), static_cast<int_t<> >(0)))) {
			return cast<int_t<std::uint32_t>>(leftNodeId);
		}
		leftNodeId = __latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type(model, cast<int_t<std::uint32_t>>(parentNodeId), cast<int_t<std::uint32_t>>(leftNodeId), cast<int_t<std::uint32_t>>(rightNodeId), cast<int_t<std::uint32_t>>(resultTypeRefId), operatorRow->operator_id, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(leftNodeId), static_cast<int_t<> >(0)))) {
			return __latency_fn_structure_row_ids_none_id();
		}
		leftTypeRefId = cast<int_t<std::uint32_t>>(resultTypeRefId);
	}
	return cast<int_t<std::uint32_t>>(leftNodeId);
}

}
