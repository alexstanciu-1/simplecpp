#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentBodyDependencyRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/resident_body_dependencies.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_dependency_kind_symbol_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_dependency_kind_file_inline_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_lowering_policy_embedded_value_affects_backend_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_lowering_policy_runtime_lookup_no_body_refresh_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_status_active_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_append_dependency.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_append_dependency.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_append_embedded_symbol_value_dependency.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_dependency_kind_symbol_value_id.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_lowering_policy_embedded_value_affects_backend_id.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_status_active_id.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_append_targets_for_value_change.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_lowering_policy_embedded_value_affects_backend_id.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_status_active_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_recompute_local_lowering_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_refresh_backend_owner_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_target.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_backend_refresh_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_local_lowering_id.hpp"
namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
bool_t resident_body_dependencies::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_body_dependencies::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_body_dependencies_dependency_kind_symbol_value_id() {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::dependency_kind_symbol_value_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_body_dependencies_dependency_kind_file_inline_value_id() {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::dependency_kind_file_inline_value_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_body_dependencies_lowering_policy_embedded_value_affects_backend_id() {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::lowering_policy_embedded_value_affects_backend_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_body_dependencies_lowering_policy_runtime_lookup_no_body_refresh_id() {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::lowering_policy_runtime_lookup_no_body_refresh_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_body_dependencies_status_active_id() {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::status_active_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_body_dependencies_append_dependency(shared_p<CompilerProjectRunReport>& report, ResidentBodyDependencyRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::append_dependency", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[5]);
	row->dependency_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_body_dependencies));
	(void) report->resident_body_dependencies.append(row);
	report->resident_body_dependency_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_body_dependencies));
	return row->dependency_id;
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_body_dependencies_append_embedded_symbol_value_dependency(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> ownerSymbolId, int_t<std::uint32_t> dependencySourceUnitId, int_t<std::uint32_t> dependencySymbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::append_embedded_symbol_value_dependency", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[6]);
	ResidentBodyDependencyRow row = ResidentBodyDependencyRow{};
	row->owner_run_id = ownerRunId;
	row->owner_source_unit_id = ownerSourceUnitId;
	row->owner_symbol_id = ownerSymbolId;
	row->dependency_source_unit_id = dependencySourceUnitId;
	row->dependency_symbol_id = dependencySymbolId;
	row->dependency_kind_id = __latency_fn_resident_body_dependencies_dependency_kind_symbol_value_id();
	row->lowering_policy_id = __latency_fn_resident_body_dependencies_lowering_policy_embedded_value_affects_backend_id();
	row->status_id = __latency_fn_resident_body_dependencies_status_active_id();
	return __latency_fn_resident_body_dependencies_append_dependency(report, row);
}

}

namespace scpp { extern const int __latency_lines_resident_body_dependencies[]; }
namespace scpp {
void __latency_fn_resident_body_dependencies_append_targets_for_value_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_body_dependencies::append_targets_for_value_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_body_dependencies.phs", __latency_lines_resident_body_dependencies[7]);
	auto __latency_local_0 = report->resident_body_dependencies;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto dependency = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(dependency->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(dependency->dependency_symbol_id), cast<int_t<>>(change->symbol_id))) && php::identical(cast<int_t<>>(dependency->status_id), cast<int_t<>>(__latency_fn_resident_body_dependencies_status_active_id()))) && php::identical(cast<int_t<>>(dependency->lowering_policy_id), cast<int_t<>>(__latency_fn_resident_body_dependencies_lowering_policy_embedded_value_affects_backend_id()))))) {
			__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, dependency->owner_source_unit_id, dependency->owner_symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_local_lowering_id(), __latency_fn_resident_recompute_targets_action_recompute_local_lowering_id());
			__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, dependency->owner_source_unit_id, dependency->owner_symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_backend_refresh_id(), __latency_fn_resident_recompute_targets_action_refresh_backend_owner_id());
		}
	}
}

}
