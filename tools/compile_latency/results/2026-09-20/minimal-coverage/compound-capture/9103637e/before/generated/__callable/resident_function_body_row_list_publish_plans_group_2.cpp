#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishPlanRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_row_list_publish_plans.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_plan.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_from_stable_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_plan.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_row_from_stable_remap_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_from_stable_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_append_from_stable_remap_proofs_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_has_stable_remap_proofs.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_plans_append_plan(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyRowListPublishPlanRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::append_plan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[22]);
	row->publish_plan_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_row_list_publish_plans));
	(void) report->resident_function_body_row_list_publish_plans.append(row);
	report->resident_function_body_row_list_publish_plan_count = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int(php::count(report->resident_function_body_row_list_publish_plans));
	report->resident_function_body_row_list_publish_plan_replacement_node_count = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_replacement_node_count) + cast<int_t<>>(row->replacement_body_node_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_plans_status_ready_id())))) {
		report->resident_function_body_row_list_publish_plan_ready_count = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_ready_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_row_list_publish_plan_planned_publish_count = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_planned_publish_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_row_list_publish_plan_retained_old_body_row_bytes = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_retained_old_body_row_bytes) + cast<int_t<>>(row->retained_old_body_row_bytes)));
		report->resident_function_body_row_list_publish_plan_cleanup_released_body_row_bytes = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_cleanup_released_body_row_bytes) + cast<int_t<>>(row->planned_cleanup_released_body_row_bytes)));
		if (static_cast<bool>((cast<int_t<>>(row->planned_cleanup_released_body_row_bytes) > static_cast<int_t<> >(0)))) {
			report->resident_function_body_row_list_publish_plan_planned_cleanup_count = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_planned_cleanup_count) + static_cast<int_t<> >(1)));
		}
	}
	else {
		report->resident_function_body_row_list_publish_plan_blocked_count = __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_plan_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_plans_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[23]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int(rowCount), __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyRowListPublishPlanRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_plans_append_from_stable_remap_proofs(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::append_from_stable_remap_proofs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[24]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_row_list_publish_plans));
	auto __latency_local_0 = report->resident_function_body_stable_node_remap_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto remap = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(remap->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_row_list_publish_plans_append_plan(report, __latency_fn_resident_function_body_row_list_publish_plans_row_from_stable_remap_proof(report, previous, remap));
		}
	}
	__latency_fn_resident_function_body_row_list_publish_plans_append_memory_estimate(report, (php::count(report->resident_function_body_row_list_publish_plans) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_plans_append_from_stable_remap_proofs_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::append_from_stable_remap_proofs_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[25]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_row_list_publish_plans_has_stable_remap_proofs(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	__latency_fn_resident_function_body_row_list_publish_plans_append_from_stable_remap_proofs(report, previous, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
