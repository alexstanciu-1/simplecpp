#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/resident_source_unit_frontend_states.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_built_from_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_built_from_counts_ref.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_owner_snapshots.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_build_state_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_by_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_reused_frontend_model_sidecar_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state_snapshot.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_reused_frontend_model_sidecar_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_built_from_counts(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, shared_p<FrontendModel> model, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenSegmentReservedBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeSegmentReservedBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> declarationCount, int_t<std::uint32_t> statementCount, int_t<std::uint32_t> expressionCount, int_t<std::uint32_t> parserErrorCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_built_from_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[21]);
	return __latency_fn_resident_source_unit_frontend_states_append_built_from_counts_ref(report, cast<int_t<std::uint32_t>>(ownerRunId), sourceUnit, model, cast<int_t<std::uint16_t>>(stateKindId), cast<int_t<std::uint32_t>>(tokenCount), cast<int_t<std::uint32_t>>(tokenSegmentCount), cast<int_t<std::uint32_t>>(tokenSegmentReservedBytes), cast<int_t<std::uint32_t>>(tokenSegmentSlackBytes), cast<int_t<std::uint32_t>>(frontendNodeCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentReservedBytes), cast<int_t<std::uint32_t>>(frontendNodeSegmentSlackBytes), cast<int_t<std::uint32_t>>(declarationCount), cast<int_t<std::uint32_t>>(statementCount), cast<int_t<std::uint32_t>>(expressionCount), cast<int_t<std::uint32_t>>(parserErrorCount));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_source_units(shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::max_source_unit_id_from_source_units", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[22]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(sourceUnit->source_unit_id) > maxId))) {
			maxId = cast<int_t<>>(sourceUnit->source_unit_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_owner_snapshots(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::max_source_unit_id_from_owner_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[23]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->source_unit_id) > maxId)))) {
			maxId = cast<int_t<>>(snapshot->source_unit_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_states_build_state_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& stateIds) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::build_state_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[24]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(stateIds, slotCount);
	auto __latency_local_0 = report->resident_source_unit_frontend_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto state = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(state->source_unit_id, slotCount)))) {
			stateIds.at(__latency_fn_structure_row_ids_dense_index(state->source_unit_id)) = state->state_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_states_append_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[25]);
	report->resident_source_unit_frontend_state_lookup_slot_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_state_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_state_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> stateReport, vector_t<int_t<std::uint32_t>>& stateIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::state_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[26]);
	metricsReport->resident_source_unit_frontend_state_lookup_probe_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_state_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(stateIds))))) {
		int_t<std::uint32_t> stateId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(stateIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(stateId), static_cast<int_t<> >(0)))) {
			ResidentSourceUnitFrontendStateRow empty = ResidentSourceUnitFrontendStateRow{};
			return empty;
		}
		ResidentSourceUnitFrontendStateRow state = __latency_fn_resident_source_unit_frontend_states_state_by_id(stateReport, cast<int_t<std::uint32_t>>(stateId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(state->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return state;
		}
	}
	metricsReport->resident_source_unit_frontend_state_lookup_fallback_scan_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_state_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source(stateReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(sourceUnitId));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, ResidentSourceUnitFrontendStateRow previousState) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_reused_previous_from_state", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[27]);
	int_t<std::uint32_t> sidecarId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_states_reused_frontend_model_sidecar_id(report, previous, previousState));
	ResidentSourceUnitFrontendStateRow row = previousState;
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnit->source_unit_id;
	row->source_unit_key_id = sourceUnit->source_unit_key_id;
	row->previous_state_id = previousState->state_id;
	row->frontend_model_sidecar_id = sidecarId;
	row->source_status_id = sourceUnit->status_id;
	row->state_kind_id = __latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id();
	row->status_id = __latency_fn_resident_source_unit_frontend_states_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id();
	ResidentSourceUnitFrontendStateRow result = __latency_fn_resident_source_unit_frontend_states_append_state(report, row);
	__latency_fn_resident_source_unit_frontend_states_append_memory_estimate(report, static_cast<int_t<> >(1));
	return result;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_reused_previous(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_reused_previous", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[28]);
	ResidentSourceUnitFrontendStateRow previousState = __latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source(previous, cast<int_t<std::uint32_t>>(ownerRunId), sourceUnit->source_unit_id);
	return __latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state(report, previous, cast<int_t<std::uint32_t>>(ownerRunId), sourceUnit, previousState);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state_snapshot(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitSnapshotRow snapshot, ResidentSourceUnitFrontendStateRow previousState) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_reused_previous_from_state_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[29]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previousState->state_id), static_cast<int_t<> >(0)))) {
		ResidentSourceUnitFrontendStateRow empty = ResidentSourceUnitFrontendStateRow{};
		return empty;
	}
	int_t<std::uint32_t> sidecarId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_states_reused_frontend_model_sidecar_id(report, previous, previousState));
	ResidentSourceUnitFrontendStateRow row = previousState;
	row->owner_run_id = snapshot->owner_run_id;
	row->source_unit_id = snapshot->source_unit_id;
	row->source_unit_key_id = snapshot->source_unit_key_id;
	row->previous_state_id = previousState->state_id;
	row->frontend_model_sidecar_id = sidecarId;
	row->source_status_id = snapshot->status_id;
	row->state_kind_id = __latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id();
	row->status_id = __latency_fn_resident_source_unit_frontend_states_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id();
	ResidentSourceUnitFrontendStateRow result = __latency_fn_resident_source_unit_frontend_states_append_state(report, row);
	__latency_fn_resident_source_unit_frontend_states_append_memory_estimate(report, static_cast<int_t<> >(1));
	return result;
}

}
