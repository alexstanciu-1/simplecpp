#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ResidentFunctionBodyRowListPublishPlanRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_row_list_publish_plans.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_from_publish_plans.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_result.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_row_from_plan.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_from_config_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_from_publish_plans.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_has_publish_plans.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_results_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[18]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int(rowCount), __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyRowListPublishResultRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_results_append_from_publish_plans(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::append_from_publish_plans", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[19]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_row_list_publish_results));
	auto __latency_local_0 = report->resident_function_body_row_list_publish_plans;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto plan = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(plan->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_row_list_publish_results_append_result(report, __latency_fn_resident_function_body_row_list_publish_results_row_from_plan(report, sourceUnits, plan));
		}
	}
	__latency_fn_resident_function_body_row_list_publish_results_append_memory_estimate(report, (php::count(report->resident_function_body_row_list_publish_results) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_results_append_from_config_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<PipelineConfig> config, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::append_from_config_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[20]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_row_list_publish_results_has_publish_plans(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	shared_p<ProjectManifest> manifest = __latency_fn_project_manifest_from_project_dir(config->project_dir);
	shared_p<SourceUnitTable> sourceUnits = __latency_fn_source_units_table_from_manifest(manifest);
	__latency_fn_resident_function_body_row_list_publish_results_append_from_publish_plans(report, sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
