#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectSymbolFunctionImportRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_project_symbol_index_last_namespace_segment.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_by_names.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_last_namespace_segment.hpp"
#include "__callable/__latency_fn_project_symbol_index_normalize_import_target_qualified_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_by_names.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_namespace_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_payload_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_namespace.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_alias_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_target_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_target_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_namespace_prefix.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_id_by_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_namespace_function_import_target_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name_id_by_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_identity_export_shape_key_from_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_export_shape_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_last_namespace_segment(const string_t& qualifiedName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::last_namespace_segment", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[48]);
	int_t<> lastStart = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> length = required_cast<int_t<>>(str::byte_length(qualifiedName));
	while (static_cast<bool>((index < length))) {
		if (static_cast<bool>(php::identical(php::string_byte_at(qualifiedName, index), static_cast<int_t<> >(92)))) {
			lastStart = (index + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy((lastStart >= length)))) {
		return string_t("");
	}
	return str::byte_slice(qualifiedName, lastStart, (length - lastStart));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_function_import_by_names(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceRowId, const string_t& namespaceName, const string_t& aliasName, const string_t& targetQualifiedName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_function_import_by_names", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[49]);
	string_t targetName = required_cast<string_t>(__latency_fn_project_symbol_index_normalize_import_target_qualified_name(targetQualifiedName));
	string_t alias = required_cast<string_t>(aliasName);
	if (static_cast<bool>(php::identical(alias, string_t("")))) {
		alias = __latency_fn_project_symbol_index_last_namespace_segment(targetName);
	}
	if (static_cast<bool>((php::identical(targetName, string_t("")) || php::identical(alias, string_t(""))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	ProjectSymbolFunctionImportRow row = ProjectSymbolFunctionImportRow{};
	row->import_id = __latency_fn_structure_row_ids_next_dense_id(php::count(index->function_import_rows));
	row->source_unit_id = sourceUnitId;
	row->source_row_id = sourceRowId;
	if (static_cast<bool>(php::condition_truthy(php::not_identical(namespaceName, string_t(""))))) {
		row->namespace_qualified_name_id = __latency_fn_project_symbol_index_intern_qualified_name(index, namespaceName);
	}
	row->alias_name_id = __latency_fn_project_symbol_index_intern_name(index, alias);
	row->target_qualified_name_id = __latency_fn_project_symbol_index_intern_qualified_name(index, targetName);
	(void) index->function_import_rows.append(row);
	index->function_import_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(index->function_import_rows));
	return row->import_id;
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_function_import_from_declaration(shared_p<ProjectSymbolIndex> index, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model, FrontendNodeRow declarationNode, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_function_import_from_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[50]);
	string_t targetQualifiedName = required_cast<string_t>(__latency_fn_project_symbol_index_name_payload_text(model, sourceText, declaration->name_id));
	string_t namespaceName = required_cast<string_t>(__latency_fn_project_symbol_index_declaration_namespace_name(model, sourceText, declaration));
	return __latency_fn_project_symbol_index_append_function_import_by_names(index, sourceUnit->source_unit_id, declarationNode->node_id, namespaceName, string_t(""), targetQualifiedName);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_function_import_namespace(shared_p<ProjectSymbolIndex> index, ProjectSymbolFunctionImportRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_import_namespace", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[51]);
	return __latency_fn_project_symbol_index_materialized_string(index->qualified_names, row->namespace_qualified_name_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_function_import_alias_name(shared_p<ProjectSymbolIndex> index, ProjectSymbolFunctionImportRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_import_alias_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[52]);
	return __latency_fn_project_symbol_index_materialized_string(index->names, row->alias_name_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_function_import_target_qualified_name(shared_p<ProjectSymbolIndex> index, ProjectSymbolFunctionImportRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_import_target_qualified_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[53]);
	return __latency_fn_project_symbol_index_materialized_string(index->qualified_names, row->target_qualified_name_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_namespace_function_import_target_qualified_name(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow fromSymbol, const string_t& calleeName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::namespace_function_import_target_qualified_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[54]);
	if (static_cast<bool>((php::identical(calleeName, string_t("")) || php::str_contains(calleeName, string_t("\\"))))) {
		return string_t("");
	}
	string_t namespacePrefix = required_cast<string_t>(__latency_fn_project_symbol_index_function_namespace_prefix(index, fromSymbol));
	int_t<std::uint32_t> namespaceId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>(php::condition_truthy(php::not_identical(namespacePrefix, string_t(""))))) {
		namespaceId = __latency_fn_project_symbol_index_qualified_name_id_by_text(index, namespacePrefix);
		if (static_cast<bool>(php::identical(cast<int_t<>>(namespaceId), static_cast<int_t<> >(0)))) {
			return string_t("");
		}
	}
	int_t<std::uint32_t> aliasId = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_name_id_by_text(index, calleeName));
	if (static_cast<bool>(php::identical(cast<int_t<>>(aliasId), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	int_t<> matchCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	string_t targetQualifiedName = required_cast<string_t>(string_t(""));
	auto __latency_local_0 = index->function_import_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto importRow = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(importRow->namespace_qualified_name_id), cast<int_t<>>(namespaceId)) && php::identical(cast<int_t<>>(importRow->alias_name_id), cast<int_t<>>(aliasId))))) {
			matchCount = (matchCount + static_cast<int_t<> >(1));
			targetQualifiedName = __latency_fn_project_symbol_index_function_import_target_qualified_name(index, importRow);
		}
	}
	if (static_cast<bool>(php::identical(matchCount, static_cast<int_t<> >(1)))) {
		return targetQualifiedName;
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_signature_shape(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::signature_shape", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[55]);
	return __latency_fn_project_symbol_index_materialized_string(index->signature_shapes, row->signature_shape_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_export_shape_key(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::export_shape_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[56]);
	return __latency_fn_project_symbol_identity_export_shape_key_from_signature_shape(__latency_fn_project_symbol_index_signature_shape(index, row));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_body_shape(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::body_shape", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[57]);
	return __latency_fn_project_symbol_index_materialized_string(index->body_shapes, row->body_shape_id, string_t(""));
}

}
