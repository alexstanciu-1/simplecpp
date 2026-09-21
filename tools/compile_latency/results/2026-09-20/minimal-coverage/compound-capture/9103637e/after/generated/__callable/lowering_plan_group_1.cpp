#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__callable/__latency_fn_lowering_plan_new_plan.hpp"
#include "__callable/__latency_fn_lowering_plan_new_plan_with_storage_capacity.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_is_step_ready.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_request_is_step_ready.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_immediate_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_lowering_adapter.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_reference.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_step_kind.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_target_symbol.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_type_ref.hpp"
#include "__callable/__latency_fn_lowering_plan_step_from_backend_request.hpp"
#include "__callable/__latency_fn_lowering_plan_step_source_key_role_base_id.hpp"
#include "__callable/__latency_fn_lowering_plan_work_id.hpp"
#include "__callable/__latency_fn_lowering_plan_lowering_step_id.hpp"
#include "__callable/__latency_fn_lowering_plan_work_step_kind.hpp"
#include "__callable/__latency_fn_lowering_plan_work_contract.hpp"
#include "__callable/__latency_fn_lowering_plan_work_lowering_adapter.hpp"
#include "__callable/__latency_fn_lowering_plan_work_type_ref.hpp"
#include "__callable/__latency_fn_lowering_plan_work_source_row.hpp"
#include "__callable/__latency_fn_lowering_plan_work_source_reference.hpp"
#include "__callable/__latency_fn_lowering_plan_work_source_key_role.hpp"
#include "__callable/__latency_fn_lowering_plan_work_local_slot_source_row.hpp"
#include "__callable/__latency_fn_lowering_plan_work_target_symbol.hpp"
#include "__callable/__latency_fn_lowering_plan_work_immediate_value.hpp"
#include "__callable/__latency_fn_lowering_plan_refresh_step_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
shared_p<LoweringPlan> __latency_fn_lowering_plan_new_plan(int_t<> stepCapacity, int_t<> blockedRequestCapacity, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> ownerSymbolId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::new_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[14]);
	return __latency_fn_lowering_plan_new_plan_with_storage_capacity(stepCapacity, stepCapacity, blockedRequestCapacity, cast<int_t<std::uint32_t>>(ownerSourceUnitId), cast<int_t<std::uint32_t>>(ownerSymbolId));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
bool_t __latency_fn_lowering_plan_backend_request_is_step_ready(BackendRequestAuthorizationRow request) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::backend_request_is_step_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[15]);
	return __latency_fn_backend_preflight_requests_work_is_step_ready(request);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
LoweringStep __latency_fn_lowering_plan_step_from_backend_request(int_t<std::uint32_t> stepId, BackendRequestAuthorizationRow request) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::step_from_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[16]);
	LoweringStep step = LoweringStep{};
	step->step_id = stepId;
	step->backend_request_id = __latency_fn_backend_preflight_requests_work_id(request);
	step->step_kind_id = __latency_fn_backend_preflight_requests_work_step_kind(request);
	step->contract_id = __latency_fn_backend_preflight_requests_work_contract(request);
	step->lowering_adapter_id = __latency_fn_backend_preflight_requests_work_lowering_adapter(request);
	step->type_ref_id = __latency_fn_backend_preflight_requests_work_type_ref(request);
	step->source_row_id = __latency_fn_backend_preflight_requests_work_source_row(request);
	step->source_reference_id = __latency_fn_backend_preflight_requests_work_source_reference(request);
	step->target_symbol_id = __latency_fn_backend_preflight_requests_work_target_symbol(request);
	step->source_key_role_id = __latency_fn_lowering_plan_step_source_key_role_base_id();
	step->value = __latency_fn_backend_preflight_requests_work_immediate_value(request);
	return step;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_id(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[17]);
	if (static_cast<bool>((cast<int_t<>>(work->backend_request_id) > static_cast<int_t<> >(0)))) {
		return work->backend_request_id;
	}
	return work->step_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_lowering_step_id(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::lowering_step_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[18]);
	return work->step_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_lowering_plan_work_step_kind(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[19]);
	return work->step_kind_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_lowering_plan_work_contract(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[20]);
	return work->contract_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_lowering_plan_work_lowering_adapter(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_lowering_adapter", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[21]);
	return work->lowering_adapter_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_type_ref(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[22]);
	return work->type_ref_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_source_row(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_source_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[23]);
	return work->source_row_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_source_reference(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_source_reference", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[24]);
	return work->source_reference_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_lowering_plan_work_source_key_role(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_source_key_role", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[25]);
	return work->source_key_role_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_local_slot_source_row(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_local_slot_source_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[26]);
	return work->local_slot_source_row_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_work_target_symbol(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_target_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[27]);
	return work->target_symbol_id;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::int32_t> __latency_fn_lowering_plan_work_immediate_value(LoweringStep work) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::work_immediate_value", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[28]);
	return work->value;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_refresh_step_count(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::refresh_step_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[29]);
	plan->step_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(plan->work_ref_count) + php::count(plan->steps)));
}

}
