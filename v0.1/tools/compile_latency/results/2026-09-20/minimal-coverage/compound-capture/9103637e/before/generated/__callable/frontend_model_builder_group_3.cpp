#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_name_payload.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_declaration_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_type_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_type_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_callee_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_callee_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_variable_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_constant_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_constant_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_namespace_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_namespace_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_with_type.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_expression_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_variable_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_parser_state_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_constant_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_or_unknown.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_parser_state_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, int_t<std::uint16_t> roleId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[41]);
	int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(model->names)));
	FrontendNamePayloadRow name = FrontendNamePayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .name_id = nameId, .source_range_id = nameRangeId, .name_role_id = roleId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_name_payload(model, name, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_declaration_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_declaration_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[42]);
	return __latency_fn_frontend_model_builder_append_name(model, cast<int_t<std::uint32_t>>(nameRangeId), __latency_fn_frontend_model_builder_name_role_declaration_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_type_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_type_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[43]);
	return __latency_fn_frontend_model_builder_append_name(model, cast<int_t<std::uint32_t>>(nameRangeId), __latency_fn_frontend_model_builder_name_role_type_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_callee_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_callee_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[44]);
	return __latency_fn_frontend_model_builder_append_name(model, cast<int_t<std::uint32_t>>(nameRangeId), __latency_fn_frontend_model_builder_name_role_callee_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_variable_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_variable_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[45]);
	return __latency_fn_frontend_model_builder_append_name(model, cast<int_t<std::uint32_t>>(nameRangeId), __latency_fn_frontend_model_builder_name_role_variable_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_constant_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_constant_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[46]);
	return __latency_fn_frontend_model_builder_append_name(model, cast<int_t<std::uint32_t>>(nameRangeId), __latency_fn_frontend_model_builder_name_role_constant_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_namespace_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_namespace_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[47]);
	return __latency_fn_frontend_model_builder_append_name(model, cast<int_t<std::uint32_t>>(nameRangeId), __latency_fn_frontend_model_builder_name_role_namespace_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_expression_type_ref_or_unknown(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expression_type_ref_or_unknown", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[48]);
	if (static_cast<bool>((cast<int_t<>>(typeRefId) > static_cast<int_t<> >(0)))) {
		return cast<int_t<std::uint32_t>>(typeRefId);
	}
	return __latency_fn_type_refs_unknown_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_variable_expression(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_variable_expression", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[49]);
	return __latency_fn_frontend_model_builder_append_variable_expression_with_type(state, source, model, cast<int_t<std::uint32_t>>(parentNodeId), __latency_fn_type_refs_unknown_id(), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_variable_expression_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_variable_expression_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[50]);
	TokenRow dollarToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("$"));
	TokenRow nameToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	string_t nameText = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, nameToken));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)));
	int_t<std::uint32_t> localTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_parser_state_local_type_ref_id_for_name(state, nameText));
	if (static_cast<bool>((cast<int_t<>>(localTypeRefId) > static_cast<int_t<> >(0)))) {
		typeRefId = cast<int_t<std::uint32_t>>(localTypeRefId);
	}
	int_t<std::uint32_t> nameRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, nameToken, counters));
	int_t<std::uint32_t> variableRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(dollarToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(nameToken), counters));
	int_t<std::uint32_t> variableNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_variable_name(model, cast<int_t<std::uint32_t>>(nameRangeId), counters));
	FrontendExpressionPayloadRow variablePayload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_variable_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = variableNameId, .left_node_id = __latency_fn_structure_row_ids_none_id(), .right_node_id = __latency_fn_structure_row_ids_none_id(), .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = typeRefId, .source_range_id = variableRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> variablePayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, variablePayload, counters));
	FrontendNodeRow variableNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_variable_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = variablePayloadId, .source_range_id = variableRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, variableNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, TokenRow nameToken, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_identifier_expression_from_token_with_type", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[51]);
	string_t nameText = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, nameToken));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_or_unknown(cast<int_t<std::uint32_t>>(inferredTypeRefId)));
	int_t<std::uint32_t> localTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_parser_state_local_type_ref_id_for_name(state, nameText));
	if (static_cast<bool>((cast<int_t<>>(localTypeRefId) > static_cast<int_t<> >(0)))) {
		typeRefId = cast<int_t<std::uint32_t>>(localTypeRefId);
	}
	int_t<std::uint32_t> nameRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, nameToken, counters));
	int_t<std::uint32_t> constantNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_constant_name(model, cast<int_t<std::uint32_t>>(nameRangeId), counters));
	FrontendExpressionPayloadRow identifierPayload = FrontendExpressionPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .expression_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_variable_id(), .operator_id = __latency_fn_structure_row_ids_none_kind_id(), .callee_node_id = __latency_fn_structure_row_ids_none_id(), .callee_name_id = constantNameId, .left_node_id = __latency_fn_structure_row_ids_none_id(), .right_node_id = __latency_fn_structure_row_ids_none_id(), .third_node_id = __latency_fn_structure_row_ids_none_id(), .first_argument_node_id = __latency_fn_structure_row_ids_none_id(), .argument_count = __latency_fn_structure_row_ids_none_kind_id(), .inferred_type_ref_id = typeRefId, .source_range_id = nameRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> identifierPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_expression_payload(model, identifierPayload, counters));
	FrontendNodeRow identifierNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = parentNodeId, .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_expression_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_expression_variable_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_expression_id(), .payload_row_id = identifierPayloadId, .source_range_id = nameRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, identifierNode, counters);
}

}
