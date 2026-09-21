#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyIncrementalAcceptanceRow.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_reference_candidate_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_source_unit_summary_no_candidate_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_fallback_scan_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_block_row.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_block_row.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_fallback_scan_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_local_parse_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_logical_consumer_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_logical_view_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_local_parse_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_logical_consumer_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_logical_view_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_publish_result_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_not_body_edit_decision_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_publish_result_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_blocked_reason_target_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_fallback_scan_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_local_parse_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_consumer_by_view.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_reference_candidate_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_view_by_source_unit.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_path_kind_function_body_edit_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_publish_result_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_row_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_selected_target_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_source_unit_summary_no_candidate_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_stable_remap_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_resolved_count.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_backend_refresh_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_dependent_resolution_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_local_lowering_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_source_unit_reparse_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_append_row.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_incremental_acceptance_source_unit_summary_no_candidate_count(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::source_unit_summary_no_candidate_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[25]);
	int_t<> candidateCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_resident_function_body_incremental_acceptance_logical_reference_candidate_count(report, decision)));
	int_t<> summaryCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_count(report, decision)));
	if (static_cast<bool>((candidateCount <= summaryCount))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	return __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((candidateCount - summaryCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_incremental_acceptance_fallback_scan_count(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::fallback_scan_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[26]);
	return __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((((((cast<int_t<>>(report->resident_function_body_logical_frontend_view_lookup_fallback_scan_count) + cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_lookup_fallback_scan_count)) + cast<int_t<>>(report->resident_function_body_logical_frontend_reference_candidate_summary_fallback_count)) + cast<int_t<>>(report->resident_function_body_summary_project_reference_fallback_scan_count)) + cast<int_t<>>(report->resident_function_body_summary_project_reference_scratch_symbol_rebuild_count)) + cast<int_t<>>(report->resident_project_symbol_name_lookup_fallback_scan_count)));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyIncrementalAcceptanceRow __latency_fn_resident_function_body_incremental_acceptance_block_row(ResidentFunctionBodyIncrementalAcceptanceRow row, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::block_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[27]);
	row->status_id = __latency_fn_resident_function_body_incremental_acceptance_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyIncrementalAcceptanceRow __latency_fn_resident_function_body_incremental_acceptance_row_from_decision(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::row_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[28]);
	ResidentFunctionBodyIncrementalAcceptanceRow row = ResidentFunctionBodyIncrementalAcceptanceRow{};
	row->owner_run_id = decision->owner_run_id;
	row->source_unit_id = decision->source_unit_id;
	row->symbol_id = decision->symbol_id;
	row->function_body_work_decision_id = decision->decision_id;
	row->path_kind_id = __latency_fn_resident_function_body_incremental_acceptance_path_kind_function_body_edit_id();
	row->status_id = __latency_fn_resident_function_body_incremental_acceptance_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_none_id();
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(decision->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id())) || php::not_identical(cast<int_t<>>(decision->status_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_status_ready_id()))))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_not_body_edit_decision_id());
	}
	ResidentFunctionBodyLocalParseProofRow localParse = __latency_fn_resident_function_body_incremental_acceptance_local_parse_by_decision(report, decision);
	row->local_parse_proof_id = localParse->proof_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(localParse->proof_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_local_parse_id());
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(localParse->status_id), cast<int_t<>>(__latency_fn_resident_function_body_local_parse_proofs_status_ready_id()))))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_local_parse_not_ready_id());
	}
	ResidentFunctionBodyRowListPublishResultRow publishResult = __latency_fn_resident_function_body_incremental_acceptance_publish_result_by_decision(report, decision);
	row->row_list_publish_result_id = publishResult->publish_result_id;
	row->row_list_publish_plan_id = publishResult->publish_plan_id;
	row->stable_node_remap_proof_id = publishResult->stable_node_remap_proof_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(publishResult->publish_result_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_publish_result_id());
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(publishResult->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_publish_result_not_ready_id());
	}
	ResidentFunctionBodyStableNodeRemapProofRow remap = __latency_fn_resident_function_body_incremental_acceptance_stable_remap_by_id(report, publishResult->stable_node_remap_proof_id);
	row->publish_repoint_preflight_id = remap->publish_repoint_preflight_id;
	ResidentFunctionBodyLogicalFrontendViewRow logicalView = __latency_fn_resident_function_body_incremental_acceptance_logical_view_by_source_unit(report, decision);
	row->logical_frontend_view_id = logicalView->view_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(logicalView->view_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_logical_view_id());
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(logicalView->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_logical_view_not_ready_id());
	}
	ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow logicalConsumer = __latency_fn_resident_function_body_incremental_acceptance_logical_consumer_by_view(report, logicalView);
	row->logical_consumer_descriptor_id = logicalConsumer->descriptor_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(logicalConsumer->descriptor_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_missing_logical_consumer_id());
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(logicalConsumer->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_logical_consumer_not_ready_id());
	}
	row->logical_reference_candidate_count = __latency_fn_resident_function_body_incremental_acceptance_logical_reference_candidate_count(report, decision);
	row->summary_project_reference_count = __latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_count(report, decision);
	row->summary_project_reference_resolved_count = __latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_resolved_count(report, decision);
	row->summary_project_reference_skipped_no_candidate_count = __latency_fn_resident_function_body_incremental_acceptance_source_unit_summary_no_candidate_count(report, decision);
	row->selected_source_reparse_count = __latency_fn_resident_function_body_incremental_acceptance_selected_target_count(report, decision->owner_run_id, decision->source_unit_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_resident_recompute_targets_target_source_unit_reparse_id(), bool_t(static_cast<bool_t>(false)));
	row->selected_local_lowering_count = __latency_fn_resident_function_body_incremental_acceptance_selected_target_count(report, decision->owner_run_id, decision->source_unit_id, decision->symbol_id, __latency_fn_resident_recompute_targets_target_local_lowering_id(), bool_t(static_cast<bool_t>(true)));
	row->selected_backend_refresh_count = __latency_fn_resident_function_body_incremental_acceptance_selected_target_count(report, decision->owner_run_id, decision->source_unit_id, decision->symbol_id, __latency_fn_resident_recompute_targets_target_backend_refresh_id(), bool_t(static_cast<bool_t>(true)));
	row->selected_dependent_resolution_count = __latency_fn_resident_function_body_incremental_acceptance_selected_target_count(report, decision->owner_run_id, decision->source_unit_id, decision->symbol_id, __latency_fn_resident_recompute_targets_target_dependent_resolution_id(), bool_t(static_cast<bool_t>(false)));
	row->fallback_scan_count = __latency_fn_resident_function_body_incremental_acceptance_fallback_scan_count(report);
	if (static_cast<bool>((cast<int_t<>>(row->fallback_scan_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_fallback_scan_id());
	}
	if (static_cast<bool>((((php::not_identical(cast<int_t<>>(row->selected_source_reparse_count), static_cast<int_t<> >(1)) || php::not_identical(cast<int_t<>>(row->selected_local_lowering_count), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(row->selected_backend_refresh_count), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(row->selected_dependent_resolution_count), static_cast<int_t<> >(0))))) {
		return __latency_fn_resident_function_body_incremental_acceptance_block_row(row, __latency_fn_resident_function_body_incremental_acceptance_blocked_reason_target_mismatch_id());
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
void __latency_fn_resident_function_body_incremental_acceptance_append_row(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyIncrementalAcceptanceRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[29]);
	row->acceptance_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_incremental_acceptances));
	(void) report->resident_function_body_incremental_acceptances.append(row);
	report->resident_function_body_incremental_acceptance_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int(php::count(report->resident_function_body_incremental_acceptances));
	report->resident_function_body_incremental_acceptance_fallback_scan_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_fallback_scan_count) + cast<int_t<>>(row->fallback_scan_count)));
	report->resident_function_body_incremental_acceptance_source_reparse_target_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_source_reparse_target_count) + cast<int_t<>>(row->selected_source_reparse_count)));
	report->resident_function_body_incremental_acceptance_local_lowering_target_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_local_lowering_target_count) + cast<int_t<>>(row->selected_local_lowering_count)));
	report->resident_function_body_incremental_acceptance_backend_refresh_target_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_backend_refresh_target_count) + cast<int_t<>>(row->selected_backend_refresh_count)));
	report->resident_function_body_incremental_acceptance_dependent_resolution_target_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_dependent_resolution_target_count) + cast<int_t<>>(row->selected_dependent_resolution_count)));
	report->resident_function_body_incremental_acceptance_summary_reference_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_summary_reference_count) + cast<int_t<>>(row->summary_project_reference_count)));
	report->resident_function_body_incremental_acceptance_summary_skipped_no_candidate_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_summary_skipped_no_candidate_count) + cast<int_t<>>(row->summary_project_reference_skipped_no_candidate_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_incremental_acceptance_status_ready_id())))) {
		report->resident_function_body_incremental_acceptance_ready_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_incremental_acceptance_blocked_count = __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_function_body_incremental_acceptance_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}
