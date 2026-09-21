#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/ParserDiagnosticTable.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/TokenRow.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_use_function_declaration_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_use_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_declaration_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_use_function_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_callee_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_use_function_declaration_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_function_import_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_use_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_namespace_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_use_function_declaration.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_namespace_declaration_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_namespace_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_use_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_namespace_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_namespace_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_namespace_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_use_function_declaration.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_parser_state_skip_comments.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_declaration_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_function_parameter_expression.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_type_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_function_parameter.hpp"
#include "__callable/__latency_fn_frontend_model_builder_body_shape_digest_from_body_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_type_name_token.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_function_body.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_declaration_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_type_syntax_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_type_syntax_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_type_syntax_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_type_syntax_named_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_declaration_payload.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_use_function_declaration_node(shared_p<FrontendModel> model, TokenRow useToken, int_t<std::uint32_t> targetRangeId, int_t<std::uint32_t> targetPayloadId, TokenRow lastToken, int_t<std::uint32_t> namespaceNameId, int_t<std::uint32_t> namespaceSourceRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_use_function_declaration_node", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[144]);
	int_t<std::uint32_t> declarationRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(useToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(lastToken), counters));
	FrontendDeclarationPayloadRow declarationPayload = FrontendDeclarationPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .declaration_kind_id = __latency_fn_frontend_model_builder_declaration_kind_use_function_id(), .name_id = targetPayloadId, .name_source_range_id = targetRangeId, .namespace_name_id = namespaceNameId, .namespace_source_range_id = namespaceSourceRangeId, .symbol_id = __latency_fn_structure_row_ids_none_id(), .declared_type_ref_id = __latency_fn_structure_row_ids_none_id(), .parameter_list_node_id = __latency_fn_structure_row_ids_none_id(), .body_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = declarationRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> declarationPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_declaration_payload(model, declarationPayload, counters));
	FrontendNodeRow declarationNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = __latency_fn_structure_row_ids_none_id(), .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_declaration_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_declaration_use_function_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_declaration_id(), .payload_row_id = declarationPayloadId, .source_range_id = declarationRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, declarationNode, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_use_function_declaration(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_use_function_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[145]);
	TokenRow useToken = __latency_fn_frontend_model_builder_expect_use_keyword(state, source);
	__latency_fn_frontend_model_builder_expect_function_import_keyword(state, source);
	int_t<std::uint32_t> targetRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_namespace_name_range(state, source, model, counters));
	int_t<std::uint32_t> targetPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_callee_name(model, cast<int_t<std::uint32_t>>(targetRangeId), counters));
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	return __latency_fn_frontend_model_builder_append_use_function_declaration_node(model, useToken, cast<int_t<std::uint32_t>>(targetRangeId), cast<int_t<std::uint32_t>>(targetPayloadId), semicolonToken, state->current_namespace_name_id, state->current_namespace_source_range_id, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_namespace_declaration(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_namespace_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[146]);
	TokenRow namespaceToken = __latency_fn_frontend_model_builder_expect_namespace_keyword(state, source);
	int_t<std::uint32_t> nameRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_namespace_name_range(state, source, model, counters));
	int_t<std::uint32_t> namePayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_namespace_name(model, cast<int_t<std::uint32_t>>(nameRangeId), counters));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("{"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("{"));
		int_t<std::uint32_t> previousNamespaceNameId = required_cast<int_t<std::uint32_t>>(state->current_namespace_name_id);
		int_t<std::uint32_t> previousNamespaceSourceRangeId = required_cast<int_t<std::uint32_t>>(state->current_namespace_source_range_id);
		state->current_namespace_name_id = namePayloadId;
		state->current_namespace_source_range_id = nameRangeId;
		__latency_fn_parser_state_skip_comments(state);
		while (static_cast<bool>((__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("function")) || __latency_fn_frontend_model_builder_at_use_function_declaration(state, source)))) {
			if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_at_use_function_declaration(state, source)))) {
				__latency_fn_frontend_model_builder_parse_use_function_declaration(state, source, model, counters);
			}
			else {
				__latency_fn_frontend_model_builder_parse_function_declaration(state, source, model, counters);
			}
			__latency_fn_parser_state_skip_comments(state);
		}
		if (static_cast<bool>(((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("}"))) && php::identical(cast<int_t<>>(state->diagnostics->error_count), static_cast<int_t<> >(0))))) {
			__latency_fn_frontend_model_builder_parse_synthetic_script_entry_declaration_until_close(state, source, model, counters);
			__latency_fn_parser_state_skip_comments(state);
		}
		TokenRow closeBrace = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("}"));
		state->current_namespace_name_id = previousNamespaceNameId;
		state->current_namespace_source_range_id = previousNamespaceSourceRangeId;
		return __latency_fn_frontend_model_builder_append_namespace_declaration_node(model, namespaceToken, cast<int_t<std::uint32_t>>(nameRangeId), cast<int_t<std::uint32_t>>(namePayloadId), closeBrace, counters);
	}
	TokenRow semicolonToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(";"));
	state->current_namespace_name_id = namePayloadId;
	state->current_namespace_source_range_id = nameRangeId;
	return __latency_fn_frontend_model_builder_append_namespace_declaration_node(model, namespaceToken, cast<int_t<std::uint32_t>>(nameRangeId), cast<int_t<std::uint32_t>>(namePayloadId), semicolonToken, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_function_declaration(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_function_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[147]);
	TokenRow functionToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("function"));
	TokenRow nameToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("("));
	int_t<std::uint32_t> parameterNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> previousParameterNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> parameterTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	while (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(")"))))) {
		if (static_cast<bool>((!__latency_fn_frontend_model_builder_at_function_parameter(state, source)))) {
			__latency_fn_parser_state_diagnostic(state);
			break;
		}
		int_t<std::uint32_t> currentParameterTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::uint32_t> currentParameterNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_function_parameter_expression(state, source, model, counters, currentParameterTypeRefId));
		if (static_cast<bool>(php::identical(cast<int_t<>>(currentParameterNodeId), static_cast<int_t<> >(0)))) {
			break;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(parameterNodeId), static_cast<int_t<> >(0)))) {
			parameterNodeId = cast<int_t<std::uint32_t>>(currentParameterNodeId);
			parameterTypeRefId = cast<int_t<std::uint32_t>>(currentParameterTypeRefId);
		}
		if (static_cast<bool>((cast<int_t<>>(previousParameterNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow previousParameterNode = __latency_fn_frontend_model_tables_node_by_id(model, previousParameterNodeId, counters);
			__latency_fn_frontend_model_tables_patch_node_links(model, previousParameterNodeId, previousParameterNode->parent_node_id, previousParameterNode->first_child_node_id, currentParameterNodeId, counters);
		}
		previousParameterNodeId = cast<int_t<std::uint32_t>>(currentParameterNodeId);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(","))))) {
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(","));
		}
		else {
			break;
		}
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(")"));
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(":"));
	TokenRow typeToken = __latency_fn_frontend_model_builder_expect_type_name_token(state, source);
	TokenRow openBrace = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("{"));
	int_t<std::uint32_t> nameRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, nameToken, counters));
	int_t<std::uint32_t> typeRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_token(model, typeToken, counters));
	int_t<std::uint32_t> declarationRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(functionToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(openBrace), counters));
	int_t<std::uint32_t> namePayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_declaration_name(model, cast<int_t<std::uint32_t>>(nameRangeId), counters));
	int_t<std::uint32_t> typeNamePayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_append_type_name(model, cast<int_t<std::uint32_t>>(typeRangeId), counters));
	string_t typeName = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, typeToken));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_type_ref_id_for_name(typeName));
	FrontendTypeSyntaxPayloadRow typePayload = FrontendTypeSyntaxPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .type_syntax_kind_id = __latency_fn_frontend_model_tables_row_kind_type_syntax_named_id(), .base_name_id = typeNamePayloadId, .base_name_source_range_id = typeRangeId, .type_ref_id = typeRefId, .type_family_id = __latency_fn_structure_row_ids_none_id(), .first_type_arg_row_id = __latency_fn_structure_row_ids_none_id(), .type_arg_count = __latency_fn_structure_row_ids_none_kind_id(), .value_arg_int = __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), .source_range_id = typeRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> typePayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_type_syntax_payload(model, typePayload, counters));
	FrontendNodeRow typeNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = __latency_fn_structure_row_ids_none_id(), .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_type_syntax_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_type_syntax_named_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_type_syntax_id(), .payload_row_id = typePayloadId, .source_range_id = typeRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> typeNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, typeNode, counters));
	FrontendDeclarationPayloadRow declarationPayload = FrontendDeclarationPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .declaration_kind_id = __latency_fn_frontend_model_builder_declaration_kind_function_id(), .name_id = namePayloadId, .name_source_range_id = nameRangeId, .namespace_name_id = state->current_namespace_name_id, .namespace_source_range_id = state->current_namespace_source_range_id, .symbol_id = __latency_fn_structure_row_ids_none_id(), .declared_type_ref_id = typeRefId, .parameter_list_node_id = parameterNodeId, .body_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = declarationRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> declarationPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_declaration_payload(model, declarationPayload, counters));
	FrontendNodeRow declarationNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = __latency_fn_structure_row_ids_none_id(), .first_child_node_id = typeNodeId, .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_declaration_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_declaration_function_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_declaration_id(), .payload_row_id = declarationPayloadId, .source_range_id = declarationRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> declarationNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_node(model, declarationNode, counters));
	__latency_fn_frontend_model_tables_patch_node_links(model, typeNodeId, declarationNodeId, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), counters);
	if (static_cast<bool>((cast<int_t<>>(parameterNodeId) > static_cast<int_t<> >(0)))) {
		int_t<std::uint32_t> currentParameterNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(parameterNodeId));
		while (static_cast<bool>((cast<int_t<>>(currentParameterNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow currentParameterNode = __latency_fn_frontend_model_tables_node_by_id(model, currentParameterNodeId, counters);
			int_t<std::uint32_t> nextParameterNodeId = required_cast<int_t<std::uint32_t>>(currentParameterNode->next_sibling_node_id);
			int_t<std::uint32_t> nextSiblingNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(nextParameterNodeId));
			if (static_cast<bool>(php::identical(cast<int_t<>>(nextParameterNodeId), static_cast<int_t<> >(0)))) {
				nextSiblingNodeId = cast<int_t<std::uint32_t>>(typeNodeId);
			}
			__latency_fn_frontend_model_tables_patch_node_links(model, currentParameterNodeId, declarationNodeId, currentParameterNode->first_child_node_id, nextSiblingNodeId, counters);
			if (static_cast<bool>(php::identical(cast<int_t<>>(nextParameterNodeId), static_cast<int_t<> >(0)))) {
				break;
			}
			currentParameterNodeId = cast<int_t<std::uint32_t>>(nextParameterNodeId);
		}
	}
	int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_parse_function_body(state, source, model, cast<int_t<std::uint32_t>>(declarationNodeId), cast<int_t<std::uint32_t>>(typeRefId), counters));
	if (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
		declarationPayload = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationPayloadId, counters);
		declarationPayload->body_node_id = bodyNodeId;
		int_t<std::uint32_t> bodySourceLength = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::uint32_t> bodyContentHash = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::uint32_t> bodyShapeWalkRows = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		__latency_fn_frontend_model_builder_body_shape_digest_from_body_node(model, source, cast<int_t<std::uint32_t>>(bodyNodeId), bodySourceLength, bodyContentHash, bodyShapeWalkRows, counters);
		declarationPayload->body_source_length = bodySourceLength;
		declarationPayload->body_content_hash = bodyContentHash;
		declarationPayload->body_shape_walk_rows = bodyShapeWalkRows;
		__latency_fn_frontend_model_tables_update_declaration_payload(model, declarationPayload, counters);
		__latency_fn_frontend_model_tables_patch_node_links(model, typeNodeId, declarationNodeId, __latency_fn_structure_row_ids_none_id(), bodyNodeId, counters);
	}
	return cast<int_t<std::uint32_t>>(declarationNodeId);
}

}
