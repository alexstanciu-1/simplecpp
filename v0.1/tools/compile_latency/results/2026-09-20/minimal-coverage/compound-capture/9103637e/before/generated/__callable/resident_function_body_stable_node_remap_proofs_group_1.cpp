#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyPublishRepointPreflightRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_publish_repoint_preflights.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_empty_local_node_range_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_missing_owner_identity_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_preflight_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_stable_window_overflow_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_identity_namespace_body_owner_symbol_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_remap_strategy_stable_window_by_local_ordinal_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_row_from_publish_repoint_preflight.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_fits.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_start_for_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_from_publish_repoint_preflights.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_row_from_publish_repoint_preflight.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_from_publish_repoint_preflights.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_append_from_publish_repoint_preflights_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_has_publish_repoint_preflights.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
ResidentFunctionBodyStableNodeRemapProofRow __latency_fn_resident_function_body_stable_node_remap_proofs_row_from_publish_repoint_preflight(ResidentFunctionBodyPublishRepointPreflightRow preflight) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::row_from_publish_repoint_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[15]);
	ResidentFunctionBodyStableNodeRemapProofRow row = ResidentFunctionBodyStableNodeRemapProofRow{};
	row->owner_run_id = preflight->owner_run_id;
	row->publish_repoint_preflight_id = preflight->preflight_id;
	row->local_parse_proof_id = preflight->local_parse_proof_id;
	row->parse_slice_id = preflight->parse_slice_id;
	row->function_body_work_decision_id = preflight->function_body_work_decision_id;
	row->current_function_body_snapshot_id = preflight->current_function_body_snapshot_id;
	row->source_unit_id = preflight->source_unit_id;
	row->symbol_id = preflight->symbol_id;
	row->body_start_offset = preflight->body_start_offset;
	row->body_end_offset = preflight->body_end_offset;
	row->local_frontend_node_count = preflight->local_frontend_node_count;
	row->full_source_body_node_count = preflight->full_source_body_node_count;
	row->identity_namespace_id = __latency_fn_resident_function_body_stable_node_remap_proofs_identity_namespace_body_owner_symbol_id();
	row->remap_strategy_id = __latency_fn_resident_function_body_stable_node_remap_proofs_remap_strategy_stable_window_by_local_ordinal_id();
	row->status_id = __latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_none_id();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(preflight->status_id), cast<int_t<>>(__latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id()))))) {
		row->status_id = __latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_preflight_not_ready_id();
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflight->local_frontend_node_count), static_cast<int_t<> >(0)))) {
			row->status_id = __latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_empty_local_node_range_id();
		}
		else {
			if (static_cast<bool>((php::identical(cast<int_t<>>(preflight->source_unit_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(preflight->symbol_id), static_cast<int_t<> >(0))))) {
				row->status_id = __latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id();
				row->blocked_reason_id = __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_missing_owner_identity_id();
			}
			else {
				if (static_cast<bool>((!__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_fits(preflight->symbol_id, preflight->local_frontend_node_count)))) {
					row->status_id = __latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id();
					row->blocked_reason_id = __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_stable_window_overflow_id();
				}
			}
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id())))) {
		row->local_node_ordinal_start = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int(static_cast<int_t<> >(1));
		row->local_node_ordinal_count = preflight->local_frontend_node_count;
		row->future_stable_node_id_start = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int(__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_start_for_symbol(preflight->symbol_id));
		row->future_stable_node_id_count = preflight->local_frontend_node_count;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_stable_node_remap_proofs_append_proof(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyStableNodeRemapProofRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::append_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[16]);
	row->remap_proof_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_stable_node_remap_proofs));
	(void) report->resident_function_body_stable_node_remap_proofs.append(row);
	report->resident_function_body_stable_node_remap_proof_count = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int(php::count(report->resident_function_body_stable_node_remap_proofs));
	report->resident_function_body_stable_node_remap_proof_node_count = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_stable_node_remap_proof_node_count) + cast<int_t<>>(row->local_node_ordinal_count)));
	if (static_cast<bool>((cast<int_t<>>(row->future_stable_node_id_count) > static_cast<int_t<> >(0)))) {
		report->resident_function_body_stable_node_remap_proof_window_count = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_stable_node_remap_proof_window_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id())))) {
		report->resident_function_body_stable_node_remap_proof_ready_count = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_stable_node_remap_proof_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_stable_node_remap_proof_blocked_count = __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_stable_node_remap_proof_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_stable_node_remap_proofs_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[17]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int(rowCount), __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyStableNodeRemapProofRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_stable_node_remap_proofs_append_from_publish_repoint_preflights(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::append_from_publish_repoint_preflights", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[18]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_stable_node_remap_proofs));
	auto __latency_local_0 = report->resident_function_body_publish_repoint_preflights;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto preflight = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflight->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_stable_node_remap_proofs_append_proof(report, __latency_fn_resident_function_body_stable_node_remap_proofs_row_from_publish_repoint_preflight(preflight));
		}
	}
	__latency_fn_resident_function_body_stable_node_remap_proofs_append_memory_estimate(report, (php::count(report->resident_function_body_stable_node_remap_proofs) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_stable_node_remap_proofs_append_from_publish_repoint_preflights_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::append_from_publish_repoint_preflights_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[19]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_stable_node_remap_proofs_has_publish_repoint_preflights(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	__latency_fn_resident_function_body_stable_node_remap_proofs_append_from_publish_repoint_preflights(report, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
