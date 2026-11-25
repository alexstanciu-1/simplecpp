#include <scpp/lang/php.hpp>
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__callable/__latency_fn_project_reference_resolution_artifact_acceptance_status_analyzer_only_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_artifact_kind_project_reference_resolution_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reserve_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_model_frontend_call_expression_symbol_lookup_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_source_model_frontend_symbol_index_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reserve_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_direct_function_symbol_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_multiple_matching_symbols_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_matching_symbol_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_ambiguous_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_missing_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_unresolved_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_materialized_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_from_symbol_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_from_source_unit_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_callee_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_resolved_symbol_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
shared_p<ProjectReferenceResolution> __latency_fn_project_reference_resolution_new_artifact(int_t<> referenceCapacity) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[16]);
	shared_p<ProjectReferenceResolution> artifact = create<ProjectReferenceResolution>();
	artifact->artifact_kind_id = __latency_fn_project_reference_resolution_artifact_kind_project_reference_resolution_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_project_reference_resolution_source_model_frontend_symbol_index_id();
	artifact->resolution_model_id = __latency_fn_project_reference_resolution_resolution_model_frontend_call_expression_symbol_lookup_id();
	artifact->acceptance_status_id = __latency_fn_project_reference_resolution_artifact_acceptance_status_analyzer_only_id();
	__latency_fn_project_reference_resolution_reserve_artifact(artifact, referenceCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_reserve_artifact(shared_p<ProjectReferenceResolution> artifact, int_t<> referenceCapacity) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reserve_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[17]);
	php::vector_reserve(artifact->rows, referenceCapacity);
	php::vector_reserve(artifact->from_symbol_keys, referenceCapacity);
	php::vector_reserve(artifact->from_source_unit_keys, referenceCapacity);
	php::vector_reserve(artifact->callee_names, referenceCapacity);
	php::vector_reserve(artifact->resolved_symbol_keys, referenceCapacity);
	php::vector_reserve(artifact->resolved_source_unit_keys, referenceCapacity);
	php::vector_reserve(artifact->actual_arguments, (referenceCapacity * static_cast<int_t<> >(2)));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_resolution_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::resolution_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[18]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_direct_function_symbol_id())))) {
		return string_t("direct_function_symbol");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_no_matching_symbol_id())))) {
		return string_t("no_matching_symbol");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_multiple_matching_symbols_id())))) {
		return string_t("multiple_matching_symbols");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id())))) {
		return string_t("no_call_expression");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id())))) {
		return string_t("resolved");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_missing_id())))) {
		return string_t("missing");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_ambiguous_id())))) {
		return string_t("ambiguous");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_unresolved_id())))) {
		return string_t("unresolved");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_materialized_string(const vector_t<string_t>& values, int_t<std::uint32_t> id, const string_t& fallback) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::materialized_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[20]);
	int_t<> index = required_cast<int_t<>>(cast<int_t<>>(id));
	if (static_cast<bool>(((index > static_cast<int_t<> >(0)) && (index <= php::count(values))))) {
		return values.at((index - static_cast<int_t<> >(1)));
	}
	return fallback;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_intern_from_symbol_key(shared_p<ProjectReferenceResolution> artifact, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::intern_from_symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[21]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->from_symbol_keys;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, value))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->from_symbol_keys.append(value);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->from_symbol_keys));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_intern_from_source_unit_key(shared_p<ProjectReferenceResolution> artifact, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::intern_from_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[22]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->from_source_unit_keys;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, value))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->from_source_unit_keys.append(value);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->from_source_unit_keys));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_intern_callee_name(shared_p<ProjectReferenceResolution> artifact, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::intern_callee_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[23]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->callee_names;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, value))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->callee_names.append(value);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->callee_names));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_intern_resolved_symbol_key(shared_p<ProjectReferenceResolution> artifact, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::intern_resolved_symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[24]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->resolved_symbol_keys;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, value))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->resolved_symbol_keys.append(value);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->resolved_symbol_keys));
}

}
