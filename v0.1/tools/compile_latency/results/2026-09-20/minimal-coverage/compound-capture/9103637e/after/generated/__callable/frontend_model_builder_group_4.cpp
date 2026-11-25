#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_identifier_expression_with_type.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_from_existing_node_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_literal_expression_with_numeric_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_integer_literal_fits_type_ref.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_bool_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_floating_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_integer_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_numeric_type_ref_accepts_decimal_literal.hpp"
#include "__callable/__latency_fn_frontend_model_builder_numeric_type_ref_is_integer_literal_target.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_bool_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_int_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_at_bool_literal.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text_equals.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_string_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_string_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_runtime_string_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_null_literal_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_null_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_type_refs_null_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_identifier_expression_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_identifier_expression_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[52]);
	TokenRow nameToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	return __latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), nameToken, cast<int_t<std::uint32_t>>(inferredTypeRefId), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_variable_expression_from_existing_node_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceVariableNodeId, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_variable_expression_from_existing_node_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[53]);
	FrontendNodeRow sourceNode = __latency_fn_frontend_model_tables_node_by_id(model, sourceVariableNodeId, counters);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(sourceNode->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(sourceNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))) || php::not_identical(cast<int_t<>>(sourceNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	FrontendExpressionPayloadRow sourceExpression = __latency_fn_frontend_model_tables_expression_by_id(model, sourceNode->payload_row_id, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(sourceExpression->payload_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(sourceExpression->callee_name_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	FrontendExpressionPayloadRow variablePayload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_variable_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = sourceExpression->callee_name_id, .left_node_id = __latency_fn_structure_row_ids_none_id(), .right_node_id = __latency_fn_structure_row_ids_none_id(), .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)), .source_range_id = sourceNode->source_range_id, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> variablePayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, variablePayload, counters));
	FrontendNodeRow variableNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_variable_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = variablePayloadId, .source_range_id = sourceNode->source_range_id, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, variableNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_literal_expression_with_numeric_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> numericTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_literal_expression_with_numeric_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[54]);
	TokenRow literalToken = TokenRow{};
	int_t<std::uint16_t> literalKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_frontend_model_builder_literal_kind_integer_id());
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(numericTypeRefId)));
	int_t<std::int32_t> numericPayload = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint16_t> literalStatusId = required_cast<int_t<std::uint16_t>>(__latency_fn_frontend_model_builder_literal_status_ready_id());
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at_bool_literal(state, source)))) {
		if (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("true")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("false"))))) {
			literalToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t(""));
		}
		else {
			literalToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
		}
		literalKindId = __latency_fn_frontend_model_builder_literal_kind_bool_id();
		typeRefId = __latency_fn_frontend_model_builder_primitive_bool_type_ref_id();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_token_text_equals(source, literalToken, string_t("true"))))) {
			numericPayload = __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(1));
		}
	}
	else {
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_number(), string_t(""))))) {
			literalToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_number(), string_t(""));
			string_t literalText = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, literalToken));
			if (static_cast<bool>(((php::str_contains(literalText, string_t(".")) || php::str_contains(literalText, string_t("e"))) || php::str_contains(literalText, string_t("E"))))) {
				if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_numeric_type_ref_accepts_decimal_literal(cast<int_t<std::uint32_t>>(typeRefId))))) {
					literalKindId = __latency_fn_frontend_model_builder_literal_kind_floating_id();
				}
				else {
					literalStatusId = __latency_fn_structure_row_ids_none_kind_id();
				}
			}
			else {
				int_t<> literalValue = required_cast<int_t<>>(cast<int_t<>>(literalText));
				if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_numeric_type_ref_is_integer_literal_target(cast<int_t<std::uint32_t>>(typeRefId))))) {
					if (static_cast<bool>((!__latency_fn_frontend_model_builder_integer_literal_fits_type_ref(cast<int_t<std::uint32_t>>(typeRefId), literalValue)))) {
						literalStatusId = __latency_fn_structure_row_ids_none_kind_id();
					}
				}
				else {
					typeRefId = __latency_fn_frontend_model_builder_primitive_int_type_ref_id();
				}
				numericPayload = __latency_fn_structure_row_ids_int32_from_int(literalValue);
			}
		}
		else {
			literalToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
			literalStatusId = __latency_fn_structure_row_ids_none_kind_id();
			__latency_fn_parser_state_diagnostic(state);
		}
	}
	int_t<std::uint32_t> literalRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, literalToken, counters));
	FrontendLiteralPayloadRow literalPayload = FrontendLiteralPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .literal_kind_id = literalKindId, .type_ref_id = typeRefId, .numeric_payload = numericPayload, .literal_status_id = literalStatusId, .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> literalPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_literal_payload(model, literalPayload, counters));
	FrontendNodeRow literalNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_literal_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_literal_id(), .payload_row_id = literalPayloadId, .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, literalNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_string_literal_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_string_literal_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[55]);
	TokenRow literalToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_string_literal(), string_t(""));
	int_t<std::uint32_t> literalRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, literalToken, counters));
	FrontendLiteralPayloadRow literalPayload = FrontendLiteralPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .literal_kind_id = __latency_fn_frontend_model_builder_literal_kind_string_id(), .type_ref_id = __latency_fn_frontend_model_builder_runtime_string_type_ref_id(), .numeric_payload = __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), .literal_status_id = __latency_fn_frontend_model_builder_literal_status_ready_id(), .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> literalPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_literal_payload(model, literalPayload, counters));
	FrontendNodeRow literalNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_literal_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_literal_id(), .payload_row_id = literalPayloadId, .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, literalNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_null_literal_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_null_literal_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[56]);
	TokenRow currentToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	TokenRow literalToken = __latency_fn_parser_state_expect(state, source, currentToken->kind_id, string_t("null"));
	int_t<std::uint32_t> literalRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, literalToken, counters));
	FrontendLiteralPayloadRow literalPayload = FrontendLiteralPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .literal_kind_id = __latency_fn_frontend_model_builder_literal_kind_null_id(), .type_ref_id = __latency_fn_type_refs_null_id(), .numeric_payload = __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), .literal_status_id = __latency_fn_structure_row_ids_none_kind_id(), .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> literalPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_literal_payload(model, literalPayload, counters));
	FrontendNodeRow literalNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_literal_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_literal_id(), .payload_row_id = literalPayloadId, .source_range_id = literalRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, literalNode, counters);
}

}
