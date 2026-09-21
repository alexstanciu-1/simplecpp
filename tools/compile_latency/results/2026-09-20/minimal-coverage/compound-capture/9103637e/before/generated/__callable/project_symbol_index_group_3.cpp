#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_qualified_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_signature_shape.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_body_shape.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_source_unit_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_add_u32.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_profile_metrics.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_project_symbol_index_body_shape_cached_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_project_symbol_index_body_shape_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_project_symbol_index_body_shape_source_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_project_symbol_index_body_shape_walk_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_project_symbol_index_declaration_rows.hpp"
#include "__callable/__latency_fn_project_symbol_identity_function_debug_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_namespace_prefix.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_namespace_prefix.hpp"
#include "__callable/__latency_fn_project_symbol_index_namespace_relative_function_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_normalize_import_target_qualified_name.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_intern_qualified_name(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::intern_qualified_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[36]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->qualified_name_ids_by_text, value)))) {
		return index->qualified_name_ids_by_text[value];
	}
	(void) index->qualified_names.append(value);
	int_t<std::uint32_t> id = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(php::count(index->qualified_names)));
	index->qualified_name_ids_by_text[value] = id;
	return cast<int_t<std::uint32_t>>(id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_intern_signature_shape(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::intern_signature_shape", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[37]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->signature_shape_ids_by_text, value)))) {
		return index->signature_shape_ids_by_text[value];
	}
	(void) index->signature_shapes.append(value);
	int_t<std::uint32_t> id = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(php::count(index->signature_shapes)));
	index->signature_shape_ids_by_text[value] = id;
	return cast<int_t<std::uint32_t>>(id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_intern_body_shape(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::intern_body_shape", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[38]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->body_shape_ids_by_text, value)))) {
		return index->body_shape_ids_by_text[value];
	}
	(void) index->body_shapes.append(value);
	int_t<std::uint32_t> id = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(php::count(index->body_shapes)));
	index->body_shape_ids_by_text[value] = id;
	return cast<int_t<std::uint32_t>>(id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_intern_source_unit_key(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::intern_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[39]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->source_unit_key_ids_by_text, value)))) {
		return index->source_unit_key_ids_by_text[value];
	}
	(void) index->source_unit_keys.append(value);
	int_t<std::uint32_t> id = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(php::count(index->source_unit_keys)));
	index->source_unit_key_ids_by_text[value] = id;
	return cast<int_t<std::uint32_t>>(id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_add_u32(int_t<std::uint32_t> left, int_t<> right) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::add_u32", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[40]);
	return __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(left) + right));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_record_profile_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> index) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::record_profile_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[41]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_project_symbol_index_declaration_rows(), index->declaration_rows_indexed);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_project_symbol_index_body_shape_elapsed_us(), index->body_shape_elapsed_us);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_project_symbol_index_body_shape_source_bytes(), index->body_shape_source_bytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_project_symbol_index_body_shape_walk_rows(), index->body_shape_walk_rows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_project_symbol_index_body_shape_cached_rows(), index->body_shape_cached_rows);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_symbol_key(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[42]);
	return __latency_fn_project_symbol_identity_function_debug_string(__latency_fn_project_symbol_index_name(index, row));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_name(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[43]);
	return __latency_fn_project_symbol_index_materialized_string(index->names, row->name_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_qualified_name(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::qualified_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[44]);
	return __latency_fn_project_symbol_index_materialized_string(index->qualified_names, row->qualified_name_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_function_namespace_prefix(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_namespace_prefix", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[45]);
	string_t name = required_cast<string_t>(__latency_fn_project_symbol_index_name(index, row));
	string_t qualifiedName = required_cast<string_t>(__latency_fn_project_symbol_index_qualified_name(index, row));
	if (static_cast<bool>(((php::identical(name, string_t("")) || php::identical(qualifiedName, string_t(""))) || php::identical(qualifiedName, name)))) {
		return string_t("");
	}
	string_t suffix = required_cast<string_t>((string_t("\\") + cast<string_t>(name)));
	if (static_cast<bool>((!str::ends_with(qualifiedName, suffix)))) {
		return string_t("");
	}
	int_t<> prefixLength = required_cast<int_t<>>((str::byte_length(qualifiedName) - str::byte_length(suffix)));
	if (static_cast<bool>((prefixLength <= static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return str::byte_slice(qualifiedName, static_cast<int_t<> >(0), prefixLength);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_namespace_relative_function_qualified_name(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow fromSymbol, const string_t& calleeName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::namespace_relative_function_qualified_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[46]);
	string_t namespacePrefix = required_cast<string_t>(__latency_fn_project_symbol_index_function_namespace_prefix(index, fromSymbol));
	if (static_cast<bool>(((php::identical(namespacePrefix, string_t("")) || php::identical(calleeName, string_t(""))) || php::str_contains(calleeName, string_t("\\"))))) {
		return string_t("");
	}
	return (cast<string_t>(namespacePrefix) + string_t("\\") + cast<string_t>(calleeName));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_normalize_import_target_qualified_name(const string_t& targetName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::normalize_import_target_qualified_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[47]);
	if (static_cast<bool>((str::starts_with(targetName, string_t("\\")) && (str::byte_length(targetName) > static_cast<int_t<> >(1))))) {
		return str::byte_slice(targetName, static_cast<int_t<> >(1), (str::byte_length(targetName) - static_cast<int_t<> >(1)));
	}
	return targetName;
}

}
