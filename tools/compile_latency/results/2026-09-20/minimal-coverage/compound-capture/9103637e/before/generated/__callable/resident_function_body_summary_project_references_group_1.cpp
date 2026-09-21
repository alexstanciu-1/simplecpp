#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendReferenceCandidateRow.hpp"
#include "__types/ResidentFunctionBodySummaryProjectReferenceRow.hpp"
#include "__types/ResidentProjectSymbolNameLookupRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_logical_frontend_reference_candidates.hpp"
#include "__types/resident_function_body_summary_project_references.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_direct_function_symbol_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_multiple_matching_symbols_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_matching_symbol_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_ambiguous_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_missing_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_access_path_summary_source_range_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_row_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_ambiguous_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_missing_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_access_path_full_frontend_fallback_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_append_row.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_append_from_logical_reference_candidates_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_append_row.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_source_text_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_symbol_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_row_from_candidate.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_callee_hash_from_candidate.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_candidate_has_reference.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_candidate_uses_previous_source.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_row_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_source_text_for_candidate.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_source_units_from_config.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_build_lookup_slots.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_lookup_by_hash_and_length.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_lookup_slot_count.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
ResidentFunctionBodySummaryProjectReferenceRow __latency_fn_resident_function_body_summary_project_references_row_from_lookup(ResidentFunctionBodyLogicalFrontendReferenceCandidateRow candidate, ResidentProjectSymbolNameLookupRow lookup, int_t<std::uint32_t> projectReferenceId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::row_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[14]);
	ResidentFunctionBodySummaryProjectReferenceRow row = ResidentFunctionBodySummaryProjectReferenceRow{};
	row->owner_run_id = candidate->owner_run_id;
	row->source_unit_id = candidate->source_unit_id;
	row->symbol_id = candidate->symbol_id;
	row->logical_reference_candidate_id = candidate->candidate_id;
	row->project_reference_id = projectReferenceId;
	row->candidate_node_id = candidate->candidate_node_id;
	row->callee_start_offset = candidate->callee_start_offset;
	row->callee_length = candidate->callee_length;
	row->actual_arg_count = candidate->actual_arg_count;
	row->match_count = lookup->match_count;
	row->resolved_symbol_id = lookup->symbol_id;
	row->resolved_source_unit_id = lookup->source_unit_id;
	row->body_span_kind_id = candidate->body_span_kind_id;
	row->access_path_id = __latency_fn_resident_function_body_summary_project_references_access_path_summary_source_range_id();
	if (static_cast<bool>((php::identical(cast<int_t<>>(lookup->lookup_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(lookup->match_count), static_cast<int_t<> >(0))))) {
		row->reference_status_id = __latency_fn_project_reference_resolution_status_missing_id();
		row->reference_resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_no_matching_symbol_id();
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(lookup->match_count), static_cast<int_t<> >(1)))) {
			row->reference_status_id = __latency_fn_project_reference_resolution_status_resolved_id();
			row->reference_resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_direct_function_symbol_id();
		}
		else {
			row->resolved_symbol_id = __latency_fn_structure_row_ids_none_id();
			row->resolved_source_unit_id = __latency_fn_structure_row_ids_none_id();
			row->reference_status_id = __latency_fn_project_reference_resolution_status_ambiguous_id();
			row->reference_resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_multiple_matching_symbols_id();
		}
	}
	row->status_id = __latency_fn_resident_function_body_summary_project_references_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_summary_project_references_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
