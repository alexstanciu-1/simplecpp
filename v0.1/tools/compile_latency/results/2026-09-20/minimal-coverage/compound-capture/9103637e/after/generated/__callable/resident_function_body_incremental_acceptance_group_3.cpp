#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyIncrementalAcceptanceRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_append_from_recompute_targets_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_append_row.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_row_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
void __latency_fn_resident_function_body_incremental_acceptance_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[30]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int(rowCount), __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyIncrementalAcceptanceRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
void __latency_fn_resident_function_body_incremental_acceptance_append_from_recompute_targets_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::append_from_recompute_targets_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[31]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_incremental_acceptances));
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(decision->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id()))))) {
			__latency_fn_resident_function_body_incremental_acceptance_append_row(report, __latency_fn_resident_function_body_incremental_acceptance_row_from_decision(report, decision));
		}
	}
	__latency_fn_resident_function_body_incremental_acceptance_append_memory_estimate(report, (php::count(report->resident_function_body_incremental_acceptances) - startCount));
}

}
