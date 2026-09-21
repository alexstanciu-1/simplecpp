#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/resident_function_body_logical_frontend_consumer_descriptors.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_empty_body_span_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_current_snapshot_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_publish_result_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_build_new_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_replacement_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_publish_result_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_published_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_snapshot_for_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_no_body_spans_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_published_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_retained_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_body_span.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_finish_descriptor.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_body_span.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_for_view.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_body_span_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_no_body_spans_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_build_new_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_retained_previous_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_view.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_finish_descriptor.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_published_body_span_from_decision(shared_p<CompilerProjectRunReport> report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> descriptorId, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::published_body_span_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[24]);
	ResidentFunctionBodySnapshotRow snapshot = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_snapshot_for_decision(report, previous, decision);
	if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision(cast<int_t<std::uint32_t>>(descriptorId), decision, __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_current_snapshot_id());
	}
	ResidentFunctionBodyRowListPublishResultRow result = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_publish_result_by_decision(report, decision->owner_run_id, decision->decision_id);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(result->publish_result_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(result->published_body_list_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(result->published_body_generation_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision(cast<int_t<std::uint32_t>>(descriptorId), decision, __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_publish_result_id());
	}
	ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow row = ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow{};
	row->logical_consumer_descriptor_id = descriptorId;
	row->owner_run_id = decision->owner_run_id;
	row->source_unit_id = decision->source_unit_id;
	row->symbol_id = decision->symbol_id;
	row->function_body_work_decision_id = decision->decision_id;
	row->previous_function_body_snapshot_id = decision->previous_function_body_snapshot_id;
	row->current_function_body_snapshot_id = decision->current_function_body_snapshot_id;
	row->body_list_id = result->published_body_list_id;
	row->body_generation_id = result->published_body_generation_id;
	row->reference_candidate_node_id = result->published_body_reference_candidate_node_id;
	row->reference_candidate_count = result->published_body_reference_candidate_count;
	row->reference_callee_start_offset = result->published_body_reference_callee_start_offset;
	row->reference_callee_length = result->published_body_reference_callee_length;
	row->reference_actual_arg_count = result->published_body_reference_actual_arg_count;
	row->source_unit_body_first_node_id = snapshot->body_first_node_id;
	row->source_unit_body_node_count = snapshot->body_node_count;
	row->logical_body_node_count = result->published_body_node_count;
	row->published_segment_count = result->published_segment_count;
	row->body_span_kind_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_replacement_body_list_id();
	if (static_cast<bool>(php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id())))) {
		row->body_span_kind_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_build_new_body_list_id();
	}
	row->body_storage_kind_id = result->storage_kind_id;
	row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_none_id();
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->logical_body_node_count), static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_empty_body_span_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_from_decision(shared_p<CompilerProjectRunReport> report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> descriptorId, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::body_span_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[25]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id())))) {
		return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_retained_body_span_from_decision(report, previous, cast<int_t<std::uint32_t>>(descriptorId), decision);
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id())) || php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id()))))) {
		return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_published_body_span_from_decision(report, previous, cast<int_t<std::uint32_t>>(descriptorId), decision);
	}
	return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision(cast<int_t<std::uint32_t>>(descriptorId), decision, __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_no_body_spans_id());
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_body_span(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::append_body_span", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[26]);
	row->span_descriptor_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_logical_frontend_body_span_descriptors));
	(void) report->resident_function_body_logical_frontend_body_span_descriptors.append(row);
	report->resident_function_body_logical_frontend_body_span_descriptor_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int(php::count(report->resident_function_body_logical_frontend_body_span_descriptors));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id())))) {
		report->resident_function_body_logical_frontend_body_span_descriptor_ready_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_body_span_descriptor_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_logical_frontend_body_span_descriptor_blocked_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_body_span_descriptor_blocked_count) + static_cast<int_t<> >(1)));
	}
	return row->span_descriptor_id;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_row(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::append_descriptor_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[27]);
	row->descriptor_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_logical_frontend_consumer_descriptors));
	(void) report->resident_function_body_logical_frontend_consumer_descriptors.append(row);
	report->resident_function_body_logical_frontend_consumer_descriptor_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int(php::count(report->resident_function_body_logical_frontend_consumer_descriptors));
	return row->descriptor_id;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_finish_descriptor(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::finish_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[28]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->descriptor_id, php::count(report->resident_function_body_logical_frontend_consumer_descriptors))))) {
		report->resident_function_body_logical_frontend_consumer_descriptors[__latency_fn_structure_row_ids_dense_index(row->descriptor_id)] = row;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id())))) {
		report->resident_function_body_logical_frontend_consumer_descriptor_ready_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_ready_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_logical_frontend_consumer_descriptor_source_unit_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_source_unit_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->descriptor_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_mixed_reuse_and_replacement_id())))) {
			report->resident_function_body_logical_frontend_consumer_descriptor_mixed_source_unit_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_mixed_source_unit_count) + static_cast<int_t<> >(1)));
		}
		report->resident_function_body_logical_frontend_consumer_descriptor_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_body_span_count) + cast<int_t<>>(row->body_span_descriptor_count)));
		report->resident_function_body_logical_frontend_consumer_descriptor_retained_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_retained_body_span_count) + cast<int_t<>>(row->retained_body_span_count)));
		report->resident_function_body_logical_frontend_consumer_descriptor_replacement_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_replacement_body_span_count) + cast<int_t<>>(row->replacement_body_span_count)));
		report->resident_function_body_logical_frontend_consumer_descriptor_build_new_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_build_new_body_span_count) + cast<int_t<>>(row->build_new_body_span_count)));
		report->resident_function_body_logical_frontend_consumer_descriptor_logical_body_node_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_logical_body_node_count) + cast<int_t<>>(row->logical_body_node_count)));
		report->resident_function_body_logical_frontend_consumer_descriptor_published_body_segment_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_published_body_segment_count) + cast<int_t<>>(row->published_body_segment_count)));
		report->resident_function_body_logical_frontend_consumer_descriptor_copied_frontend_node_row_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_copied_frontend_node_row_count) + cast<int_t<>>(row->copied_frontend_node_row_count)));
	}
	else {
		report->resident_function_body_logical_frontend_consumer_descriptor_blocked_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_for_view(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentFunctionBodyLogicalFrontendViewRow view) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::append_descriptor_for_view", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[29]);
	ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow descriptor = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_view(view);
	int_t<std::uint32_t> descriptorId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_row(report, descriptor));
	descriptor->descriptor_id = descriptorId;
	int_t<std::uint32_t> firstSpanId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<> spanCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> blockedSpanCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(view->owner_run_id)) || php::not_identical(cast<int_t<>>(decision->source_unit_id), cast<int_t<>>(view->source_unit_id))) || php::not_identical(cast<int_t<>>(decision->status_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_status_ready_id()))))) {
			continue;
		}
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id())) && php::not_identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id()))) && php::not_identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id()))))) {
			continue;
		}
		ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow span = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_from_decision(report, previous, cast<int_t<std::uint32_t>>(descriptorId), decision);
		int_t<std::uint32_t> spanId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_body_span(report, span));
		if (static_cast<bool>(php::identical(cast<int_t<>>(firstSpanId), static_cast<int_t<> >(0)))) {
			firstSpanId = cast<int_t<std::uint32_t>>(spanId);
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(span->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id())))) {
			spanCount = (spanCount + static_cast<int_t<> >(1));
			if (static_cast<bool>(php::identical(cast<int_t<>>(span->body_span_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_retained_previous_body_list_id())))) {
				descriptor->retained_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(descriptor->retained_body_span_count) + static_cast<int_t<> >(1)));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(span->body_span_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_build_new_body_list_id())))) {
					descriptor->build_new_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(descriptor->build_new_body_span_count) + static_cast<int_t<> >(1)));
				}
				else {
					descriptor->replacement_body_span_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(descriptor->replacement_body_span_count) + static_cast<int_t<> >(1)));
				}
			}
		}
		else {
			blockedSpanCount = (blockedSpanCount + static_cast<int_t<> >(1));
		}
	}
	descriptor->first_body_span_descriptor_id = firstSpanId;
	int_t<> totalSpanCount = required_cast<int_t<>>((spanCount + blockedSpanCount));
	descriptor->body_span_descriptor_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int(totalSpanCount);
	if (static_cast<bool>(php::identical(totalSpanCount, static_cast<int_t<> >(0)))) {
		descriptor->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id();
		descriptor->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_no_body_spans_id();
		descriptor->descriptor_kind_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_blocked_id();
	}
	else {
		if (static_cast<bool>((blockedSpanCount > static_cast<int_t<> >(0)))) {
			descriptor->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id();
			descriptor->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_body_span_blocked_id();
			descriptor->descriptor_kind_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_blocked_id();
		}
	}
	__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_finish_descriptor(report, descriptor);
}

}
