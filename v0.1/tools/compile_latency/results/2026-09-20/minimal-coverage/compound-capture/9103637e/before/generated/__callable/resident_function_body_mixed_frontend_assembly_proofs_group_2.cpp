#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyMixedFrontendAssemblyProofRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/resident_function_body_mixed_frontend_assembly_proofs.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_from_publish_results_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_ready_publish_results.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_source_unit_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_row_from_source_unit.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_from_publish_results_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::append_from_publish_results_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[18]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_ready_publish_results(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_mixed_frontend_assembly_proofs));
	auto __latency_local_0 = report->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>((php::not_identical(cast<int_t<>>(result->owner_run_id), cast<int_t<>>(ownerRunId)) || php::not_identical(cast<int_t<>>(result->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
			continue;
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_source_unit_proof(report, cast<int_t<std::uint32_t>>(ownerRunId), result->source_unit_id)))) {
			continue;
		}
		__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_proof(report, __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_row_from_source_unit(report, cast<int_t<std::uint32_t>>(ownerRunId), result->source_unit_id));
	}
	__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_memory_estimate(report, (php::count(report->resident_function_body_mixed_frontend_assembly_proofs) - startCount));
}

}
