#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeListOwner.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ResidentFunctionBodyParseSliceRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishPlanRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/RowListPublishResult.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/resident_function_body_parse_slices.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_parse_slice_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_cleanup_retained_owner_list.hpp"
#include "__callable/__latency_fn_frontend_node_lists_publish_owner_list_for_generation.hpp"
#include "__callable/__latency_fn_project_reference_resolution_argument_count_from_call_expression.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_body_model.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_source_range_from_call_expression.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_empty_replacement_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_parse_slice_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_remap_proof_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_parser_diagnostic_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_failed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_plan_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_token_cursor_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_parse_slice_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_remap_proof_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_row_from_plan.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_publish_status_ok_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_segmented_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_append_result.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
ResidentFunctionBodyParseSliceRow __latency_fn_resident_function_body_row_list_publish_results_parse_slice_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> sliceId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::parse_slice_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[14]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sliceId, php::count(report->resident_function_body_parse_slices))))) {
		ResidentFunctionBodyParseSliceRow slice = report->resident_function_body_parse_slices[__latency_fn_structure_row_ids_dense_index(sliceId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(slice->slice_id), cast<int_t<>>(sliceId)))) {
			return slice;
		}
	}
	auto __latency_local_0 = report->resident_function_body_parse_slices;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto slice = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(slice->slice_id), cast<int_t<>>(sliceId)))) {
			return slice;
		}
	}
	ResidentFunctionBodyParseSliceRow empty = ResidentFunctionBodyParseSliceRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishResultRow __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(ResidentFunctionBodyRowListPublishPlanRow plan, int_t<std::uint32_t> parseSliceId, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_row_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[15]);
	ResidentFunctionBodyRowListPublishResultRow row = ResidentFunctionBodyRowListPublishResultRow{};
	row->owner_run_id = plan->owner_run_id;
	row->publish_plan_id = plan->publish_plan_id;
	row->stable_node_remap_proof_id = plan->stable_node_remap_proof_id;
	row->function_body_work_decision_id = plan->function_body_work_decision_id;
	row->current_function_body_snapshot_id = plan->current_function_body_snapshot_id;
	row->source_unit_id = plan->source_unit_id;
	row->symbol_id = plan->symbol_id;
	row->parse_slice_id = parseSliceId;
	row->previous_body_list_id = plan->previous_body_list_id;
	row->published_body_list_id = __latency_fn_structure_row_ids_none_id();
	row->previous_body_generation_id = plan->previous_body_generation_id;
	row->published_body_generation_id = __latency_fn_structure_row_ids_none_id();
	row->previous_body_node_count = plan->previous_body_node_count;
	row->status_id = __latency_fn_resident_function_body_row_list_publish_results_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishResultRow __latency_fn_resident_function_body_row_list_publish_results_row_from_plan(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyRowListPublishPlanRow plan) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::row_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[16]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(plan->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_plans_status_ready_id()))))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, __latency_fn_structure_row_ids_none_id(), __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_plan_not_ready_id());
	}
	ResidentFunctionBodyStableNodeRemapProofRow remap = __latency_fn_resident_function_body_row_list_publish_results_remap_proof_by_id(report, plan->stable_node_remap_proof_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(remap->remap_proof_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, __latency_fn_structure_row_ids_none_id(), __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_remap_proof_id());
	}
	ResidentFunctionBodyParseSliceRow slice = __latency_fn_resident_function_body_row_list_publish_results_parse_slice_by_id(report, remap->parse_slice_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(slice->slice_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, remap->parse_slice_id, __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_parse_slice_id());
	}
	shared_p<FrontendModel> model = create<FrontendModel>();
	int_t<std::uint32_t> actualTokenAfterBodyIndex = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> parserDiagnosticCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((!__latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice(sourceUnits, slice, model, actualTokenAfterBodyIndex, parserDiagnosticCount)))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, slice->slice_id, __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_source_unit_id());
	}
	if (static_cast<bool>((cast<int_t<>>(parserDiagnosticCount) > static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, slice->slice_id, __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_parser_diagnostic_id());
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(actualTokenAfterBodyIndex), cast<int_t<>>(__latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body(slice)))))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, slice->slice_id, __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_token_cursor_mismatch_id());
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(model->node_count), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_row_list_publish_results_blocked_row_from_plan(plan, slice->slice_id, __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_empty_replacement_body_id());
	}
	shared_p<FrontendNodeListOwner> owner = create<FrontendNodeListOwner>();
	owner->owner_source_unit_id = plan->source_unit_id;
	owner->current_list_id = plan->previous_body_list_id;
	owner->current_generation_id = plan->previous_body_generation_id;
	owner->current_rows->row_count = plan->previous_body_node_count;
	RowListPublishResult publishResult = __latency_fn_frontend_node_lists_publish_owner_list_for_generation(owner, model->node_rows, plan->planned_body_list_id, plan->planned_body_generation_id);
	RowListPublishResult cleanupResult = RowListPublishResult{};
	bool_t publishOk = required_cast<bool_t>(php::identical(cast<int_t<>>(publishResult->status_id), cast<int_t<>>(__latency_fn_row_segment_policy_publish_status_ok_id())));
	if (static_cast<bool>((publishOk && (cast<int_t<>>(plan->planned_cleanup_released_body_row_bytes) > static_cast<int_t<> >(0))))) {
		cleanupResult = __latency_fn_frontend_node_lists_cleanup_retained_owner_list(owner);
	}
	int_t<std::uint32_t> referenceCandidateNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> referenceCandidateCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> referenceCalleeStartOffset = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> referenceCalleeLength = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> referenceActualArgCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>(php::condition_truthy(publishOk))) {
		FrontendNodeRow callNode = __latency_fn_project_reference_resolution_call_expression_node_from_body_model(model);
		if (static_cast<bool>((cast<int_t<>>(callNode->node_id) > static_cast<int_t<> >(0)))) {
			referenceCandidateNodeId = callNode->node_id;
			referenceCandidateCount = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
			referenceActualArgCount = __latency_fn_project_reference_resolution_argument_count_from_call_expression(model, callNode);
			SourceRangeRow calleeRange = __latency_fn_project_reference_resolution_callee_name_source_range_from_call_expression(model, callNode);
			if (static_cast<bool>((cast<int_t<>>(calleeRange->length) > static_cast<int_t<> >(0)))) {
				referenceCalleeStartOffset = calleeRange->start_offset;
				referenceCalleeLength = calleeRange->length;
			}
		}
	}
	ResidentFunctionBodyRowListPublishResultRow row = ResidentFunctionBodyRowListPublishResultRow{};
	row->owner_run_id = plan->owner_run_id;
	row->publish_plan_id = plan->publish_plan_id;
	row->stable_node_remap_proof_id = plan->stable_node_remap_proof_id;
	row->function_body_work_decision_id = plan->function_body_work_decision_id;
	row->current_function_body_snapshot_id = plan->current_function_body_snapshot_id;
	row->source_unit_id = plan->source_unit_id;
	row->symbol_id = plan->symbol_id;
	row->parse_slice_id = slice->slice_id;
	row->previous_body_list_id = publishResult->previous_list_id;
	row->published_body_list_id = publishResult->published_list_id;
	row->previous_body_generation_id = publishResult->previous_generation_id;
	row->published_body_generation_id = publishResult->published_generation_id;
	row->published_body_reference_candidate_node_id = referenceCandidateNodeId;
	row->published_body_reference_candidate_count = referenceCandidateCount;
	row->published_body_reference_callee_start_offset = referenceCalleeStartOffset;
	row->published_body_reference_callee_length = referenceCalleeLength;
	row->published_body_reference_actual_arg_count = referenceActualArgCount;
	row->previous_body_node_count = publishResult->previous_row_count;
	row->published_body_node_count = publishResult->published_row_count;
	row->previous_segment_count = publishResult->previous_segment_count;
	row->published_segment_count = publishResult->published_segment_count;
	row->retained_old_body_row_bytes = publishResult->retained_old_generation_bytes;
	row->cleanup_released_body_row_bytes = cleanupResult->cleanup_released_bytes;
	row->publish_count = publishResult->publish_count;
	row->cleanup_count = owner->cleanup_count;
	row->storage_kind_id = model->node_rows->storage_kind_id;
	row->status_id = __latency_fn_resident_function_body_row_list_publish_results_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_none_id();
	if (static_cast<bool>((!publishOk))) {
		row->status_id = __latency_fn_resident_function_body_row_list_publish_results_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_failed_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
void __latency_fn_resident_function_body_row_list_publish_results_append_result(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyRowListPublishResultRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::append_result", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[17]);
	row->publish_result_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_row_list_publish_results));
	(void) report->resident_function_body_row_list_publish_results.append(row);
	report->resident_function_body_row_list_publish_result_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int(php::count(report->resident_function_body_row_list_publish_results));
	report->resident_function_body_row_list_publish_result_replacement_node_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_replacement_node_count) + cast<int_t<>>(row->published_body_node_count)));
	report->resident_function_body_row_list_publish_result_retained_old_body_row_bytes = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_retained_old_body_row_bytes) + cast<int_t<>>(row->retained_old_body_row_bytes)));
	report->resident_function_body_row_list_publish_result_cleanup_released_body_row_bytes = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_cleanup_released_body_row_bytes) + cast<int_t<>>(row->cleanup_released_body_row_bytes)));
	report->resident_function_body_row_list_publish_result_segment_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_segment_count) + cast<int_t<>>(row->published_segment_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id())))) {
		report->resident_function_body_row_list_publish_result_ready_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_ready_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_row_list_publish_result_published_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_published_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>((cast<int_t<>>(row->cleanup_count) > static_cast<int_t<> >(0)))) {
			report->resident_function_body_row_list_publish_result_cleanup_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_cleanup_count) + static_cast<int_t<> >(1)));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->storage_kind_id), cast<int_t<>>(__latency_fn_frontend_node_lists_storage_segmented_id())))) {
			report->resident_function_body_row_list_publish_result_segmented_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_segmented_count) + static_cast<int_t<> >(1)));
		}
	}
	else {
		report->resident_function_body_row_list_publish_result_blocked_count = __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int((cast<int_t<>>(report->resident_function_body_row_list_publish_result_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}
