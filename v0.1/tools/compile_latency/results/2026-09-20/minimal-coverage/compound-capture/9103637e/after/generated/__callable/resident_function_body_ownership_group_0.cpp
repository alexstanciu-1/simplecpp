#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/ProjectFrontendModel.hpp"
#include "__types/ProjectFrontendSourceRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/resident_function_body_ownership.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_built_current_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_status_current_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_model_by_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_max_source_unit_id_from_symbols.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_model_index_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_project_frontend_state_handle_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_probe.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
bool_t resident_function_body_ownership::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_ownership::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_ownership_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_ownership_snapshot_kind_built_current_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::snapshot_kind_built_current_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::snapshot_kind_reused_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_ownership_status_current_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::status_current_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_ownership_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[4]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
shared_p<FrontendModel> __latency_fn_resident_function_body_ownership_model_by_source_unit_id(shared_p<ProjectFrontendModel> project, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::model_by_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[5]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = project->source_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceRow = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceRow->source_unit_id), cast<int_t<>>(sourceUnitId)))) {
			if (static_cast<bool>((index < php::count(project->models)))) {
				return project->models[index];
			}
			shared_p<FrontendModel> empty = create<FrontendModel>();
			return empty;
		}
		index = (index + static_cast<int_t<> >(1));
	}
	shared_p<FrontendModel> empty = create<FrontendModel>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_ownership_max_source_unit_id_from_symbols(shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::max_source_unit_id_from_symbols", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[6]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(symbol->source_unit_id) > maxId))) {
			maxId = cast<int_t<>>(symbol->source_unit_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_build_model_index_lookup_ids(shared_p<ProjectFrontendModel> project, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& modelIndexIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::build_model_index_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[7]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(modelIndexIds, slotCount);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = project->source_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceRow = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceRow->source_unit_id, slotCount)))) {
			modelIndexIds.at(__latency_fn_structure_row_ids_dense_index(sourceRow->source_unit_id)) = __latency_fn_resident_function_body_ownership_uint32_from_int((index + static_cast<int_t<> >(1)));
		}
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_ownership_build_project_frontend_state_handle_lookup_ids(shared_p<ProjectFrontendModel> project, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& frontendStateIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::build_project_frontend_state_handle_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[8]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(frontendStateIds, slotCount);
	int_t<> selected = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = project->source_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceRow = __latency_local_1.value_copy();
		if (static_cast<bool>((__latency_fn_structure_row_ids_has_dense_id(sourceRow->source_unit_id, slotCount) && (index < php::count(project->frontend_state_ids))))) {
			int_t<std::uint32_t> stateId = required_cast<int_t<std::uint32_t>>(project->frontend_state_ids[index]);
			if (static_cast<bool>((cast<int_t<>>(stateId) > static_cast<int_t<> >(0)))) {
				frontendStateIds.at(__latency_fn_structure_row_ids_dense_index(sourceRow->source_unit_id)) = stateId;
				selected = (selected + static_cast<int_t<> >(1));
			}
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_resident_function_body_ownership_uint32_from_int(selected);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_append_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::append_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[9]);
	report->resident_function_body_ownership_lookup_slot_count = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(report->resident_function_body_ownership_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_increment_lookup_probe(shared_p<CompilerProjectRunReport>& report) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::increment_lookup_probe", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[10]);
	report->resident_function_body_ownership_lookup_probe_count = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(report->resident_function_body_ownership_lookup_probe_count) + static_cast<int_t<> >(1)));
}

}
