#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/ResidentSourceUnitSymbolStateRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/resident_source_unit_symbol_states.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_states_from_scratch.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_built_current_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_owner_snapshots.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_project_symbol_index_sidecar.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_reused_previous_from_owner_snapshots.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_project_symbol_index_for_owner.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_append_states_from_scratch(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> projectSymbolIndexSidecarId, vector_t<int_t<std::uint32_t>>& kindIds, vector_t<int_t<std::uint32_t>>& firstSymbolIds, vector_t<int_t<std::uint32_t>>& symbolCounts, vector_t<int_t<std::uint32_t>>& previousStateIds) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_states_from_scratch", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[36]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_source_unit_symbol_states));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(sourceUnit->source_unit_id));
		int_t<std::uint16_t> kindId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_source_unit_symbol_states_state_kind_built_current_id());
		int_t<std::uint32_t> firstSymbolId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::uint32_t> symbolCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::uint32_t> previousStateId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		if (static_cast<bool>((((index >= static_cast<int_t<> >(0)) && (index < php::count(kindIds))) && (cast<int_t<>>(kindIds.at(index)) > static_cast<int_t<> >(0))))) {
			kindId = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(kindIds.at(index)));
			firstSymbolId = cast<int_t<std::uint32_t>>(firstSymbolIds.at(index));
			symbolCount = cast<int_t<std::uint32_t>>(symbolCounts.at(index));
			previousStateId = cast<int_t<std::uint32_t>>(previousStateIds.at(index));
		}
		ResidentSourceUnitSymbolStateRow row = ResidentSourceUnitSymbolStateRow{};
		row->owner_run_id = ownerRunId;
		row->source_unit_id = sourceUnit->source_unit_id;
		row->source_unit_key_id = sourceUnit->source_unit_key_id;
		row->previous_state_id = previousStateId;
		row->project_symbol_index_sidecar_id = projectSymbolIndexSidecarId;
		row->first_symbol_id = firstSymbolId;
		row->symbol_count = symbolCount;
		row->state_kind_id = kindId;
		row->status_id = __latency_fn_resident_source_unit_symbol_states_status_ready_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_symbol_states_blocked_reason_none_id();
		__latency_fn_resident_source_unit_symbol_states_append_state(report, row);
	}
	__latency_fn_resident_source_unit_symbol_states_append_memory_estimate(report, (php::count(report->resident_source_unit_symbol_states) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_append_reused_previous_from_owner_snapshots(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::append_reused_previous_from_owner_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[37]);
	shared_p<ProjectSymbolIndex> previousSymbols = __latency_fn_resident_source_unit_symbol_states_project_symbol_index_for_owner(previous, cast<int_t<std::uint32_t>>(ownerRunId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(previousSymbols->symbol_count), static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<std::uint32_t> sidecarId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_symbol_states_append_project_symbol_index_sidecar(report, previousSymbols));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_owner_snapshots(report, ownerRunId));
	vector_t<int_t<std::uint32_t>> previousStateIds = {};
	if (static_cast<bool>((slotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids(previous, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousStateIds);
		__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics(report, slotCount);
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_source_unit_symbol_states));
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitSymbolStateRow previousState = __latency_fn_resident_source_unit_symbol_states_state_from_lookup(report, previous, previousStateIds, cast<int_t<std::uint32_t>>(ownerRunId), snapshot->source_unit_id);
			if (static_cast<bool>((cast<int_t<>>(previousState->state_id) > static_cast<int_t<> >(0)))) {
				ResidentSourceUnitSymbolStateRow row = previousState;
				row->owner_run_id = ownerRunId;
				row->source_unit_id = snapshot->source_unit_id;
				row->source_unit_key_id = snapshot->source_unit_key_id;
				row->previous_state_id = previousState->state_id;
				row->project_symbol_index_sidecar_id = sidecarId;
				row->state_kind_id = __latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id();
				row->status_id = __latency_fn_resident_source_unit_symbol_states_status_ready_id();
				row->blocked_reason_id = __latency_fn_resident_source_unit_symbol_states_blocked_reason_none_id();
				__latency_fn_resident_source_unit_symbol_states_append_state(report, row);
			}
		}
	}
	__latency_fn_resident_source_unit_symbol_states_append_memory_estimate(report, (php::count(report->resident_source_unit_symbol_states) - startCount));
}

}
