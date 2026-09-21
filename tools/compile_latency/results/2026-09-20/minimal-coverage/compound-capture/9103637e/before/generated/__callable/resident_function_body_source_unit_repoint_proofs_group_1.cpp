#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyMixedFrontendAssemblyProofRow.hpp"
#include "__types/ResidentFunctionBodySourceUnitRepointProofRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_mixed_frontend_assembly_proofs.hpp"
#include "__types/resident_function_body_source_unit_repoint_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_append_from_mixed_assembly_proofs_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_has_ready_mixed_assembly_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_row_from_mixed_assembly.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_source_unit_repoint_proofs_append_proof(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodySourceUnitRepointProofRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::append_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[14]);
	row->repoint_proof_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_source_unit_repoint_proofs));
	(void) report->resident_function_body_source_unit_repoint_proofs.append(row);
	report->resident_function_body_source_unit_repoint_proof_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int(php::count(report->resident_function_body_source_unit_repoint_proofs));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id())))) {
		report->resident_function_body_source_unit_repoint_proof_ready_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_proof_ready_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_source_unit_repoint_source_unit_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_source_unit_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->repoint_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_mixed_reuse_and_replacement_id())))) {
			report->resident_function_body_source_unit_repoint_mixed_source_unit_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_mixed_source_unit_count) + static_cast<int_t<> >(1)));
		}
		report->resident_function_body_source_unit_repoint_reused_body_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_reused_body_count) + cast<int_t<>>(row->reused_body_count)));
		report->resident_function_body_source_unit_repoint_replacement_body_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_replacement_body_count) + cast<int_t<>>(row->replacement_body_count)));
		report->resident_function_body_source_unit_repoint_build_new_body_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_build_new_body_count) + cast<int_t<>>(row->build_new_body_count)));
		report->resident_function_body_source_unit_repoint_source_unit_row_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_source_unit_row_count) + cast<int_t<>>(row->source_unit_row_count)));
		report->resident_function_body_source_unit_repoint_stable_non_body_node_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_stable_non_body_node_count) + cast<int_t<>>(row->stable_non_body_node_count)));
		report->resident_function_body_source_unit_repoint_retained_body_node_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_retained_body_node_count) + cast<int_t<>>(row->retained_body_node_count)));
		report->resident_function_body_source_unit_repoint_repointed_body_node_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_repointed_body_node_count) + cast<int_t<>>(row->repointed_body_node_count)));
		report->resident_function_body_source_unit_repoint_assembled_body_node_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_assembled_body_node_count) + cast<int_t<>>(row->assembled_body_node_count)));
		report->resident_function_body_source_unit_repoint_current_full_source_rebuild_row_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_current_full_source_rebuild_row_count) + cast<int_t<>>(row->current_full_source_rebuild_row_count)));
		report->resident_function_body_source_unit_repoint_cleanup_released_body_row_bytes = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_cleanup_released_body_row_bytes) + cast<int_t<>>(row->cleanup_released_body_row_bytes)));
		report->resident_function_body_source_unit_repoint_published_segment_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_published_segment_count) + cast<int_t<>>(row->published_segment_count)));
		report->resident_function_body_source_unit_repoint_published_segmented_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_published_segmented_count) + cast<int_t<>>(row->published_segmented_count)));
	}
	else {
		report->resident_function_body_source_unit_repoint_proof_blocked_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_source_unit_repoint_proof_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_source_unit_repoint_proofs_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[15]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int(rowCount), __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodySourceUnitRepointProofRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_source_unit_repoint_proofs_append_from_mixed_assembly_proofs_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::append_from_mixed_assembly_proofs_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[16]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_source_unit_repoint_proofs_has_ready_mixed_assembly_proofs(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_source_unit_repoint_proofs));
	auto __latency_local_0 = report->resident_function_body_mixed_frontend_assembly_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto assembly = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(assembly->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(assembly->status_id), cast<int_t<>>(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id()))))) {
			__latency_fn_resident_function_body_source_unit_repoint_proofs_append_proof(report, __latency_fn_resident_function_body_source_unit_repoint_proofs_row_from_mixed_assembly(report, assembly));
		}
	}
	__latency_fn_resident_function_body_source_unit_repoint_proofs_append_memory_estimate(report, (php::count(report->resident_function_body_source_unit_repoint_proofs) - startCount));
}

}
