#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_body_shape_digest_from_body_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32_slice.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_namespace_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_namespace_declaration.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_namespace_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_use_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_use_function_declaration.hpp"
#include "__callable/__latency_fn_frontend_model_builder_at_use_keyword.hpp"
#include "__callable/__latency_fn_parser_cursor_peek_at.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_use_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_function_import_keyword.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_append_namespace_declaration_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_namespace_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_declaration_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_namespace_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
void __latency_fn_frontend_model_builder_body_shape_digest_from_body_node(shared_p<FrontendModel> model, const string_t& source, int_t<std::uint32_t> bodyNodeId, int_t<std::uint32_t>& bodyLengthOut, int_t<std::uint32_t>& bodyHashOut, int_t<std::uint32_t>& walkRowsOut, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::body_shape_digest_from_body_node", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[135]);
	bodyLengthOut = __latency_fn_structure_row_ids_none_id();
	bodyHashOut = __latency_fn_structure_row_ids_none_id();
	walkRowsOut = __latency_fn_structure_row_ids_none_id();
	if (static_cast<bool>(php::identical(cast<int_t<>>(bodyNodeId), static_cast<int_t<> >(0)))) {
		return;
	}
	FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
	int_t<> walkRows = required_cast<int_t<>>(static_cast<int_t<> >(1));
	if (static_cast<bool>(php::identical(cast<int_t<>>(bodyNode->source_range_id), static_cast<int_t<> >(0)))) {
		walkRowsOut = __latency_fn_structure_row_ids_uint32_from_int(walkRows);
		return;
	}
	SourceRangeRow firstRange = __latency_fn_frontend_model_tables_source_range_by_id(model, bodyNode->source_range_id, counters);
	int_t<> endOffset = required_cast<int_t<>>((cast<int_t<>>(firstRange->start_offset) + cast<int_t<>>(firstRange->length)));
	int_t<std::uint32_t> nextNodeId = required_cast<int_t<std::uint32_t>>(bodyNode->next_sibling_node_id);
	while (static_cast<bool>((cast<int_t<>>(nextNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow nextNode = __latency_fn_frontend_model_tables_node_by_id(model, nextNodeId, counters);
		walkRows = (walkRows + static_cast<int_t<> >(1));
		if (static_cast<bool>((cast<int_t<>>(nextNode->source_range_id) > static_cast<int_t<> >(0)))) {
			SourceRangeRow nextRange = __latency_fn_frontend_model_tables_source_range_by_id(model, nextNode->source_range_id, counters);
			int_t<> nextEndOffset = required_cast<int_t<>>((cast<int_t<>>(nextRange->start_offset) + cast<int_t<>>(nextRange->length)));
			if (static_cast<bool>((nextEndOffset > endOffset))) {
				endOffset = nextEndOffset;
			}
		}
		nextNodeId = nextNode->next_sibling_node_id;
	}
	int_t<> bodyLength = required_cast<int_t<>>((endOffset - cast<int_t<>>(firstRange->start_offset)));
	bodyLengthOut = __latency_fn_structure_row_ids_uint32_from_int(bodyLength);
	bodyHashOut = __latency_fn_source_buffers_content_hash32_slice(source, firstRange->start_offset, __latency_fn_structure_row_ids_uint32_from_int(bodyLength));
	walkRowsOut = __latency_fn_structure_row_ids_uint32_from_int(walkRows);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_namespace_name_range(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_namespace_name_range", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[136]);
	TokenRow firstToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	TokenRow lastToken = firstToken;
	while (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("\\"))))) {
		__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("\\"));
		lastToken = __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	}
	return __latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(firstToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(lastToken), counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_namespace_declaration(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_namespace_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[137]);
	return (__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("namespace")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("namespace")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
TokenRow __latency_fn_frontend_model_builder_expect_namespace_keyword(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expect_namespace_keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[138]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("namespace"))))) {
		return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("namespace"));
	}
	return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t("namespace"));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_use_keyword(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_use_keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[139]);
	return (__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("use")) || __latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t("use")));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_at_use_function_declaration(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::at_use_function_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[140]);
	return (__latency_fn_frontend_model_builder_at_use_keyword(state, source) && (__latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_keyword(), string_t("function")) || __latency_fn_parser_cursor_peek_at(source, state->tokens, state->cursor, static_cast<int_t<> >(1), __latency_fn_token_kinds_identifier(), string_t("function"))));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
TokenRow __latency_fn_frontend_model_builder_expect_use_keyword(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expect_use_keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[141]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("use"))))) {
		return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("use"));
	}
	return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t("use"));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
TokenRow __latency_fn_frontend_model_builder_expect_function_import_keyword(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expect_function_import_keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[142]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_keyword(), string_t("function"))))) {
		return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t("function"));
	}
	return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t("function"));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_namespace_declaration_node(shared_p<FrontendModel> model, TokenRow namespaceToken, int_t<std::uint32_t> nameRangeId, int_t<std::uint32_t> namePayloadId, TokenRow lastToken, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::append_namespace_declaration_node", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[143]);
	int_t<std::uint32_t> declarationRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_source_range_from_offsets(model, cast<int_t<>>(namespaceToken->start_offset), __latency_fn_frontend_model_builder_token_end_offset(lastToken), counters));
	FrontendDeclarationPayloadRow declarationPayload = FrontendDeclarationPayloadRow{.payload_id = __latency_fn_structure_row_ids_none_id(), .declaration_kind_id = __latency_fn_frontend_model_builder_declaration_kind_namespace_id(), .name_id = namePayloadId, .name_source_range_id = nameRangeId, .namespace_name_id = namePayloadId, .namespace_source_range_id = nameRangeId, .symbol_id = __latency_fn_structure_row_ids_none_id(), .declared_type_ref_id = __latency_fn_structure_row_ids_none_id(), .parameter_list_node_id = __latency_fn_structure_row_ids_none_id(), .body_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = declarationRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	int_t<std::uint32_t> declarationPayloadId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_append_declaration_payload(model, declarationPayload, counters));
	FrontendNodeRow declarationNode = FrontendNodeRow{.node_id = __latency_fn_structure_row_ids_none_id(), .parent_node_id = __latency_fn_structure_row_ids_none_id(), .first_child_node_id = __latency_fn_structure_row_ids_none_id(), .next_sibling_node_id = __latency_fn_structure_row_ids_none_id(), .row_family_id = __latency_fn_frontend_model_tables_row_family_declaration_id(), .row_kind_id = __latency_fn_frontend_model_tables_row_kind_declaration_namespace_id(), .payload_kind_id = __latency_fn_frontend_model_tables_payload_kind_declaration_id(), .payload_row_id = declarationPayloadId, .source_range_id = declarationRangeId, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_node(model, declarationNode, counters);
}

}
