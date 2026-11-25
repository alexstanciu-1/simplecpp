#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentChangeEventRow.hpp"
#include "__types/ResidentDirtyBailoutRow.hpp"
#include "__types/ResidentDirtyBudgetRow.hpp"
#include "__types/ResidentDirtyLimitCheckRow.hpp"
#include "__types/ResidentDirtyProcessedRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_change_events.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_check_for_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_check_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_checks_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_budget_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_exceeded_limit_check_for_budget.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_limit_checks_for_budget.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_depth_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_bailout.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_depth_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_widen_kind_project_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_widen_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_checks_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_process_queue_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_process_queue_row.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_queued_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_memory_estimate.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_queue_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_budget.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_from_owner_events.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_default_budget_row.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_event_is_dirty.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_queue_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_row_from_event.hpp"
namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_append_limit_checks_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_limit_checks_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[63]);
	ResidentDirtyBudgetRow budget = __latency_fn_resident_dirty_propagation_budget_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(budget->budget_id), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_has_limit_checks_for_budget(report, budget->budget_id)))) {
		return __latency_fn_resident_dirty_propagation_has_exceeded_limit_check_for_budget(report, budget->budget_id);
	}
	int_t<> startLimitCheckCount = required_cast<int_t<>>(php::count(report->resident_dirty_limit_checks));
	bool_t exceeded = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_check_for_kind(report, budget, __latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id())))) {
		exceeded = bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_check_for_kind(report, budget, __latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id())))) {
		exceeded = bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_check_for_kind(report, budget, __latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id())))) {
		exceeded = bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_check_for_kind(report, budget, __latency_fn_resident_dirty_propagation_limit_kind_depth_id())))) {
		exceeded = bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_check_for_kind(report, budget, __latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id())))) {
		exceeded = bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_check_for_kind(report, budget, __latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id())))) {
		exceeded = bool_t(static_cast<bool_t>(true));
	}
	__latency_fn_resident_dirty_propagation_append_limit_check_memory_estimate(report, (php::count(report->resident_dirty_limit_checks) - startLimitCheckCount));
	return bool_t(exceeded);
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_bailout(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBailoutRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_bailout", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[64]);
	row->bailout_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_dirty_bailouts));
	(void) report->resident_dirty_bailouts.append(row);
	report->resident_dirty_bailout_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_dirty_bailouts));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id())))) {
		report->resident_dirty_bailout_queue_row_limit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_queue_row_limit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id())))) {
		report->resident_dirty_bailout_processed_row_limit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_processed_row_limit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id())))) {
		report->resident_dirty_bailout_fanout_ref_limit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_fanout_ref_limit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_depth_id())))) {
		report->resident_dirty_bailout_depth_limit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_depth_limit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id())))) {
		report->resident_dirty_bailout_scratch_byte_limit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_scratch_byte_limit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id())))) {
		report->resident_dirty_bailout_fallback_scan_limit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_fallback_scan_limit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->widen_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_widen_kind_source_unit_id())))) {
		report->resident_dirty_bailout_widen_source_unit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_widen_source_unit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->widen_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_widen_kind_project_id())))) {
		report->resident_dirty_bailout_widen_project_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_bailout_widen_project_count) + static_cast<int_t<> >(1)));
	}
	return row->bailout_id;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
void __latency_fn_resident_dirty_propagation_process_queue_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::process_queue_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[65]);
	int_t<> startProcessedCount = required_cast<int_t<>>(php::count(report->resident_dirty_processed_rows));
	int_t<> startBailoutCount = required_cast<int_t<>>(php::count(report->resident_dirty_bailouts));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_append_limit_checks_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		__latency_fn_resident_dirty_propagation_append_memory_estimate(report, static_cast<int_t<> >(0), static_cast<int_t<> >(0), static_cast<int_t<> >(0), (php::count(report->resident_dirty_bailouts) - startBailoutCount));
		return;
	}
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_queued_id()))))) {
			__latency_fn_resident_dirty_propagation_process_queue_row(report, row);
		}
	}
	__latency_fn_resident_dirty_propagation_append_memory_estimate(report, static_cast<int_t<> >(0), (php::count(report->resident_dirty_processed_rows) - startProcessedCount), static_cast<int_t<> >(0), (php::count(report->resident_dirty_bailouts) - startBailoutCount));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
void __latency_fn_resident_dirty_propagation_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> queueCount, int_t<> processedCount, int_t<> budgetCount, int_t<> bailoutCount) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[66]);
	int_t<> rowCount = required_cast<int_t<>>((((queueCount + processedCount) + budgetCount) + bailoutCount));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> bytes = required_cast<int_t<>>(((((queueCount * static_cast<int_t<> >(sizeof(ResidentDirtyQueueRow))) + (processedCount * static_cast<int_t<> >(sizeof(ResidentDirtyProcessedRow)))) + (budgetCount * static_cast<int_t<> >(sizeof(ResidentDirtyBudgetRow)))) + (bailoutCount * static_cast<int_t<> >(sizeof(ResidentDirtyBailoutRow)))));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(rowCount), __latency_fn_structure_row_ids_uint32_from_int(bytes), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_has_queue_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::has_queue_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[67]);
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
void __latency_fn_resident_dirty_propagation_append_from_owner_events(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_from_owner_events", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[68]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_has_queue_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startQueueCount = required_cast<int_t<>>(php::count(report->resident_dirty_queue_rows));
	int_t<> startProcessedCount = required_cast<int_t<>>(php::count(report->resident_dirty_processed_rows));
	int_t<> startBudgetCount = required_cast<int_t<>>(php::count(report->resident_dirty_budgets));
	int_t<> startBailoutCount = required_cast<int_t<>>(php::count(report->resident_dirty_bailouts));
	auto __latency_local_0 = report->resident_change_events;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto event = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(event->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_resident_dirty_propagation_event_is_dirty(event)))) {
			__latency_fn_resident_dirty_propagation_append_queue(report, __latency_fn_resident_dirty_propagation_row_from_event(event));
		}
	}
	if (static_cast<bool>((php::count(report->resident_dirty_queue_rows) > startQueueCount))) {
		__latency_fn_resident_dirty_propagation_append_budget(report, __latency_fn_resident_dirty_propagation_default_budget_row(cast<int_t<std::uint32_t>>(ownerRunId)));
	}
	__latency_fn_resident_dirty_propagation_append_memory_estimate(report, (php::count(report->resident_dirty_queue_rows) - startQueueCount), (php::count(report->resident_dirty_processed_rows) - startProcessedCount), (php::count(report->resident_dirty_budgets) - startBudgetCount), (php::count(report->resident_dirty_bailouts) - startBailoutCount));
}

}
