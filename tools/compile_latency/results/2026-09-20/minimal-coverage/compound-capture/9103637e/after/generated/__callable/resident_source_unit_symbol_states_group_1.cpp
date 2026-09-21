#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentSourceUnitSymbolStateRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_source_unit_symbol_states.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_by_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_rebuilt_replacement_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_symbol_fact_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_row.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
ResidentSourceUnitSymbolStateRow __latency_fn_resident_source_unit_symbol_states_previous_state_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::previous_state_by_owner_and_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[13]);
	auto __latency_local_0 = report->resident_source_unit_symbol_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto state = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(state->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return state;
		}
	}
	ResidentSourceUnitSymbolStateRow empty = ResidentSourceUnitSymbolStateRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& stateIds) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::build_state_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[14]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(stateIds, slotCount);
	auto __latency_local_0 = report->resident_source_unit_symbol_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto state = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(state->source_unit_id, slotCount)))) {
			stateIds.at(__latency_fn_structure_row_ids_dense_index(state->source_unit_id)) = state->state_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_append_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[15]);
	report->resident_source_unit_symbol_state_lookup_slot_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
ResidentSourceUnitSymbolStateRow __latency_fn_resident_source_unit_symbol_states_state_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> stateReport, vector_t<int_t<std::uint32_t>>& stateIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::state_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[16]);
	metricsReport->resident_source_unit_symbol_state_lookup_probe_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_symbol_state_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(stateIds))))) {
		int_t<std::uint32_t> stateId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(stateIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(stateId), static_cast<int_t<> >(0)))) {
			ResidentSourceUnitSymbolStateRow empty = ResidentSourceUnitSymbolStateRow{};
			return empty;
		}
		ResidentSourceUnitSymbolStateRow state = __latency_fn_resident_source_unit_symbol_states_state_by_id(stateReport, cast<int_t<std::uint32_t>>(stateId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(state->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return state;
		}
	}
	metricsReport->resident_source_unit_symbol_state_lookup_fallback_scan_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_symbol_state_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_source_unit_symbol_states_previous_state_by_owner_and_source(stateReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(sourceUnitId));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
ResidentSourceUnitSymbolStateRow __latency_fn_resident_source_unit_symbol_states_append_state(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitSymbolStateRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_state", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[17]);
	row->state_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_source_unit_symbol_states));
	(void) report->resident_source_unit_symbol_states.append(row);
	report->resident_source_unit_symbol_state_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::count(report->resident_source_unit_symbol_states));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id())))) {
		report->resident_source_unit_symbol_state_reuse_previous_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_reuse_previous_count) + static_cast<int_t<> >(1)));
		report->resident_source_unit_symbol_state_reuse_previous_symbol_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_reuse_previous_symbol_count) + cast<int_t<>>(row->symbol_count)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_symbol_states_state_kind_rebuilt_replacement_id())))) {
			report->resident_source_unit_symbol_state_rebuilt_replacement_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_rebuilt_replacement_count) + static_cast<int_t<> >(1)));
			report->resident_source_unit_symbol_state_rebuilt_replacement_symbol_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_rebuilt_replacement_symbol_count) + cast<int_t<>>(row->symbol_count)));
		}
		else {
			report->resident_source_unit_symbol_state_built_current_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_built_current_count) + static_cast<int_t<> >(1)));
			report->resident_source_unit_symbol_state_built_current_symbol_count = __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_symbol_state_built_current_symbol_count) + cast<int_t<>>(row->symbol_count)));
		}
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[18]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(rowCount), __latency_fn_resident_source_unit_symbol_states_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentSourceUnitSymbolStateRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_row(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[19]);
	int_t<std::uint32_t> symbolStartId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> symbolCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnit->source_unit_id, php::count(symbolCounts))))) {
		int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(sourceUnit->source_unit_id));
		symbolStartId = cast<int_t<std::uint32_t>>(firstSymbolIds.at(index));
		symbolCount = cast<int_t<std::uint32_t>>(symbolCounts.at(index));
	}
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(symbolCount), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(8000) + cast<int_t<>>(sourceUnit->source_unit_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_source_unit_id(), sourceUnit->source_unit_id, __latency_fn_structure_row_ids_none_id(), sourceUnit->source_unit_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = symbolCount;
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6100000) + cast<int_t<>>(sourceUnit->source_unit_id)));
	row->published_row_first_id = symbolStartId;
	row->published_row_count = symbolCount;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_append_symbol_fact_publication_row(shared_p<PartitionReadinessArtifact>& artifact, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_symbol_fact_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[20]);
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_row(sourceUnit, cast<int_t<std::uint32_t>>(ownerRunId), firstSymbolIds, symbolCounts));
}

}
