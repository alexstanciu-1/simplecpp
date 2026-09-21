#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_new_artifact.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_no_lowering_steps_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_kind_function_body_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_kind_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_kind_function_body_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_kind_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_immediate_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_runtime_opaque_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_emit_status_ready_for_text_sink_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_no_lowering_steps_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_emit_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_kind_function_body_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_from_plan.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_new_artifact(shared_p<LoweringPlan> plan, int_t<> decisionCapacity, int_t<> valueCapacity, int_t<> blockCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[16]);
	return __latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity(plan, decisionCapacity, valueCapacity, blockCapacity, valueCapacity);
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_blocked_reason_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[18]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_blocked_reason_lowering_plan_blocked_id())))) {
		return string_t("lowering_plan_blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_blocked_reason_no_lowering_steps_id())))) {
		return string_t("no_lowering_steps");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_decision_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_decision_kind_function_body_id())))) {
		return string_t("function_body");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_block_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[20]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_block_kind_function_body_id())))) {
		return string_t("function_body");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_value_role_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_role_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[21]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_return_value_id())))) {
		return string_t("return_value");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_scalar_id())))) {
		return string_t("echo_scalar");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id())))) {
		return string_t("echo_string");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_storage_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::storage_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[22]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_storage_kind_immediate_value_id())))) {
		return string_t("immediate_value");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_backend_emission_decisions_storage_kind_runtime_opaque_value_id())))) {
		return string_t("runtime_opaque_value");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_status_from_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::status_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[23]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(plan->backend_emit_status_id), cast<int_t<>>(__latency_fn_lowering_plan_backend_emit_status_ready_for_text_sink_id())) && php::identical(cast<int_t<>>(plan->blocked_request_count), static_cast<int_t<> >(0))) && (cast<int_t<>>(plan->step_count) > static_cast<int_t<> >(0))))) {
		return __latency_fn_backend_emission_decisions_status_ready_id();
	}
	return __latency_fn_backend_emission_decisions_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_blocked_reason_from_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::blocked_reason_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[24]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_backend_emission_decisions_status_from_plan(plan)), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_ready_id())))) {
		return __latency_fn_backend_emission_decisions_blocked_reason_none_id();
	}
	if (static_cast<bool>(((cast<int_t<>>(plan->blocked_request_count) > static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(plan->backend_emit_status_id), cast<int_t<>>(__latency_fn_lowering_plan_backend_emit_status_blocked_id()))))) {
		return __latency_fn_backend_emission_decisions_blocked_reason_lowering_plan_blocked_id();
	}
	return __latency_fn_backend_emission_decisions_blocked_reason_no_lowering_steps_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionRow __latency_fn_backend_emission_decisions_decision_from_plan(int_t<std::uint32_t> decisionId, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[25]);
	BackendEmissionDecisionRow row = BackendEmissionDecisionRow{};
	row->decision_id = decisionId;
	row->owner_source_unit_id = plan->owner_source_unit_id;
	row->owner_symbol_id = plan->owner_symbol_id;
	row->first_lowering_step_id = __latency_fn_structure_row_ids_none_id();
	if (static_cast<bool>((cast<int_t<>>(plan->step_count) > static_cast<int_t<> >(0)))) {
		row->first_lowering_step_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	row->lowering_step_count = plan->step_count;
	row->first_blocked_request_id = __latency_fn_structure_row_ids_none_id();
	if (static_cast<bool>((cast<int_t<>>(plan->blocked_request_count) > static_cast<int_t<> >(0)))) {
		row->first_blocked_request_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	row->blocked_request_count = plan->blocked_request_count;
	row->decision_kind_id = __latency_fn_backend_emission_decisions_decision_kind_function_body_id();
	row->lowering_plan_status_id = plan->backend_emit_status_id;
	row->status_id = __latency_fn_backend_emission_decisions_status_from_plan(plan);
	row->blocked_reason_id = __latency_fn_backend_emission_decisions_blocked_reason_from_plan(plan);
	return row;
}

}
