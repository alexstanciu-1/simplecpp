#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyChangeRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_change_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_decision_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_symbol_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_change_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_decision_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_from_symbol_definition_changes.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_from_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_max_symbol_id_from_symbol_changes.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_append_decision(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyWorkDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::append_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[30]);
	row->decision_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_work_decisions));
	if (static_cast<bool>((cast<int_t<>>(row->current_function_body_snapshot_id) > static_cast<int_t<> >(0)))) {
		ResidentFunctionBodySnapshotRow currentSnapshot = __latency_fn_resident_function_body_work_decisions_snapshot_by_id(report, row->current_function_body_snapshot_id);
		row->current_frontend_state_id = currentSnapshot->frontend_state_id;
		row->current_frontend_node_list_snapshot_id = currentSnapshot->frontend_node_list_snapshot_id;
	}
	(void) report->resident_function_body_work_decisions.append(row);
	report->resident_function_body_work_decision_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int(php::count(report->resident_function_body_work_decisions));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id())))) {
		report->resident_function_body_work_reuse_previous_body_rows_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_work_reuse_previous_body_rows_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id())))) {
			report->resident_function_body_work_parse_replacement_body_rows_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_work_parse_replacement_body_rows_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id())))) {
				report->resident_function_body_work_publish_public_surface_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_work_publish_public_surface_count) + static_cast<int_t<> >(1)));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id())))) {
					report->resident_function_body_work_cleanup_deleted_body_rows_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_work_cleanup_deleted_body_rows_count) + static_cast<int_t<> >(1)));
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id())))) {
						report->resident_function_body_work_build_new_body_rows_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_work_build_new_body_rows_count) + static_cast<int_t<> >(1)));
					}
				}
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_append_change_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::append_change_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[31]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_work_decisions_uint32_from_int(rowCount), __latency_fn_resident_function_body_work_decisions_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyChangeRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_append_decision_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::append_decision_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[32]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_work_decisions_uint32_from_int(rowCount), __latency_fn_resident_function_body_work_decisions_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyWorkDecisionRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_append_from_symbol_definition_changes(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::append_from_symbol_definition_changes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[33]);
	int_t<> startChangeCount = required_cast<int_t<>>(php::count(report->resident_function_body_changes));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_max_symbol_id_from_symbol_changes(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	int_t<> previousSlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_symbol_id_for_owner(previous, ownerRunId));
	if (static_cast<bool>((previousSlotCount > slotCount))) {
		slotCount = previousSlotCount;
	}
	vector_t<int_t<std::uint32_t>> currentSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	if (static_cast<bool>((slotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_function_body_work_decisions_build_snapshot_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, currentSnapshotIds);
		__latency_fn_resident_function_body_work_decisions_build_snapshot_lookup_ids(previous, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousSnapshotIds);
		__latency_fn_resident_function_body_work_decisions_append_lookup_metrics(report, (slotCount * static_cast<int_t<> >(2)));
	}
	auto __latency_local_0 = report->resident_symbol_definition_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_work_decisions_append_change(report, __latency_fn_resident_function_body_work_decisions_change_from_symbol_change(report, previous, change, currentSnapshotIds, previousSnapshotIds));
		}
	}
	__latency_fn_resident_function_body_work_decisions_append_change_memory_estimate(report, (php::count(report->resident_function_body_changes) - startChangeCount));
	int_t<> startDecisionCount = required_cast<int_t<>>(php::count(report->resident_function_body_work_decisions));
	auto __latency_local_2 = report->resident_function_body_changes;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto change = __latency_local_3.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_work_decisions_append_decision(report, __latency_fn_resident_function_body_work_decisions_decision_from_change(change));
		}
	}
	__latency_fn_resident_function_body_work_decisions_append_decision_memory_estimate(report, (php::count(report->resident_function_body_work_decisions) - startDecisionCount));
}

}
