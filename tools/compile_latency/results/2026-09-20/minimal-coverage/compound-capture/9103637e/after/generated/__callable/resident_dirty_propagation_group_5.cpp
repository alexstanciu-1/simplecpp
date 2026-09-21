#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentDirtyBailoutRow.hpp"
#include "__types/ResidentDirtyBudgetRow.hpp"
#include "__types/ResidentDirtyLimitCheckRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_exceeded_limit_check_for_budget.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_limit_exceeded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_first_queue_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_widen_kind_for_limit_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_widen_kind_project_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_widen_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_check_memory_estimate.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_check.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_depth_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_limit_exceeded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_limit_ok_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_bailout.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_bailout_from_limit_check.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_first_queue_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_bailout_for_budget.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_limit_exceeded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_processed_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_widen_kind_for_limit_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_bailout_from_limit_check.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_check.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_limit_check_for_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_count_for_limit_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_observed_count_for_limit_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_limit_exceeded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_limit_ok_id.hpp"
namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_has_exceeded_limit_check_for_budget(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> budgetId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::has_exceeded_limit_check_for_budget", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[56]);
	auto __latency_local_0 = report->resident_dirty_limit_checks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->budget_id), cast<int_t<>>(budgetId)) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_limit_exceeded_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
ResidentDirtyQueueRow __latency_fn_resident_dirty_propagation_first_queue_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::first_queue_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[57]);
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return row;
		}
	}
	ResidentDirtyQueueRow empty = ResidentDirtyQueueRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_dirty_propagation_widen_kind_for_limit_kind(int_t<std::uint16_t> limitKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::widen_kind_for_limit_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[58]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id())) || php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id()))))) {
		return __latency_fn_resident_dirty_propagation_widen_kind_project_id();
	}
	return __latency_fn_resident_dirty_propagation_widen_kind_source_unit_id();
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
void __latency_fn_resident_dirty_propagation_append_limit_check_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> limitCheckCount) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_limit_check_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[59]);
	if (static_cast<bool>((limitCheckCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(limitCheckCount), __latency_fn_structure_row_ids_uint32_from_int((limitCheckCount * static_cast<int_t<> >(sizeof(ResidentDirtyLimitCheckRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_limit_check(shared_p<CompilerProjectRunReport>& report, ResidentDirtyLimitCheckRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_limit_check", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[60]);
	row->check_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_dirty_limit_checks));
	(void) report->resident_dirty_limit_checks.append(row);
	report->resident_dirty_limit_check_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_dirty_limit_checks));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_limit_ok_id())))) {
		report->resident_dirty_limit_check_ok_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_ok_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_limit_exceeded_id())))) {
		report->resident_dirty_limit_check_exceeded_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_exceeded_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id())))) {
		report->resident_dirty_limit_check_queue_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_queue_row_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id())))) {
		report->resident_dirty_limit_check_processed_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_processed_row_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id())))) {
		report->resident_dirty_limit_check_fanout_ref_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_fanout_ref_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_depth_id())))) {
		report->resident_dirty_limit_check_depth_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_depth_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id())))) {
		report->resident_dirty_limit_check_scratch_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_scratch_byte_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id())))) {
		report->resident_dirty_limit_check_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_limit_check_fallback_scan_count) + static_cast<int_t<> >(1)));
	}
	return row->check_id;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
void __latency_fn_resident_dirty_propagation_append_bailout_from_limit_check(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBudgetRow budget, ResidentDirtyLimitCheckRow check) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_bailout_from_limit_check", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[61]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(check->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_limit_exceeded_id()))))) {
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_has_bailout_for_budget(report, budget->budget_id, check->limit_kind_id)))) {
		return;
	}
	ResidentDirtyQueueRow queue = __latency_fn_resident_dirty_propagation_first_queue_for_owner(report, budget->owner_run_id);
	ResidentDirtyBailoutRow row = ResidentDirtyBailoutRow{};
	row->owner_run_id = budget->owner_run_id;
	row->source_unit_id = queue->source_unit_id;
	row->symbol_id = queue->symbol_id;
	row->queue_id = queue->queue_id;
	row->budget_id = budget->budget_id;
	row->observed_count = check->observed_count;
	row->limit_count = check->limit_count;
	row->limit_kind_id = check->limit_kind_id;
	row->widen_kind_id = __latency_fn_resident_dirty_propagation_widen_kind_for_limit_kind(check->limit_kind_id);
	row->status_id = __latency_fn_resident_dirty_propagation_status_processed_id();
	__latency_fn_resident_dirty_propagation_append_bailout(report, row);
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_append_limit_check_for_kind(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBudgetRow budget, int_t<std::uint16_t> limitKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_limit_check_for_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[62]);
	ResidentDirtyLimitCheckRow row = ResidentDirtyLimitCheckRow{};
	row->owner_run_id = budget->owner_run_id;
	row->budget_id = budget->budget_id;
	row->limit_kind_id = limitKindId;
	row->observed_count = __latency_fn_resident_dirty_propagation_observed_count_for_limit_kind(report, budget, cast<int_t<std::uint16_t>>(limitKindId));
	row->limit_count = __latency_fn_resident_dirty_propagation_limit_count_for_limit_kind(budget, cast<int_t<std::uint16_t>>(limitKindId));
	row->status_id = __latency_fn_resident_dirty_propagation_status_limit_ok_id();
	if (static_cast<bool>((cast<int_t<>>(row->observed_count) > cast<int_t<>>(row->limit_count)))) {
		row->status_id = __latency_fn_resident_dirty_propagation_status_limit_exceeded_id();
	}
	__latency_fn_resident_dirty_propagation_append_limit_check(report, row);
	__latency_fn_resident_dirty_propagation_append_bailout_from_limit_check(report, budget, row);
	return bool_t(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_limit_exceeded_id())));
}

}
