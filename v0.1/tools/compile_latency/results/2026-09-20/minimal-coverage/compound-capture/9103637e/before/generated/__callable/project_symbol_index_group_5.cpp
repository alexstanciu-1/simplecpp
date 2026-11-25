#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_project_symbol_identity_body_shape_key_from_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_range_text.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_payload_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_range_text.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_is_synthetic_script_entry.hpp"
#include "__callable/__latency_fn_frontend_model_builder_synthetic_script_entry_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_function_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_payload_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_namespace_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_payload_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_namespace_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_qualified_function_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_payload_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_ref_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_syntax_name.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_type_syntax_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_type_syntax_named_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_type_syntax_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_return_type_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_ref_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_syntax_name.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_body_shape__exec.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32_slice.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_body_shape_key(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::body_shape_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[58]);
	return __latency_fn_project_symbol_identity_body_shape_key_from_body_shape(__latency_fn_project_symbol_index_body_shape(index, row));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_source_unit_key(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[59]);
	return __latency_fn_project_symbol_index_materialized_string(index->source_unit_keys, row->source_unit_key_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_source_range_text(shared_p<FrontendModel> model, const string_t& sourceText, int_t<std::uint32_t> sourceRangeId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::source_range_text", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[60]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceRangeRow range = __latency_fn_frontend_model_tables_source_range_by_id(model, sourceRangeId, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(range->source_range_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return str::byte_slice(sourceText, cast<int_t<>>(range->start_offset), cast<int_t<>>(range->length));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_name_payload_text(shared_p<FrontendModel> model, const string_t& sourceText, int_t<std::uint32_t> namePayloadId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::name_payload_text", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[61]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNamePayloadRow name = __latency_fn_frontend_model_tables_name_by_id(model, namePayloadId, counters);
	return __latency_fn_project_symbol_index_source_range_text(model, sourceText, name->source_range_id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_declaration_function_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::declaration_function_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[62]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_declaration_is_synthetic_script_entry(declaration)))) {
		return __latency_fn_frontend_model_builder_synthetic_script_entry_name();
	}
	return __latency_fn_project_symbol_index_name_payload_text(model, sourceText, declaration->name_id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_declaration_namespace_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::declaration_namespace_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[63]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(declaration->namespace_name_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return __latency_fn_project_symbol_index_name_payload_text(model, sourceText, declaration->namespace_name_id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_declaration_qualified_function_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration, const string_t& functionName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::declaration_qualified_function_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[64]);
	string_t namespaceName = required_cast<string_t>(__latency_fn_project_symbol_index_declaration_namespace_name(model, sourceText, declaration));
	if (static_cast<bool>(php::identical(namespaceName, string_t("")))) {
		return functionName;
	}
	return (cast<string_t>(namespaceName) + string_t("\\") + cast<string_t>(functionName));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_type_syntax_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendTypeSyntaxPayloadRow typeSyntax) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::type_syntax_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[65]);
	string_t typeName = required_cast<string_t>(__latency_fn_project_symbol_index_name_payload_text(model, sourceText, typeSyntax->base_name_id));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(typeName, string_t(""))))) {
		return typeName;
	}
	return __latency_fn_project_symbol_index_type_ref_name(typeSyntax->type_ref_id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_declaration_return_type_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow declarationNode, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::declaration_return_type_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[66]);
	if (static_cast<bool>((cast<int_t<>>(declarationNode->first_child_node_id) > static_cast<int_t<> >(0)))) {
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		FrontendNodeRow typeNode = __latency_fn_frontend_model_tables_node_by_id(model, declarationNode->first_child_node_id, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(typeNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_type_syntax_id())) && php::identical(cast<int_t<>>(typeNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_type_syntax_named_id()))))) {
			FrontendTypeSyntaxPayloadRow typeSyntax = __latency_fn_frontend_model_tables_type_syntax_by_id(model, typeNode->payload_row_id, counters);
			return __latency_fn_project_symbol_index_type_syntax_name(model, sourceText, typeSyntax);
		}
	}
	return __latency_fn_project_symbol_index_type_ref_name(declaration->declared_type_ref_id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_declaration_body_shape__exec(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration, int_t<std::uint32_t>& bodyLengthOut, int_t<std::uint32_t>& walkRowsOut, bool_t& usedCachedDigest) {
	bodyLengthOut = __latency_fn_structure_row_ids_none_id();
	walkRowsOut = __latency_fn_structure_row_ids_none_id();
	usedCachedDigest = bool_t(static_cast<bool_t>(false));
	if (static_cast<bool>(php::identical(cast<int_t<>>(declaration->body_node_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	if (static_cast<bool>((cast<int_t<>>(declaration->body_source_length) > static_cast<int_t<> >(0)))) {
		bodyLengthOut = declaration->body_source_length;
		walkRowsOut = declaration->body_shape_walk_rows;
		usedCachedDigest = bool_t(static_cast<bool_t>(true));
		return (string_t("body_digest:v2:bytes=") + cast<string_t>(cast<int_t<>>(declaration->body_source_length)) + string_t(":hash32=") + cast<string_t>(cast<int_t<>>(declaration->body_content_hash)));
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, declaration->body_node_id, counters);
	int_t<> walkRows = required_cast<int_t<>>(static_cast<int_t<> >(1));
	if (static_cast<bool>(php::identical(cast<int_t<>>(bodyNode->source_range_id), static_cast<int_t<> >(0)))) {
		walkRowsOut = __latency_fn_structure_row_ids_uint32_from_int(walkRows);
		return string_t("");
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
	int_t<std::uint32_t> bodyHash = required_cast<int_t<std::uint32_t>>(__latency_fn_source_buffers_content_hash32_slice(sourceText, firstRange->start_offset, __latency_fn_structure_row_ids_uint32_from_int(bodyLength)));
	bodyLengthOut = __latency_fn_structure_row_ids_uint32_from_int(bodyLength);
	walkRowsOut = __latency_fn_structure_row_ids_uint32_from_int(walkRows);
	return (string_t("body_digest:v2:bytes=") + cast<string_t>(bodyLength) + string_t(":hash32=") + cast<string_t>(cast<int_t<>>(bodyHash)));
}

}
