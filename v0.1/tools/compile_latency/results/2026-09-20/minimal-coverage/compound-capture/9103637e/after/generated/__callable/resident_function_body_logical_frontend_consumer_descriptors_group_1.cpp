#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/resident_function_body_logical_frontend_views.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_empty_body_span_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_has_ready_logical_views.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_publish_result_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_previous_publish_result_by_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_snapshot_for_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_logical_view_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_view.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_empty_body_span_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_current_snapshot_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_retained_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_retained_previous_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_previous_publish_result_by_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_retained_body_span_from_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_snapshot_for_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_empty_body_span_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::blocked_reason_empty_body_span_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_has_ready_logical_views(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::has_ready_logical_views", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[17]);
	auto __latency_local_0 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto view = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(view->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(view->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishResultRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_publish_result_by_decision(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> decisionId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::publish_result_by_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[18]);
	auto __latency_local_0 = report->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(result->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(result->function_body_work_decision_id), cast<int_t<>>(decisionId))) && php::identical(cast<int_t<>>(result->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
			return result;
		}
	}
	ResidentFunctionBodyRowListPublishResultRow empty = ResidentFunctionBodyRowListPublishResultRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishResultRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_previous_publish_result_by_symbol(shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::previous_publish_result_by_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[19]);
	auto __latency_local_0 = previous->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(result->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(result->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(result->symbol_id), cast<int_t<>>(symbolId))) && php::identical(cast<int_t<>>(result->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
			return result;
		}
	}
	ResidentFunctionBodyRowListPublishResultRow empty = ResidentFunctionBodyRowListPublishResultRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_snapshot_for_decision(shared_p<CompilerProjectRunReport> report, shared_p<CompilerProjectRunReport> previous, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::snapshot_for_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[20]);
	if (static_cast<bool>((cast<int_t<>>(decision->current_function_body_snapshot_id) > static_cast<int_t<> >(0)))) {
		ResidentFunctionBodySnapshotRow current = __latency_fn_resident_function_body_work_decisions_snapshot_by_id(report, decision->current_function_body_snapshot_id);
		if (static_cast<bool>((cast<int_t<>>(current->snapshot_id) > static_cast<int_t<> >(0)))) {
			return current;
		}
	}
	if (static_cast<bool>((cast<int_t<>>(decision->previous_function_body_snapshot_id) > static_cast<int_t<> >(0)))) {
		ResidentFunctionBodySnapshotRow old = __latency_fn_resident_function_body_work_decisions_snapshot_by_id(previous, decision->previous_function_body_snapshot_id);
		if (static_cast<bool>((cast<int_t<>>(old->snapshot_id) > static_cast<int_t<> >(0)))) {
			return old;
		}
	}
	ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_view(ResidentFunctionBodyLogicalFrontendViewRow view) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::descriptor_from_view", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[21]);
	ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow row = ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow{};
	row->owner_run_id = view->owner_run_id;
	row->source_unit_id = view->source_unit_id;
	row->logical_frontend_view_id = view->view_id;
	row->source_unit_repoint_proof_id = view->source_unit_repoint_proof_id;
	row->current_frontend_state_id = view->current_frontend_state_id;
	row->current_frontend_node_list_snapshot_id = view->current_frontend_node_list_snapshot_id;
	row->source_unit_list_id = view->current_source_unit_list_id;
	row->source_unit_generation_id = view->current_source_unit_generation_id;
	row->source_unit_row_count = view->logical_source_unit_row_count;
	row->logical_body_node_count = view->logical_body_node_count;
	row->published_body_segment_count = view->published_segment_count;
	row->source_unit_storage_kind_id = view->current_source_unit_storage_kind_id;
	row->descriptor_kind_id = view->view_kind_id;
	row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_none_id();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(view->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
		row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_logical_view_not_ready_id();
		row->descriptor_kind_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_kind_blocked_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision(int_t<std::uint32_t> descriptorId, ResidentFunctionBodyWorkDecisionRow decision, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::blocked_body_span_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[22]);
	ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow row = ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow{};
	row->logical_consumer_descriptor_id = descriptorId;
	row->owner_run_id = decision->owner_run_id;
	row->source_unit_id = decision->source_unit_id;
	row->symbol_id = decision->symbol_id;
	row->function_body_work_decision_id = decision->decision_id;
	row->previous_function_body_snapshot_id = decision->previous_function_body_snapshot_id;
	row->current_function_body_snapshot_id = decision->current_function_body_snapshot_id;
	row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_retained_body_span_from_decision(shared_p<CompilerProjectRunReport> report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> descriptorId, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::retained_body_span_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[23]);
	ResidentFunctionBodySnapshotRow snapshot = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_snapshot_for_decision(report, previous, decision);
	if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision(cast<int_t<std::uint32_t>>(descriptorId), decision, __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_current_snapshot_id());
	}
	ResidentFunctionBodyRowListPublishResultRow previousResult = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_previous_publish_result_by_symbol(previous, decision->owner_run_id, decision->source_unit_id, decision->symbol_id);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(previousResult->publish_result_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(previousResult->published_body_list_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(previousResult->published_body_generation_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_body_span_from_decision(cast<int_t<std::uint32_t>>(descriptorId), decision, __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_missing_retained_body_list_id());
	}
	ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow row = ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow{};
	row->logical_consumer_descriptor_id = descriptorId;
	row->owner_run_id = decision->owner_run_id;
	row->source_unit_id = decision->source_unit_id;
	row->symbol_id = decision->symbol_id;
	row->function_body_work_decision_id = decision->decision_id;
	row->previous_function_body_snapshot_id = decision->previous_function_body_snapshot_id;
	row->current_function_body_snapshot_id = decision->current_function_body_snapshot_id;
	row->body_list_id = previousResult->published_body_list_id;
	row->body_generation_id = previousResult->published_body_generation_id;
	row->reference_candidate_node_id = previousResult->published_body_reference_candidate_node_id;
	row->reference_candidate_count = previousResult->published_body_reference_candidate_count;
	row->reference_callee_start_offset = previousResult->published_body_reference_callee_start_offset;
	row->reference_callee_length = previousResult->published_body_reference_callee_length;
	row->reference_actual_arg_count = previousResult->published_body_reference_actual_arg_count;
	row->source_unit_body_first_node_id = snapshot->body_first_node_id;
	row->source_unit_body_node_count = snapshot->body_node_count;
	row->logical_body_node_count = snapshot->body_node_count;
	row->published_segment_count = previousResult->published_segment_count;
	row->body_span_kind_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_retained_previous_body_list_id();
	row->body_storage_kind_id = previousResult->storage_kind_id;
	row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_none_id();
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->logical_body_node_count), static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_blocked_reason_empty_body_span_id();
	}
	return row;
}

}
