#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendReferenceCandidateRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_logical_frontend_consumer_descriptors.hpp"
#include "__types/resident_function_body_logical_frontend_reference_candidates.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_full_source_fallback_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_from_descriptors_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_has_ready_descriptors.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_row_from_span.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_reference_candidates_append_row(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyLogicalFrontendReferenceCandidateRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[12]);
	row->candidate_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_logical_frontend_reference_candidates));
	(void) report->resident_function_body_logical_frontend_reference_candidates.append(row);
	report->resident_function_body_logical_frontend_reference_candidate_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int(php::count(report->resident_function_body_logical_frontend_reference_candidates));
	report->resident_function_body_logical_frontend_reference_candidate_summary_read_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_summary_read_count) + static_cast<int_t<> >(1)));
	report->resident_function_body_logical_frontend_reference_candidate_body_node_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_body_node_count) + cast<int_t<>>(row->body_node_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->access_path_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_full_source_fallback_id())))) {
		report->resident_function_body_logical_frontend_reference_candidate_summary_fallback_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_summary_fallback_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id())))) {
		report->resident_function_body_logical_frontend_reference_candidate_found_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_found_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>((cast<int_t<>>(row->callee_length) > static_cast<int_t<> >(0)))) {
			report->resident_function_body_logical_frontend_reference_candidate_callee_range_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_callee_range_count) + static_cast<int_t<> >(1)));
			report->resident_function_body_logical_frontend_reference_candidate_argument_summary_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_argument_summary_count) + static_cast<int_t<> >(1)));
		}
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id())))) {
			report->resident_function_body_logical_frontend_reference_candidate_no_candidate_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_no_candidate_count) + static_cast<int_t<> >(1)));
		}
		else {
			report->resident_function_body_logical_frontend_reference_candidate_blocked_count = __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_blocked_count) + static_cast<int_t<> >(1)));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_reference_candidates_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[13]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int(rowCount), __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyLogicalFrontendReferenceCandidateRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_reference_candidates_append_from_descriptors_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::append_from_descriptors_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[14]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_logical_frontend_reference_candidates_has_ready_descriptors(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_logical_frontend_reference_candidates));
	auto __latency_local_0 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		if (static_cast<bool>((php::not_identical(cast<int_t<>>(descriptor->owner_run_id), cast<int_t<>>(ownerRunId)) || php::not_identical(cast<int_t<>>(descriptor->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
			continue;
		}
		auto __latency_local_2 = report->resident_function_body_logical_frontend_body_span_descriptors;
		for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
			auto span = __latency_local_3.value_copy();
			if (static_cast<bool>((php::identical(cast<int_t<>>(span->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(span->logical_consumer_descriptor_id), cast<int_t<>>(descriptor->descriptor_id))))) {
				ResidentFunctionBodyLogicalFrontendReferenceCandidateRow row = __latency_fn_resident_function_body_logical_frontend_reference_candidates_row_from_span(descriptor, span);
				__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_row(report, row);
			}
		}
	}
	__latency_fn_resident_function_body_logical_frontend_reference_candidates_append_memory_estimate(report, (php::count(report->resident_function_body_logical_frontend_reference_candidates) - startCount));
}

}