void __latency_fn_resident_function_body_summary_project_references_append_row(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodySummaryProjectReferenceRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[15]);
	row->summary_reference_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_summary_project_references));
	(void) report->resident_function_body_summary_project_references.append(row);
	report->resident_function_body_summary_project_reference_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int(php::count(report->resident_function_body_summary_project_references));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->access_path_id), cast<int_t<>>(__latency_fn_resident_function_body_summary_project_references_access_path_full_frontend_fallback_id())))) {
		report->resident_function_body_summary_project_reference_fallback_scan_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_fallback_scan_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_summary_project_references_status_blocked_id())))) {
		report->resident_function_body_summary_project_reference_blocked_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_blocked_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id())))) {
		report->resident_function_body_summary_project_reference_resolved_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_resolved_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_missing_id())))) {
			report->resident_function_body_summary_project_reference_missing_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_missing_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_ambiguous_id())))) {
				report->resident_function_body_summary_project_reference_ambiguous_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_ambiguous_count) + static_cast<int_t<> >(1)));
			}
			else {
				report->resident_function_body_summary_project_reference_unresolved_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_unresolved_count) + static_cast<int_t<> >(1)));
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
void __latency_fn_resident_function_body_summary_project_references_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[16]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_summary_project_references_uint32_from_int(rowCount), __latency_fn_resident_function_body_summary_project_references_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodySummaryProjectReferenceRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
void __latency_fn_resident_function_body_summary_project_references_append_from_logical_reference_candidates_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<PipelineConfig> currentConfig, shared_p<PipelineConfig> previousConfig, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::append_from_logical_reference_candidates_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[17]);
	int_t<> foundCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> skippedNoCandidateCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	bool_t needsPreviousSource = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	auto __latency_local_0 = report->resident_function_body_logical_frontend_reference_candidates;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto candidate = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(candidate->owner_run_id), cast<int_t<>>(ownerRunId))))) {
			continue;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(candidate->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id())))) {
			skippedNoCandidateCount = (skippedNoCandidateCount + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_function_body_summary_project_references_candidate_has_reference(candidate)))) {
			foundCount = (foundCount + static_cast<int_t<> >(1));
			if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_function_body_summary_project_references_candidate_uses_previous_source(candidate)))) {
				needsPreviousSource = bool_t(static_cast<bool_t>(true));
			}
		}
	}
	if (static_cast<bool>((skippedNoCandidateCount > static_cast<int_t<> >(0)))) {
		report->resident_function_body_summary_project_reference_skipped_no_candidate_count = __latency_fn_resident_function_body_summary_project_references_uint32_from_int((cast<int_t<>>(report->resident_function_body_summary_project_reference_skipped_no_candidate_count) + skippedNoCandidateCount));
	}
	if (static_cast<bool>((foundCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_summary_project_references));
	shared_p<SourceUnitTable> currentSourceUnits = __latency_fn_resident_function_body_summary_project_references_source_units_from_config(currentConfig);
	shared_p<SourceUnitTable> previousSourceUnits = currentSourceUnits;
	if (static_cast<bool>(php::condition_truthy(needsPreviousSource))) {
		previousSourceUnits = __latency_fn_resident_function_body_summary_project_references_source_units_from_config(previousConfig);
	}
	int_t<> lookupSlotCount = required_cast<int_t<>>(__latency_fn_resident_project_symbol_name_lookups_lookup_slot_count(report, ownerRunId));
	vector_t<int_t<std::uint32_t>> lookupIds = {};
	__latency_fn_resident_project_symbol_name_lookups_build_lookup_slots(report, ownerRunId, lookupSlotCount, lookupIds);
	int_t<> projectReferenceOffset = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_2 = report->resident_function_body_logical_frontend_reference_candidates;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto candidate = __latency_local_3.value_copy();
		if (static_cast<bool>((php::not_identical(cast<int_t<>>(candidate->owner_run_id), cast<int_t<>>(ownerRunId)) || (!__latency_fn_resident_function_body_summary_project_references_candidate_has_reference(candidate))))) {
			continue;
		}
		string_t sourceText = required_cast<string_t>(__latency_fn_resident_function_body_summary_project_references_source_text_for_candidate(currentSourceUnits, previousSourceUnits, candidate));
		if (static_cast<bool>(php::identical(sourceText, string_t("")))) {
			__latency_fn_resident_function_body_summary_project_references_append_row(report, __latency_fn_resident_function_body_summary_project_references_blocked_row_from_candidate(candidate, __latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_source_text_id()));
			continue;
		}
		int_t<std::uint32_t> calleeHash = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_summary_project_references_callee_hash_from_candidate(sourceText, candidate));
		if (static_cast<bool>(php::identical(cast<int_t<>>(calleeHash), static_cast<int_t<> >(0)))) {
			__latency_fn_resident_function_body_summary_project_references_append_row(report, __latency_fn_resident_function_body_summary_project_references_blocked_row_from_candidate(candidate, __latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_symbol_id()));
			continue;
		}
		ResidentProjectSymbolNameLookupRow lookup = __latency_fn_resident_project_symbol_name_lookups_lookup_by_hash_and_length(report, report, lookupIds, ownerRunId, calleeHash, candidate->callee_length);
		projectReferenceOffset = (projectReferenceOffset + static_cast<int_t<> >(1));
		__latency_fn_resident_function_body_summary_project_references_append_row(report, __latency_fn_resident_function_body_summary_project_references_row_from_lookup(candidate, lookup, __latency_fn_resident_function_body_summary_project_references_uint32_from_int(projectReferenceOffset)));
	}
	__latency_fn_resident_function_body_summary_project_references_append_memory_estimate(report, (php::count(report->resident_function_body_summary_project_references) - startCount));
}

}
