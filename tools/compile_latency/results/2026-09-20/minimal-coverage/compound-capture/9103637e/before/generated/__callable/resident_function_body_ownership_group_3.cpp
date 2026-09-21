#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ProjectFrontendModel.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/ResidentSourceUnitSymbolStateRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_function_body_ownership_project_state_handle_lookup_selected.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_all_symbols_have_body_owner_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_snapshots_from_project.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_model_index_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_project_frontend_state_handle_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_max_source_unit_id_from_symbols.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_row_from_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_copy_reused_snapshot.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_status_current_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_copy_reused_snapshot.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_reused_previous_snapshot_for_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_by_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_function_body_ownership_project_state_handle_lookup_selected.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_all_symbols_have_body_owner_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_snapshots_from_project_with_reuse.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_model_index_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_project_frontend_state_handle_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_frontend_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_max_source_unit_id_from_symbols.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_max_symbol_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_reused_previous_snapshot_for_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_row_from_symbol.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_from_lookup.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[27]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_ownership_uint32_from_int(rowCount), __latency_fn_resident_function_body_ownership_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodySnapshotRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_append_snapshots_from_project(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectFrontendModel> project, shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::append_snapshots_from_project", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[28]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_snapshots));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_function_body_ownership_max_source_unit_id_from_symbols(symbols));
	vector_t<int_t<std::uint32_t>> modelIndexIds = {};
	vector_t<int_t<std::uint32_t>> frontendStateIds = {};
	vector_t<int_t<std::uint32_t>> frontendSnapshotIds = {};
	bool_t needsModelLookup = required_cast<bool_t>((!__latency_fn_resident_function_body_ownership_all_symbols_have_body_owner_metadata(symbols)));
	if (static_cast<bool>((slotCount > static_cast<int_t<> >(0)))) {
		if (static_cast<bool>(php::condition_truthy(needsModelLookup))) {
			__latency_fn_resident_function_body_ownership_build_model_index_lookup_ids(project, slotCount, modelIndexIds);
		}
		int_t<std::uint32_t> projectStateHandleRows = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_ownership_build_project_frontend_state_handle_lookup_ids(project, slotCount, frontendStateIds));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_function_body_ownership_project_state_handle_lookup_selected(), projectStateHandleRows);
		__latency_fn_resident_frontend_node_lists_build_snapshot_lookup_ids(report, ownerRunId, slotCount, frontendSnapshotIds);
		int_t<> lookupSlotCount = required_cast<int_t<>>((slotCount * static_cast<int_t<> >(2)));
		if (static_cast<bool>(php::condition_truthy(needsModelLookup))) {
			lookupSlotCount = (lookupSlotCount + slotCount);
		}
		__latency_fn_resident_function_body_ownership_append_lookup_metrics(report, lookupSlotCount);
	}
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		__latency_fn_resident_function_body_ownership_append_snapshot(report, __latency_fn_resident_function_body_ownership_row_from_symbol(project, symbols, symbol, report, cast<int_t<std::uint32_t>>(ownerRunId), modelIndexIds, frontendStateIds, frontendSnapshotIds));
	}
	__latency_fn_resident_function_body_ownership_append_memory_estimate(report, (php::count(report->resident_function_body_snapshots) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_copy_reused_snapshot(ResidentFunctionBodySnapshotRow previous, ProjectSymbolIndexRow symbol, ResidentSourceUnitFrontendStateRow frontendState, ResidentFrontendNodeListSnapshotRow frontendSnapshot, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::copy_reused_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[29]);
	ResidentFunctionBodySnapshotRow row = previous;
	row->owner_run_id = ownerRunId;
	row->source_unit_id = symbol->source_unit_id;
	row->symbol_id = symbol->symbol_id;
	row->declaration_node_id = symbol->source_row_id;
	row->frontend_state_id = frontendState->state_id;
	row->frontend_node_list_snapshot_id = frontendSnapshot->snapshot_id;
	row->snapshot_kind_id = __latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id();
	row->status_id = __latency_fn_resident_function_body_ownership_status_current_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_ownership_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_reused_previous_snapshot_for_symbol(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> previousReport, ProjectSymbolIndexRow symbol, ResidentSourceUnitSymbolStateRow currentState, ResidentSourceUnitFrontendStateRow frontendState, ResidentFrontendNodeListSnapshotRow frontendSnapshot, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::reused_previous_snapshot_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[30]);
	ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(currentState->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id()))))) {
		return empty;
	}
	if (static_cast<bool>((((php::identical(cast<int_t<>>(currentState->previous_state_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(currentState->first_symbol_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(frontendState->state_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(frontendSnapshot->snapshot_id), static_cast<int_t<> >(0))))) {
		return empty;
	}
	ResidentSourceUnitSymbolStateRow previousState = __latency_fn_resident_source_unit_symbol_states_state_by_id(previousReport, currentState->previous_state_id);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(previousState->state_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(previousState->first_symbol_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(previousState->symbol_count), cast<int_t<>>(currentState->symbol_count))))) {
		return empty;
	}
	int_t<> offset = required_cast<int_t<>>((cast<int_t<>>(symbol->symbol_id) - cast<int_t<>>(currentState->first_symbol_id)));
	if (static_cast<bool>(((offset < static_cast<int_t<> >(0)) || (offset >= cast<int_t<>>(currentState->symbol_count))))) {
		return empty;
	}
	int_t<std::uint32_t> previousSymbolId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(previousState->first_symbol_id) + offset)));
	ResidentFunctionBodySnapshotRow previousSnapshot = __latency_fn_resident_function_body_ownership_snapshot_from_lookup(metricsReport, previousReport, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(previousSymbolId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(previousSnapshot->snapshot_id), static_cast<int_t<> >(0)))) {
		return empty;
	}
	return __latency_fn_resident_function_body_ownership_copy_reused_snapshot(previousSnapshot, symbol, frontendState, frontendSnapshot, cast<int_t<std::uint32_t>>(ownerRunId));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_append_snapshots_from_project_with_reuse(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, shared_p<ProjectFrontendModel> project, shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::append_snapshots_from_project_with_reuse", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[31]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_snapshots));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_function_body_ownership_max_source_unit_id_from_symbols(symbols));
	vector_t<int_t<std::uint32_t>> modelIndexIds = {};
	vector_t<int_t<std::uint32_t>> frontendStateIds = {};
	vector_t<int_t<std::uint32_t>> frontendSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> symbolStateIds = {};
	bool_t needsModelLookup = required_cast<bool_t>((!__latency_fn_resident_function_body_ownership_all_symbols_have_body_owner_metadata(symbols)));
	if (static_cast<bool>((slotCount > static_cast<int_t<> >(0)))) {
		if (static_cast<bool>(php::condition_truthy(needsModelLookup))) {
			__latency_fn_resident_function_body_ownership_build_model_index_lookup_ids(project, slotCount, modelIndexIds);
		}
		int_t<std::uint32_t> projectStateHandleRows = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_ownership_build_project_frontend_state_handle_lookup_ids(project, slotCount, frontendStateIds));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_function_body_ownership_project_state_handle_lookup_selected(), projectStateHandleRows);
		__latency_fn_resident_frontend_node_lists_build_snapshot_lookup_ids(report, ownerRunId, slotCount, frontendSnapshotIds);
		__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids(report, ownerRunId, slotCount, symbolStateIds);
		int_t<> lookupSlotCount = required_cast<int_t<>>((slotCount * static_cast<int_t<> >(2)));
		if (static_cast<bool>(php::condition_truthy(needsModelLookup))) {
			lookupSlotCount = (lookupSlotCount + slotCount);
		}
		__latency_fn_resident_function_body_ownership_append_lookup_metrics(report, lookupSlotCount);
		__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics(report, slotCount);
	}
	int_t<> previousSnapshotSlotCount = required_cast<int_t<>>(__latency_fn_resident_function_body_ownership_max_symbol_id_for_owner(previousReport, cast<int_t<std::uint32_t>>(ownerRunId)));
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	if (static_cast<bool>((previousSnapshotSlotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_function_body_ownership_build_snapshot_lookup_ids(previousReport, cast<int_t<std::uint32_t>>(ownerRunId), previousSnapshotSlotCount, previousSnapshotIds);
		__latency_fn_resident_function_body_ownership_append_lookup_metrics(report, previousSnapshotSlotCount);
	}
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		ResidentSourceUnitFrontendStateRow frontendState = __latency_fn_resident_function_body_ownership_frontend_state_from_lookup(report, report, frontendStateIds, cast<int_t<std::uint32_t>>(ownerRunId), symbol->source_unit_id);
		ResidentFrontendNodeListSnapshotRow frontendSnapshot = __latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup(report, report, frontendSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), symbol->source_unit_id);
		ResidentSourceUnitSymbolStateRow symbolState = __latency_fn_resident_source_unit_symbol_states_state_from_lookup(report, report, symbolStateIds, ownerRunId, symbol->source_unit_id);
		ResidentFunctionBodySnapshotRow reused = __latency_fn_resident_function_body_ownership_reused_previous_snapshot_for_symbol(report, previousReport, symbol, symbolState, frontendState, frontendSnapshot, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId));
		if (static_cast<bool>((cast<int_t<>>(reused->snapshot_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_resident_function_body_ownership_append_snapshot(report, reused);
		}
		else {
			__latency_fn_resident_function_body_ownership_append_snapshot(report, __latency_fn_resident_function_body_ownership_row_from_symbol(project, symbols, symbol, report, cast<int_t<std::uint32_t>>(ownerRunId), modelIndexIds, frontendStateIds, frontendSnapshotIds));
		}
	}
	__latency_fn_resident_function_body_ownership_append_memory_estimate(report, (php::count(report->resident_function_body_snapshots) - startCount));
}

}
