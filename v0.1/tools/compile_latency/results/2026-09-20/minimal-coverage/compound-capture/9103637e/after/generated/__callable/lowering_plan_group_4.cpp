#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/LoweringBlockedRequestRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__callable/__latency_fn_lowering_plan_blocked_request_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_lowering_plan_step_equals.hpp"
#include "__callable/__latency_fn_lowering_plan_blocked_request_equals.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_emit_status_name.hpp"
#include "__callable/__latency_fn_lowering_plan_plan_debug_string.hpp"
#include "__callable/__latency_fn_lowering_plan_lowering_step_kind_name.hpp"
#include "__callable/__latency_fn_lowering_plan_step_debug_string.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_name.hpp"
#include "__callable/__latency_fn_lowering_plan_blocked_request_debug_string.hpp"
#include "__callable/__latency_fn_lowering_plan_plan_stable_hash.hpp"
#include "__callable/__latency_fn_lowering_plan_step_stable_hash.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_stable_hash.hpp"
#include "__callable/__latency_fn_lowering_plan_binary_operand_stable_hash.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_stable_hash.hpp"
#include "__callable/__latency_fn_lowering_plan_local_operand_stable_hash.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_stable_hash.hpp"
#include "__callable/__latency_fn_lowering_plan_call_argument_stable_hash.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_stable_hash.hpp"
#include "__callable/__latency_fn_lowering_plan_control_flow_operand_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
LoweringBlockedRequestRow __latency_fn_lowering_plan_blocked_request_by_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> blockedRequestId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::blocked_request_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[46]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(blockedRequestId, cast<int_t<>>(plan->blocked_request_count))))) {
		LoweringBlockedRequestRow row = plan->blocked_requests[__latency_fn_structure_row_ids_dense_index(blockedRequestId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_request_id), cast<int_t<>>(blockedRequestId)))) {
			return row;
		}
	}
	auto __latency_local_0 = plan->blocked_requests;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_request_id), cast<int_t<>>(blockedRequestId)))) {
			return row;
		}
	}
	LoweringBlockedRequestRow empty = LoweringBlockedRequestRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
bool_t __latency_fn_lowering_plan_step_equals(LoweringStep left, LoweringStep right) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::step_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[47]);
	return ((((((((php::identical(cast<int_t<>>(left->step_id), cast<int_t<>>(right->step_id)) && php::identical(cast<int_t<>>(left->backend_request_id), cast<int_t<>>(right->backend_request_id))) && php::identical(cast<int_t<>>(left->step_kind_id), cast<int_t<>>(right->step_kind_id))) && php::identical(cast<int_t<>>(left->contract_id), cast<int_t<>>(right->contract_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->source_reference_id), cast<int_t<>>(right->source_reference_id))) && php::identical(cast<int_t<>>(left->target_symbol_id), cast<int_t<>>(right->target_symbol_id))) && php::identical(cast<int_t<>>(left->value), cast<int_t<>>(right->value)));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
bool_t __latency_fn_lowering_plan_blocked_request_equals(LoweringBlockedRequestRow left, LoweringBlockedRequestRow right) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::blocked_request_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[48]);
	return (((((php::identical(cast<int_t<>>(left->blocked_request_id), cast<int_t<>>(right->blocked_request_id)) && php::identical(cast<int_t<>>(left->backend_request_id), cast<int_t<>>(right->backend_request_id))) && php::identical(cast<int_t<>>(left->contract_id), cast<int_t<>>(right->contract_id))) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->blocked_reason_id), cast<int_t<>>(right->blocked_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
string_t __latency_fn_lowering_plan_plan_debug_string(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::plan_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[49]);
	string_t text = required_cast<string_t>(string_t("lowering_plan:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(plan->owner_symbol_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_lowering_plan_backend_emit_status_name(plan->backend_emit_status_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(plan->step_count)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(plan->blocked_request_count)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
string_t __latency_fn_lowering_plan_step_debug_string(LoweringStep row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::step_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[50]);
	string_t text = required_cast<string_t>(string_t("lowering_step:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->step_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(row->backend_request_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_lowering_plan_lowering_step_kind_name(row->step_kind_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->type_ref_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
string_t __latency_fn_lowering_plan_blocked_request_debug_string(LoweringBlockedRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::blocked_request_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[51]);
	string_t text = required_cast<string_t>(string_t("lowering_blocked_request:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->blocked_request_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_backend_preflight_requests_status_name(row->status_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_backend_preflight_requests_blocked_reason_name(row->blocked_reason_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_plan_stable_hash(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::plan_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[52]);
	string_t identity = required_cast<string_t>(string_t("lowering_plan:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->artifact_kind_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->source_model_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->plan_model_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->owner_source_unit_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->owner_symbol_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->step_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->work_ref_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->local_operand_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->call_argument_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->control_flow_operand_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->blocked_request_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(plan->backend_emit_status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_step_stable_hash(LoweringStep row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::step_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[53]);
	string_t identity = required_cast<string_t>(string_t("lowering_step:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->step_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->backend_request_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->step_kind_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->contract_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->lowering_adapter_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_reference_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->target_symbol_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->value)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_binary_operand_stable_hash(BackendBinaryOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::binary_operand_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[54]);
	return __latency_fn_backend_preflight_requests_binary_operand_stable_hash(row);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_local_operand_stable_hash(BackendLocalOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::local_operand_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[55]);
	return __latency_fn_backend_preflight_requests_local_operand_stable_hash(row);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_call_argument_stable_hash(BackendCallArgumentRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::call_argument_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[56]);
	return __latency_fn_backend_preflight_requests_call_argument_stable_hash(row);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_control_flow_operand_stable_hash(BackendControlFlowOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::control_flow_operand_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[57]);
	return __latency_fn_backend_preflight_requests_control_flow_operand_stable_hash(row);
}

}
