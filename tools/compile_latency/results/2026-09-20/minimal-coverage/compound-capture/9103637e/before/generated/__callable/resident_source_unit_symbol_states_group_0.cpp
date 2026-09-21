#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ResidentSourceUnitSymbolStateRow.hpp"
#include "__types/resident_source_unit_symbol_states.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_built_current_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_rebuilt_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_recompute_trial_requested.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_reserve_symbol_state_scratch.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_record_symbol_state_scratch.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_project_symbol_index_sidecar.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_new_index.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_project_symbol_index_by_sidecar_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_new_index.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_project_symbol_index_by_sidecar_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_project_symbol_index_for_owner.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
bool_t resident_source_unit_symbol_states::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_source_unit_symbol_states::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_symbol_states_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_symbol_states_state_kind_built_current_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::state_kind_built_current_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::state_kind_reused_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_symbol_states_state_kind_rebuilt_replacement_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::state_kind_rebuilt_replacement_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_symbol_states_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_symbol_states_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[5]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_recompute_trial_requested() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_recompute_trial_requested", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[6]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_SYMBOL_FACT_WORKER_RECOMPUTE_TRIAL")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_reserve_symbol_state_scratch(int_t<> slotCount, vector_t<int_t<std::uint32_t>>& kindIds, vector_t<int_t<std::uint32_t>>& firstSymbolIds, vector_t<int_t<std::uint32_t>>& symbolCounts, vector_t<int_t<std::uint32_t>>& previousStateIds) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::reserve_symbol_state_scratch", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[7]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(kindIds, slotCount);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(firstSymbolIds, slotCount);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(symbolCounts, slotCount);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(previousStateIds, slotCount);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_record_symbol_state_scratch(vector_t<int_t<std::uint32_t>>& kindIds, vector_t<int_t<std::uint32_t>>& firstSymbolIds, vector_t<int_t<std::uint32_t>>& symbolCounts, vector_t<int_t<std::uint32_t>>& previousStateIds, int_t<std::uint32_t> sourceUnitId, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> firstSymbolId, int_t<std::uint32_t> symbolCount, int_t<std::uint32_t> previousStateId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::record_symbol_state_scratch", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[8]);
	if (static_cast<bool>((!__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(kindIds))))) {
		return;
	}
	int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(sourceUnitId));
	kindIds.at(index) = __latency_fn_resident_source_unit_symbol_states_uint32_from_int(cast<int_t<>>(stateKindId));
	firstSymbolIds.at(index) = firstSymbolId;
	symbolCounts.at(index) = symbolCount;
	previousStateIds.at(index) = previousStateId;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_symbol_states_append_project_symbol_index_sidecar(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_project_symbol_index_sidecar", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[9]);
	(void) report->resident_source_unit_symbol_indexes.append(symbols);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_source_unit_symbol_indexes));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
shared_p<ProjectSymbolIndex> __latency_fn_resident_source_unit_symbol_states_project_symbol_index_by_sidecar_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> sidecarId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::project_symbol_index_by_sidecar_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[10]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sidecarId, php::count(report->resident_source_unit_symbol_indexes))))) {
		return report->resident_source_unit_symbol_indexes[__latency_fn_structure_row_ids_dense_index(sidecarId)];
	}
	return __latency_fn_project_symbol_index_new_index(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
shared_p<ProjectSymbolIndex> __latency_fn_resident_source_unit_symbol_states_project_symbol_index_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::project_symbol_index_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[11]);
	auto __latency_local_0 = report->resident_source_unit_symbol_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto state = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(state->project_symbol_index_sidecar_id) > static_cast<int_t<> >(0))))) {
			return __latency_fn_resident_source_unit_symbol_states_project_symbol_index_by_sidecar_id(report, state->project_symbol_index_sidecar_id);
		}
	}
	return __latency_fn_project_symbol_index_new_index(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
ResidentSourceUnitSymbolStateRow __latency_fn_resident_source_unit_symbol_states_state_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> stateId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::state_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[12]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(stateId, php::count(report->resident_source_unit_symbol_states))))) {
		ResidentSourceUnitSymbolStateRow row = report->resident_source_unit_symbol_states[__latency_fn_structure_row_ids_dense_index(stateId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_id), cast<int_t<>>(stateId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_source_unit_symbol_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_id), cast<int_t<>>(stateId)))) {
			return row;
		}
	}
	ResidentSourceUnitSymbolStateRow empty = ResidentSourceUnitSymbolStateRow{};
	return empty;
}

}
