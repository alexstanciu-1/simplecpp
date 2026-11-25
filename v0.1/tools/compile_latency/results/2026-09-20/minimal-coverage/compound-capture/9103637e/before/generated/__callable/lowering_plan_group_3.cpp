#include <scpp/lang/php.hpp>
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowSpan.hpp"
#include "__types/LoweringBlockedRequestRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__callable/__latency_fn_lowering_plan_control_flow_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_blocked_reason.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_cache_owner_symbol.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_feature.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_lowering_adapter.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_project_callable_blocked_reason.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_project_callable_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_status.hpp"
#include "__callable/__latency_fn_lowering_plan_blocked_request_from_backend_request.hpp"
#include "__callable/__latency_fn_lowering_plan_plan_effect_no_step_id.hpp"
#include "__callable/__latency_fn_lowering_plan_append_blocked_request.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_emit_status_blocked_id.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_emit_status_ready_for_text_sink_id.hpp"
#include "__callable/__latency_fn_lowering_plan_finalize_backend_emit_status.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_first_request_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_span_is_empty.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_span_request_at.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_id.hpp"
#include "__callable/__latency_fn_lowering_plan_append_blocked_request.hpp"
#include "__callable/__latency_fn_lowering_plan_append_work_ref.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_request_is_step_ready.hpp"
#include "__callable/__latency_fn_lowering_plan_blocked_request_from_backend_request.hpp"
#include "__callable/__latency_fn_lowering_plan_finalize_backend_emit_status.hpp"
#include "__callable/__latency_fn_lowering_plan_from_entry_and_backend_requests.hpp"
#include "__callable/__latency_fn_lowering_plan_new_plan_with_storage_capacity.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_lowering_plan_step_by_id.hpp"
#include "__callable/__latency_fn_lowering_plan_step_by_id.hpp"
#include "__callable/__latency_fn_lowering_plan_work_id.hpp"
#include "__callable/__latency_fn_lowering_plan_work_id_by_step_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
BackendControlFlowOperandRow __latency_fn_lowering_plan_control_flow_operand_by_owner_row_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::control_flow_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[39]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(plan->control_flow_operand_count))))) {
		BackendControlFlowOperandRow row = plan->control_flow_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(plan->control_flow_operand_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendControlFlowOperandRow row = plan->control_flow_operands[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = plan->control_flow_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendControlFlowOperandRow empty = BackendControlFlowOperandRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
LoweringBlockedRequestRow __latency_fn_lowering_plan_blocked_request_from_backend_request(int_t<std::uint32_t> blockedRequestId, BackendRequestAuthorizationRow request) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::blocked_request_from_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[40]);
	LoweringBlockedRequestRow row = LoweringBlockedRequestRow{};
	row->blocked_request_id = blockedRequestId;
	row->backend_request_id = __latency_fn_backend_preflight_requests_work_id(request);
	row->contract_id = __latency_fn_backend_preflight_requests_work_contract(request);
	row->project_callable_contract_id = __latency_fn_backend_preflight_requests_work_project_callable_contract(request);
	row->source_row_id = __latency_fn_backend_preflight_requests_work_source_row(request);
	row->feature_id = __latency_fn_backend_preflight_requests_work_feature(request);
	row->lowering_adapter_id = __latency_fn_backend_preflight_requests_work_lowering_adapter(request);
	row->cache_owner_symbol_id = __latency_fn_backend_preflight_requests_work_cache_owner_symbol(request);
	row->plan_effect_id = __latency_fn_lowering_plan_plan_effect_no_step_id();
	row->status_id = __latency_fn_backend_preflight_requests_work_status(request);
	row->blocked_reason_id = __latency_fn_backend_preflight_requests_work_blocked_reason(request);
	row->project_callable_blocked_reason_id = __latency_fn_backend_preflight_requests_work_project_callable_blocked_reason(request);
	return row;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_blocked_request(shared_p<LoweringPlan> plan, LoweringBlockedRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_blocked_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[41]);
	row->blocked_request_id = __latency_fn_structure_row_ids_next_dense_id(php::count(plan->blocked_requests));
	(void) plan->blocked_requests.append(row);
	plan->blocked_request_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(plan->blocked_requests));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_finalize_backend_emit_status(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::finalize_backend_emit_status", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[42]);
	if (static_cast<bool>(((cast<int_t<>>(plan->blocked_request_count) > static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(plan->step_count), static_cast<int_t<> >(0))))) {
		plan->backend_emit_status_id = __latency_fn_lowering_plan_backend_emit_status_blocked_id();
		return;
	}
	plan->backend_emit_status_id = __latency_fn_lowering_plan_backend_emit_status_ready_for_text_sink_id();
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
shared_p<LoweringPlan> __latency_fn_lowering_plan_from_entry_and_backend_requests(AnalysisEntryContextRow entry, shared_p<BackendRequestAuthorizationArtifact> requests) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::from_entry_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[43]);
	shared_p<LoweringPlan> plan = __latency_fn_lowering_plan_new_plan_with_storage_capacity(cast<int_t<>>(requests->ready_count), static_cast<int_t<> >(0), cast<int_t<>>(requests->blocked_count), entry->source_unit_id, entry->symbol_id);
	BackendRequestRowSpan span = __latency_fn_backend_preflight_requests_first_request_span(requests);
	while (static_cast<bool>((!__latency_fn_backend_preflight_requests_request_span_is_empty(span)))) {
		int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((offset < cast<int_t<>>(span->count)))) {
			BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_span_request_at(requests, span, offset);
			if (static_cast<bool>(php::condition_truthy(__latency_fn_lowering_plan_backend_request_is_step_ready(request)))) {
				__latency_fn_lowering_plan_append_work_ref(plan, __latency_fn_backend_preflight_requests_work_id(request));
			}
			else {
				__latency_fn_lowering_plan_append_blocked_request(plan, __latency_fn_lowering_plan_blocked_request_from_backend_request(__latency_fn_structure_row_ids_none_id(), request));
			}
			offset = (offset + static_cast<int_t<> >(1));
		}
		span = __latency_fn_backend_preflight_requests_next_request_span(requests, span);
	}
	__latency_fn_lowering_plan_finalize_backend_emit_status(plan);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
LoweringStep __latency_fn_lowering_plan_step_by_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> stepId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::step_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[44]);
	auto __latency_local_0 = plan->steps;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->step_id), cast<int_t<>>(stepId)))) {
			return row;
		}
	}
	LoweringStep empty = LoweringStep{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_id_by_step_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> stepId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_id_by_step_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[45]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(stepId, cast<int_t<>>(plan->work_ref_count))))) {
		return plan->work_ids[__latency_fn_structure_row_ids_dense_index(stepId)];
	}
	LoweringStep row = __latency_fn_lowering_plan_step_by_id(plan, cast<int_t<std::uint32_t>>(stepId));
	if (static_cast<bool>((cast<int_t<>>(row->step_id) > static_cast<int_t<> >(0)))) {
		return __latency_fn_lowering_plan_work_id(row);
	}
	return __latency_fn_structure_row_ids_none_id();
}

}
