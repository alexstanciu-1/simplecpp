#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentDirtyProcessedRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_source_change_by_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_symbol_change_by_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_entity_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_entity_kind_symbol_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_dirty_processed_row.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_source_change.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_symbol_change.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_source_change_by_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_symbol_change_by_id.hpp"
#include "__callable/__latency_fn_resident_change_events_append_from_owner_changes.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_from_owner_events.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_process_queue_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_processed_status_can_select.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_dirty_processed_row.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_owner.hpp"
namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
ResidentSourceUnitChangeRow __latency_fn_resident_recompute_targets_source_change_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> changeId) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::source_change_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[23]);
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_id), cast<int_t<>>(changeId)))) {
			return change;
		}
	}
	ResidentSourceUnitChangeRow row = ResidentSourceUnitChangeRow{};
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
ResidentSymbolDefinitionChangeRow __latency_fn_resident_recompute_targets_symbol_change_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> changeId) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::symbol_change_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[24]);
	auto __latency_local_0 = report->resident_symbol_definition_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_id), cast<int_t<>>(changeId)))) {
			return change;
		}
	}
	ResidentSymbolDefinitionChangeRow row = ResidentSymbolDefinitionChangeRow{};
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_targets_for_dirty_processed_row(shared_p<CompilerProjectRunReport>& report, ResidentDirtyProcessedRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_targets_for_dirty_processed_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[25]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->entity_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_entity_kind_source_unit_id())))) {
		ResidentSourceUnitChangeRow sourceChange = __latency_fn_resident_recompute_targets_source_change_by_id(report, row->reason_change_id);
		if (static_cast<bool>((cast<int_t<>>(sourceChange->change_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_resident_recompute_targets_append_targets_for_source_change(report, sourceChange);
		}
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->entity_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_entity_kind_symbol_id())))) {
		ResidentSymbolDefinitionChangeRow symbolChange = __latency_fn_resident_recompute_targets_symbol_change_by_id(report, row->reason_change_id);
		if (static_cast<bool>((cast<int_t<>>(symbolChange->change_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_resident_recompute_targets_append_targets_for_symbol_change(report, symbolChange);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_targets_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_targets_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[26]);
	__latency_fn_resident_change_events_append_from_owner_changes(report, ownerRunId);
	__latency_fn_resident_dirty_propagation_append_from_owner_events(report, ownerRunId);
	__latency_fn_resident_dirty_propagation_process_queue_for_owner(report, ownerRunId);
	auto __latency_local_0 = report->resident_dirty_processed_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_resident_dirty_propagation_processed_status_can_select(row->status_id)))) {
			__latency_fn_resident_recompute_targets_append_targets_for_dirty_processed_row(report, row);
		}
	}
}

}
