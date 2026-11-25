#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ResidentFunctionBodyParseSliceRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_parse_slices.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_is_token_start_byte.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_scan_token_end.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_token_count_in_span.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_from_work_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_row_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_token_count_before_offset.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_token_count_in_span.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_units_row_by_id.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_new_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_from_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_row_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_should_emit_slice.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_from_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_append_from_config_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_has_slice_candidates.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_parse_slices_token_count_in_span(const string_t& source, int_t<> startOffset, int_t<> spanLength) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::token_count_in_span", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[12]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> length = required_cast<int_t<>>(str::byte_length(source));
	if (static_cast<bool>((startOffset < static_cast<int_t<> >(0)))) {
		startOffset = static_cast<int_t<> >(0);
	}
	if (static_cast<bool>((startOffset > length))) {
		startOffset = length;
	}
	int_t<> endOffset = required_cast<int_t<>>((startOffset + spanLength));
	if (static_cast<bool>((endOffset > length))) {
		endOffset = length;
	}
	int_t<> offset = required_cast<int_t<>>(startOffset);
	while (static_cast<bool>((offset < endOffset))) {
		int_t<> byte = required_cast<int_t<>>(php::string_byte_at(source, offset));
		if (static_cast<bool>((!__latency_fn_resident_function_body_parse_slices_is_token_start_byte(byte)))) {
			offset = (offset + static_cast<int_t<> >(1));
		}
		else {
			count = (count + static_cast<int_t<> >(1));
			offset = __latency_fn_resident_function_body_parse_slices_scan_token_end(source, offset, endOffset);
		}
	}
	return __latency_fn_resident_function_body_parse_slices_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
ResidentFunctionBodyParseSliceRow __latency_fn_resident_function_body_parse_slices_row_from_decision(shared_p<CompilerProjectRunReport> report, shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::row_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[13]);
	ResidentFunctionBodySnapshotRow snapshot = __latency_fn_resident_function_body_parse_slices_snapshot_by_id(report, decision->current_function_body_snapshot_id);
	SourceUnitTableRow sourceUnit = __latency_fn_source_units_row_by_id(sourceUnits, decision->source_unit_id);
	string_t sourceText = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(sourceUnits, decision->source_unit_id));
	int_t<> bodyStart = required_cast<int_t<>>(cast<int_t<>>(snapshot->body_start_offset));
	int_t<> bodyLength = required_cast<int_t<>>(cast<int_t<>>(snapshot->body_length));
	ResidentFunctionBodyParseSliceRow row = ResidentFunctionBodyParseSliceRow{};
	row->owner_run_id = decision->owner_run_id;
	row->function_body_work_decision_id = decision->decision_id;
	row->function_body_change_id = decision->function_body_change_id;
	row->current_function_body_snapshot_id = decision->current_function_body_snapshot_id;
	row->source_unit_id = decision->source_unit_id;
	row->symbol_id = decision->symbol_id;
	row->body_start_offset = snapshot->body_start_offset;
	row->body_length = snapshot->body_length;
	row->body_end_offset = __latency_fn_resident_function_body_parse_slices_uint32_from_int((bodyStart + bodyLength));
	row->token_start_index = __latency_fn_resident_function_body_parse_slices_token_count_before_offset(sourceText, bodyStart);
	row->token_count = __latency_fn_resident_function_body_parse_slices_token_count_in_span(sourceText, bodyStart, bodyLength);
	row->source_unit_length = sourceUnit->source_length;
	row->parse_slice_kind_id = __latency_fn_resident_function_body_parse_slices_parse_slice_kind_from_work_decision(decision);
	row->work_decision_kind_id = decision->work_decision_kind_id;
	row->status_id = __latency_fn_resident_function_body_parse_slices_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_parse_slices_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
void __latency_fn_resident_function_body_parse_slices_append_slice(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyParseSliceRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::append_slice", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[14]);
	row->slice_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_parse_slices));
	(void) report->resident_function_body_parse_slices.append(row);
	report->resident_function_body_parse_slice_count = __latency_fn_resident_function_body_parse_slices_uint32_from_int(php::count(report->resident_function_body_parse_slices));
	report->resident_function_body_parse_slice_byte_count = __latency_fn_resident_function_body_parse_slices_uint32_from_int((cast<int_t<>>(report->resident_function_body_parse_slice_byte_count) + cast<int_t<>>(row->body_length)));
	report->resident_function_body_parse_slice_token_count = __latency_fn_resident_function_body_parse_slices_uint32_from_int((cast<int_t<>>(report->resident_function_body_parse_slice_token_count) + cast<int_t<>>(row->token_count)));
	report->resident_function_body_parse_slice_source_unit_byte_count = __latency_fn_resident_function_body_parse_slices_uint32_from_int((cast<int_t<>>(report->resident_function_body_parse_slice_source_unit_byte_count) + cast<int_t<>>(row->source_unit_length)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->parse_slice_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_parse_slices_parse_slice_kind_new_body_id())))) {
		report->resident_function_body_parse_slice_build_new_count = __latency_fn_resident_function_body_parse_slices_uint32_from_int((cast<int_t<>>(report->resident_function_body_parse_slice_build_new_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_parse_slice_replacement_count = __latency_fn_resident_function_body_parse_slices_uint32_from_int((cast<int_t<>>(report->resident_function_body_parse_slice_replacement_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
void __latency_fn_resident_function_body_parse_slices_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[15]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_parse_slices_uint32_from_int(rowCount), __latency_fn_resident_function_body_parse_slices_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyParseSliceRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
void __latency_fn_resident_function_body_parse_slices_append_from_body_work_decisions(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::append_from_body_work_decisions", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[16]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_parse_slices));
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_resident_function_body_parse_slices_should_emit_slice(decision)))) {
			__latency_fn_resident_function_body_parse_slices_append_slice(report, __latency_fn_resident_function_body_parse_slices_row_from_decision(report, sourceUnits, decision));
		}
	}
	__latency_fn_resident_function_body_parse_slices_append_memory_estimate(report, (php::count(report->resident_function_body_parse_slices) - startCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
void __latency_fn_resident_function_body_parse_slices_append_from_config_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<PipelineConfig> config, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::append_from_config_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[17]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_parse_slices_has_slice_candidates(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	shared_p<ProjectManifest> manifest = __latency_fn_project_manifest_from_project_dir(config->project_dir);
	shared_p<SourceUnitTable> sourceUnits = __latency_fn_source_units_table_from_manifest(manifest);
	__latency_fn_resident_function_body_parse_slices_append_from_body_work_decisions(report, sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
