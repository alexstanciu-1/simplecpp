#include <scpp/lang/php.hpp>
#include "__types/ProjectSymbolIndex.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_literal_status_ready.hpp"
#include "__callable/__latency_fn_project_symbol_index_literal_status_ready.hpp"
#include "__callable/__latency_fn_project_symbol_index_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_lookup_policy_scan_ok_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_lookup_probe.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_scope_global_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_scope_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_status_indexed_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_status_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_ref_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_sidecar_string_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_sidecar_string_byte_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_sidecar_lookup_entry_count.hpp"
#include "__callable/__latency_fn_project_symbol_index_sidecar_string_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_index_literal_status_ready(int_t<std::uint16_t> literalStatusId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::literal_status_ready", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[14]);
	return __latency_fn_frontend_body_summaries_literal_status_ready(literalStatusId);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_update_lookup_policy(shared_p<ProjectSymbolIndex> index) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::update_lookup_policy", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[15]);
	if (static_cast<bool>((cast<int_t<>>(index->symbol_count) > static_cast<int_t<> >(32)))) {
		index->lookup_policy_status_id = __latency_fn_project_symbol_index_lookup_policy_index_recommended_id();
	}
	else {
		index->lookup_policy_status_id = __latency_fn_project_symbol_index_lookup_policy_scan_ok_id();
	}
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_record_lookup_probe(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> scannedRows) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::record_lookup_probe", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[16]);
	index->lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(index->lookup_probe_count) + static_cast<int_t<> >(1)));
	index->lookup_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(index->lookup_fallback_scan_count) + cast<int_t<>>(scannedRows)));
	__latency_fn_project_symbol_index_update_lookup_policy(index);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_symbol_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::symbol_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())))) {
		return string_t("function");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_scope_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::scope_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[18]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_symbol_index_scope_global_id())))) {
		return string_t("global");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_symbol_index_status_indexed_id())))) {
		return string_t("indexed");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_type_ref_name(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::type_ref_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[20]);
	return __latency_fn_type_ref_identity_name(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_materialized_string(const vector_t<string_t>& values, int_t<std::uint32_t> id, const string_t& fallback) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::materialized_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[21]);
	int_t<> index = required_cast<int_t<>>(cast<int_t<>>(id));
	if (static_cast<bool>(((index > static_cast<int_t<> >(0)) && (index <= php::count(values))))) {
		return values.at((index - static_cast<int_t<> >(1)));
	}
	return fallback;
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_sidecar_string_count(shared_p<ProjectSymbolIndex> index) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::sidecar_string_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[22]);
	return __latency_fn_structure_row_ids_uint32_from_int(((((php::count(index->names) + php::count(index->qualified_names)) + php::count(index->signature_shapes)) + php::count(index->body_shapes)) + php::count(index->source_unit_keys)));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_sidecar_string_byte_count(shared_p<ProjectSymbolIndex> index) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::sidecar_string_byte_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[23]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = index->names;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto value = __latency_local_1.value_copy();
		total = (total + str::byte_length(value));
	}
	auto __latency_local_2 = index->qualified_names;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto value = __latency_local_3.value_copy();
		total = (total + str::byte_length(value));
	}
	auto __latency_local_4 = index->signature_shapes;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto value = __latency_local_5.value_copy();
		total = (total + str::byte_length(value));
	}
	auto __latency_local_6 = index->body_shapes;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto value = __latency_local_7.value_copy();
		total = (total + str::byte_length(value));
	}
	auto __latency_local_8 = index->source_unit_keys;
	for (auto __latency_local_9 : foreach_range(__latency_local_8)) {
		auto value = __latency_local_9.value_copy();
		total = (total + str::byte_length(value));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_sidecar_lookup_entry_count(shared_p<ProjectSymbolIndex> index) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::sidecar_lookup_entry_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[24]);
	int_t<> stringLookupEntries = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_project_symbol_index_sidecar_string_count(index)));
	int_t<> functionLookupSlots = required_cast<int_t<>>((((php::count(index->function_name_symbol_ids) + php::count(index->function_name_match_counts)) + php::count(index->function_qualified_name_symbol_ids)) + php::count(index->function_qualified_name_match_counts)));
	int_t<> functionImportSlots = required_cast<int_t<>>((cast<int_t<>>(index->function_import_count) * static_cast<int_t<> >(3)));
	return __latency_fn_structure_row_ids_uint32_from_int(((stringLookupEntries + functionLookupSlots) + functionImportSlots));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_intern_name(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::intern_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[25]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->name_ids_by_text, value)))) {
		return index->name_ids_by_text[value];
	}
	(void) index->names.append(value);
	int_t<std::uint32_t> id = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(php::count(index->names)));
	index->name_ids_by_text[value] = id;
	return cast<int_t<std::uint32_t>>(id);
}

}
