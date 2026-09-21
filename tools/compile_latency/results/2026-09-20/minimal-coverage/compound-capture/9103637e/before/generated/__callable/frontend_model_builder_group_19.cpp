#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ParserDiagnosticTable.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_declaration_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_function_id.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_attach_synthetic_script_entry_body.hpp"
#include "__callable/__latency_fn_frontend_model_builder_body_shape_digest_from_body_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_declaration_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell.hpp"
#include "__callable/__latency_fn_frontend_model_builder_attach_synthetic_script_entry_body.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_eof_with_implicit_return.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_int_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell.hpp"
#include "__callable/__latency_fn_frontend_model_builder_attach_synthetic_script_entry_body.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close_with_implicit_return.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_int_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_capacity_at_least.hpp"
#include "__callable/__latency_fn_frontend_model_builder_capacity_at_least.hpp"
#include "__callable/__latency_fn_frontend_model_builder_reserve_model_for_tokens.hpp"
#include "__callable/__latency_fn_frontend_model_tables_reserve_model.hpp"
#include "__callable/__latency_fn_frontend_model_builder_apply_node_segment_policy.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit_with_node_segment_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_apply_node_segment_policy.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_namespace_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_use_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_namespace_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit_with_node_segment_policy.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_use_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_reserve_model_for_tokens.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_make.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_source_buffers_append_from_source_unit.hpp"
#include "__callable/__latency_fn_source_buffers_append_line_starts.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell(shared_p<PhsParserState> state, shared_p<FrontendModel> model, int_t<std::uint32_t> entryTypeRefId, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t>& declarationPayloadId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_synthetic_script_entry_declaration_shell", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[148]);
	TokenRow currentToken = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	int_t<std::uint32_t> declarationRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(currentToken->start_offset), cast<int_t<>>(currentToken->start_offset), counters));
	FrontendDeclarationPayloadRow declarationPayload = FrontendDeclarationPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .declaration_kind_id = __latency_fn_frontend_model_builder_declaration_kind_function_id(), .name_id = __latency_fn_structure_row_ids_none_id(), .name_source_range_id = __latency_fn_structure_row_ids_none_id(), .namespace_name_id = state->current_namespace_name_id, .namespace_source_range_id = state->current_namespace_source_range_id, .symbol_id = __latency_fn_structure_row_ids_none_id(), .declared_type_ref_id = entryTypeRefId, .parameter_list_node_id = __latency_fn_structure_row_ids_none_id(), .body_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = declarationRangeId, .flags = __latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id()};
	declarationPayloadId = __latency_fn_frontend_model_tables_append_declaration_payload(model, declarationPayload, counters);
	FrontendNodeRow declarationNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = __latency_fn_structure_row_ids_none_id(), .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_declaration_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_declaration_function_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_declaration_id(), .payload_row_id = declarationPayloadId, .source_range_id = declarationRangeId, .flags = __latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id()};
	return __latency_fn_frontend_model_tables_append_node(model, declarationNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_attach_synthetic_script_entry_body(shared_p<FrontendModel> model, const string_t& source, int_t<std::uint32_t> declarationNodeId, int_t<std::uint32_t> declarationPayloadId, int_t<std::uint32_t> bodyNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::attach_synthetic_script_entry_body", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[149]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(bodyNodeId), static_cast<int_t<> >(0)))) {
		return;
	}
	FrontendDeclarationPayloadRow declarationPayload = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationPayloadId, counters);
	declarationPayload->body_node_id = bodyNodeId;
	int_t<std::uint32_t> bodySourceLength = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> bodyContentHash = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> bodyShapeWalkRows = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	__latency_fn_frontend_model_builder_body_shape_digest_from_body_node(model, source, cast<int_t<std::uint32_t>>(bodyNodeId), bodySourceLength, bodyContentHash, bodyShapeWalkRows, counters);
	declarationPayload->body_source_length = bodySourceLength;
	declarationPayload->body_content_hash = bodyContentHash;
	declarationPayload->body_shape_walk_rows = bodyShapeWalkRows;
	__latency_fn_frontend_model_tables_update_declaration_payload(model, declarationPayload, counters);
	FrontendNodeRow declarationNode = __latency_fn_frontend_model_tables_node_by_id(model, declarationNodeId, counters);
	declarationNode->first_child_node_id = bodyNodeId;
	__latency_fn_frontend_model_tables_update_node(model, declarationNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_synthetic_script_entry_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[150]);
	int_t<std::uint32_t> entryTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_primitive_int_type_ref_id());
	int_t<std::uint32_t> declarationPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> declarationNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell(state, model, cast<int_t<std::uint32_t>>(entryTypeRefId), counters, declarationPayloadId));
	int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_eof_with_implicit_return(state, source, model, cast<int_t<std::uint32_t>>(declarationNodeId), cast<int_t<std::uint32_t>>(entryTypeRefId), counters));
	__latency_fn_frontend_model_builder_attach_synthetic_script_entry_body(model, source, cast<int_t<std::uint32_t>>(declarationNodeId), cast<int_t<std::uint32_t>>(declarationPayloadId), cast<int_t<std::uint32_t>>(bodyNodeId), counters);
	return cast<int_t<std::uint32_t>>(declarationNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration_until_close(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_synthetic_script_entry_declaration_until_close", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[151]);
	int_t<std::uint32_t> entryTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_primitive_int_type_ref_id());
	int_t<std::uint32_t> declarationPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> declarationNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell(state, model, cast<int_t<std::uint32_t>>(entryTypeRefId), counters, declarationPayloadId));
	int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_statement_list_until_close_with_implicit_return(state, source, model, cast<int_t<std::uint32_t>>(declarationNodeId), bool_t(static_cast<bool_t>(true)), cast<int_t<std::uint32_t>>(entryTypeRefId), counters));
	__latency_fn_frontend_model_builder_attach_synthetic_script_entry_body(model, source, cast<int_t<std::uint32_t>>(declarationNodeId), cast<int_t<std::uint32_t>>(declarationPayloadId), cast<int_t<std::uint32_t>>(bodyNodeId), counters);
	return cast<int_t<std::uint32_t>>(declarationNodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<> __latency_fn_frontend_model_builder_capacity_at_least(int_t<> capacity, int_t<> minimum) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::capacity_at_least", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[152]);
	if (static_cast<bool>((capacity < minimum))) {
		return minimum;
	}
	return capacity;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_reserve_model_for_tokens(shared_p<FrontendModel> model, SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::reserve_model_for_tokens", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[153]);
	int_t<> tokenCount = required_cast<int_t<>>(cast<int_t<>>(tokens->token_count));
	int_t<> lineStartCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>(sourceUnit->line_count) + static_cast<int_t<> >(1)), static_cast<int_t<> >(8)));
	int_t<> sourceRangeCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>(((tokenCount * static_cast<int_t<> >(3)) / static_cast<int_t<> >(4))) + static_cast<int_t<> >(64)), static_cast<int_t<> >(32)));
	int_t<> nodeCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(2))) + static_cast<int_t<> >(16)), static_cast<int_t<> >(32)));
	int_t<> declarationCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(512))) + static_cast<int_t<> >(4)), static_cast<int_t<> >(4)));
	int_t<> statementCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(6))) + static_cast<int_t<> >(16)), static_cast<int_t<> >(8)));
	int_t<> expressionCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(3))) + static_cast<int_t<> >(16)), static_cast<int_t<> >(12)));
	int_t<> typeSyntaxCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(512))) + static_cast<int_t<> >(4)), static_cast<int_t<> >(4)));
	int_t<> literalCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(6))) + static_cast<int_t<> >(16)), static_cast<int_t<> >(8)));
	int_t<> nameCapacity = required_cast<int_t<>>(__latency_fn_frontend_model_builder_capacity_at_least((cast<int_t<>>((tokenCount / static_cast<int_t<> >(6))) + static_cast<int_t<> >(16)), static_cast<int_t<> >(12)));
	__latency_fn_frontend_model_tables_reserve_model(model, static_cast<int_t<> >(1), sourceRangeCapacity, static_cast<int_t<> >(0), lineStartCapacity, nodeCapacity, declarationCapacity, statementCapacity, expressionCapacity, typeSyntaxCapacity, literalCapacity, nameCapacity, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_apply_node_segment_policy(shared_p<FrontendModel> model, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::apply_node_segment_policy", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[154]);
	if (static_cast<bool>((cast<int_t<>>(segmentThreshold) > static_cast<int_t<> >(0)))) {
		model->node_rows->segment_threshold = segmentThreshold;
	}
	if (static_cast<bool>((cast<int_t<>>(segmentCapacity) > static_cast<int_t<> >(0)))) {
		model->node_rows->segment_capacity = segmentCapacity;
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
shared_p<FrontendModel> __latency_fn_frontend_model_builder_parse_source_unit(SourceUnitTableRow sourceUnit, const string_t& source, shared_p<TokenStream> tokens, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[155]);
	return __latency_fn_frontend_model_builder_parse_source_unit_with_node_segment_policy(sourceUnit, source, tokens, counters, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id());
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
shared_p<FrontendModel> __latency_fn_frontend_model_builder_parse_source_unit_with_node_segment_policy(SourceUnitTableRow sourceUnit, const string_t& source, shared_p<TokenStream> tokens, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_source_unit_with_node_segment_policy", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[156]);
	shared_p<FrontendModel> model = create<FrontendModel>();
	model->source_unit_id = sourceUnit->source_unit_id;
	__latency_fn_frontend_model_builder_apply_node_segment_policy(model, cast<int_t<std::uint32_t>>(segmentThreshold), cast<int_t<std::uint32_t>>(segmentCapacity));
	__latency_fn_frontend_model_builder_reserve_model_for_tokens(model, sourceUnit, tokens, counters);
	int_t<std::uint32_t> sourceBufferId = required_cast<int_t<std::uint32_t>>(__latency_fn_source_buffers_append_from_source_unit(model, sourceUnit, source, counters));
	__latency_fn_source_buffers_append_line_starts(model, sourceBufferId, source, counters);
	shared_p<PhsParserState> state = __latency_fn_parser_state_make(sourceUnit, sourceBufferId, tokens);
	__latency_fn_parser_state_skip_comments(state);
	while (static_cast<bool>(((__latency_fn_frontend_model_builder_at_namespace_declaration(state, source) || __latency_fn_frontend_model_builder_at_use_function_declaration(state, source)) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("function"))))) {
		if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_namespace_declaration(state, source)))) {
			__latency_fn_frontend_model_builder_parse_namespace_declaration(state, source, model, counters);
		}
		else {
			if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_use_function_declaration(state, source)))) {
				__latency_fn_frontend_model_builder_parse_use_function_declaration(state, source, model, counters);
			}
			else {
				__latency_fn_frontend_model_builder_parse_function_declaration(state, source, model, counters);
			}
		}
		__latency_fn_parser_state_skip_comments(state);
	}
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_eof(), string_t(""))))) {
		__latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration(state, source, model, counters);
	}
	model->parser_diagnostic_count = state->diagnostics->row_count;
	model->parser_error_count = state->diagnostics->error_count;
	return model;
}

}
