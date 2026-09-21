#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyParseSliceRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_local_parse_proofs.hpp"
#include "__types/resident_function_body_parse_slices.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_missing_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_parser_diagnostic_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_token_cursor_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_row_from_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_row_from_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_from_parse_slices.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_row_from_slice.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_from_config_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_append_from_parse_slices.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_has_parse_slices.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
ResidentFunctionBodyLocalParseProofRow __latency_fn_resident_function_body_local_parse_proofs_row_from_slice(shared_p<CompilerProjectRunReport> report, shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyParseSliceRow slice) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::row_from_slice", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[12]);
	shared_p<FrontendModel> model = create<FrontendModel>();
	int_t<std::uint32_t> actualTokenAfterBodyIndex = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> parserDiagnosticCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((!__latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice(sourceUnits, slice, model, actualTokenAfterBodyIndex, parserDiagnosticCount)))) {
		return __latency_fn_resident_function_body_local_parse_proofs_blocked_row_from_slice(slice, __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_missing_source_unit_id());
	}
	ResidentFunctionBodySnapshotRow snapshot = __latency_fn_resident_function_body_parse_slices_snapshot_by_id(report, slice->current_function_body_snapshot_id);
	ResidentFunctionBodyLocalParseProofRow row = ResidentFunctionBodyLocalParseProofRow{};
	row->owner_run_id = slice->owner_run_id;
	row->parse_slice_id = slice->slice_id;
	row->function_body_work_decision_id = slice->function_body_work_decision_id;
	row->current_function_body_snapshot_id = slice->current_function_body_snapshot_id;
	row->source_unit_id = slice->source_unit_id;
	row->symbol_id = slice->symbol_id;
	row->body_start_offset = slice->body_start_offset;
	row->body_end_offset = slice->body_end_offset;
	row->token_start_index = slice->token_start_index;
	row->expected_token_after_body_index = __latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body(slice);
	row->actual_token_after_body_index = actualTokenAfterBodyIndex;
	row->local_frontend_node_count = model->node_count;
	row->local_statement_count = model->statement_count;
	row->local_expression_count = model->expression_count;
	row->local_source_range_count = model->source_range_count;
	row->full_source_body_node_count = snapshot->body_node_count;
	row->parser_diagnostic_count = parserDiagnosticCount;
	row->status_id = __latency_fn_resident_function_body_local_parse_proofs_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_none_id();
	if (static_cast<bool>((cast<int_t<>>(row->parser_diagnostic_count) > static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_resident_function_body_local_parse_proofs_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_parser_diagnostic_id();
	}
	else {
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->actual_token_after_body_index), cast<int_t<>>(row->expected_token_after_body_index))))) {
			row->status_id = __latency_fn_resident_function_body_local_parse_proofs_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_token_cursor_mismatch_id();
		}
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_local_parse_proofs_append_proof(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyLocalParseProofRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::append_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[13]);
	row->proof_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_local_parse_proofs));
	(void) report->resident_function_body_local_parse_proofs.append(row);
	report->resident_function_body_local_parse_proof_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int(php::count(report->resident_function_body_local_parse_proofs));
	report->resident_function_body_local_parse_proof_node_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_local_parse_proof_node_count) + cast<int_t<>>(row->local_frontend_node_count)));
	report->resident_function_body_local_parse_proof_statement_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_local_parse_proof_statement_count) + cast<int_t<>>(row->local_statement_count)));
	report->resident_function_body_local_parse_proof_expression_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_local_parse_proof_expression_count) + cast<int_t<>>(row->local_expression_count)));
	report->resident_function_body_local_parse_proof_diagnostic_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_local_parse_proof_diagnostic_count) + cast<int_t<>>(row->parser_diagnostic_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_local_parse_proofs_status_ready_id())))) {
		report->resident_function_body_local_parse_proof_ready_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_local_parse_proof_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_local_parse_proof_blocked_count = __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_local_parse_proof_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_local_parse_proofs_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[14]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int(rowCount), __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyLocalParseProofRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_local_parse_proofs_append_from_parse_slices(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::append_from_parse_slices", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[15]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_local_parse_proofs));
	auto __latency_local_0 = report->resident_function_body_parse_slices;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto slice = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(slice->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_function_body_local_parse_proofs_append_proof(report, __latency_fn_resident_function_body_local_parse_proofs_row_from_slice(report, sourceUnits, slice));
		}
	}
	__latency_fn_resident_function_body_local_parse_proofs_append_memory_estimate(report, (php::count(report->resident_function_body_local_parse_proofs) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_local_parse_proofs_append_from_config_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<PipelineConfig> config, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::append_from_config_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[16]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_local_parse_proofs_has_parse_slices(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	shared_p<ProjectManifest> manifest = __latency_fn_project_manifest_from_project_dir(config->project_dir);
	shared_p<SourceUnitTable> sourceUnits = __latency_fn_source_units_table_from_manifest(manifest);
	__latency_fn_resident_function_body_local_parse_proofs_append_from_parse_slices(report, sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
