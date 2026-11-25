#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyPublishRepointPreflightRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_local_parse_proofs.hpp"
#include "__types/resident_function_body_publish_repoint_preflights.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_preflight.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_publish_strategy_proof_only_deferred_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_required_remap_stable_body_node_ids_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_from_local_parse_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_preflight.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_row_from_local_parse_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_from_local_parse_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_append_from_local_parse_proofs_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_has_local_parse_proofs.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
void __latency_fn_resident_function_body_publish_repoint_preflights_append_preflight(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyPublishRepointPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::append_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[13]);
	row->preflight_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_publish_repoint_preflights));
	(void) report->resident_function_body_publish_repoint_preflights.append(row);
	report->resident_function_body_publish_repoint_preflight_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int(php::count(report->resident_function_body_publish_repoint_preflights));
	report->resident_function_body_publish_repoint_preflight_node_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_node_count) + cast<int_t<>>(row->local_frontend_node_count)));
	report->resident_function_body_publish_repoint_preflight_statement_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_statement_count) + cast<int_t<>>(row->local_statement_count)));
	report->resident_function_body_publish_repoint_preflight_expression_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_expression_count) + cast<int_t<>>(row->local_expression_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->required_remap_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_publish_repoint_preflights_required_remap_stable_body_node_ids_id())))) {
		report->resident_function_body_publish_repoint_preflight_requires_remap_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_requires_remap_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->publish_strategy_id), cast<int_t<>>(__latency_fn_resident_function_body_publish_repoint_preflights_publish_strategy_proof_only_deferred_id())))) {
		report->resident_function_body_publish_repoint_preflight_deferred_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_deferred_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id())))) {
		report->resident_function_body_publish_repoint_preflight_ready_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_publish_repoint_preflight_blocked_count = __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((cast<int_t<>>(report->resident_function_body_publish_repoint_preflight_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
void __latency_fn_resident_function_body_publish_repoint_preflights_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[14]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int(rowCount), __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyPublishRepointPreflightRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
void __latency_fn_resident_function_body_publish_repoint_preflights_append_from_local_parse_proofs(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::append_from_local_parse_proofs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[15]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_publish_repoint_preflights));
	auto __latency_local_0 = report->resident_function_body_local_parse_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto proof = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(proof->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_publish_repoint_preflights_append_preflight(report, __latency_fn_resident_function_body_publish_repoint_preflights_row_from_local_parse_proof(proof));
		}
	}
	__latency_fn_resident_function_body_publish_repoint_preflights_append_memory_estimate(report, (php::count(report->resident_function_body_publish_repoint_preflights) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
void __latency_fn_resident_function_body_publish_repoint_preflights_append_from_local_parse_proofs_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::append_from_local_parse_proofs_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[16]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_publish_repoint_preflights_has_local_parse_proofs(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	__latency_fn_resident_function_body_publish_repoint_preflights_append_from_local_parse_proofs(report, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
