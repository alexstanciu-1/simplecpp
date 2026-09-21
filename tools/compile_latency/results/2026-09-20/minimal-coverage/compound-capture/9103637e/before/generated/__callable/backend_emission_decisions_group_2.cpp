#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/LoweringStep.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_value_role_for_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_from_work_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_from_type_ref.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_immediate_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_runtime_opaque_value_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_from_type_ref.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_from_step.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_from_work_step_kind.hpp"
#include "__callable/__latency_fn_lowering_plan_lowering_step_id.hpp"
#include "__callable/__latency_fn_lowering_plan_work_id.hpp"
#include "__callable/__latency_fn_lowering_plan_work_immediate_value.hpp"
#include "__callable/__latency_fn_lowering_plan_work_source_reference.hpp"
#include "__callable/__latency_fn_lowering_plan_work_source_row.hpp"
#include "__callable/__latency_fn_lowering_plan_work_step_kind.hpp"
#include "__callable/__latency_fn_lowering_plan_work_target_symbol.hpp"
#include "__callable/__latency_fn_lowering_plan_work_type_ref.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_from_type_ref.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_from_backend_request.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_from_work_step_kind.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_immediate_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_reference.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_step_kind.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_target_symbol.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_type_ref.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_from_decision.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_kind_function_body_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_decision.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_binary_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_local_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_value_role_from_work_step_kind(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_role_from_work_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[26]);
	return __latency_fn_backend_emission_adapter_routes_value_role_for_step_kind(stepKindId);
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_storage_kind_from_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::storage_kind_from_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[27]);
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	if (static_cast<bool>(php::identical(cast<int_t<>>(trait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_runtime_opaque_id())))) {
		return __latency_fn_backend_emission_decisions_storage_kind_runtime_opaque_value_id();
	}
	return __latency_fn_backend_emission_decisions_storage_kind_immediate_value_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionValueRow __latency_fn_backend_emission_decisions_value_from_step(int_t<std::uint32_t> valueId, int_t<std::uint32_t> decisionId, LoweringStep step) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_from_step", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[28]);
	BackendEmissionValueRow row = BackendEmissionValueRow{};
	row->value_id = valueId;
	row->decision_id = decisionId;
	row->lowering_step_id = __latency_fn_lowering_plan_lowering_step_id(step);
	row->backend_request_id = __latency_fn_lowering_plan_work_id(step);
	row->source_row_id = __latency_fn_lowering_plan_work_source_row(step);
	row->source_reference_id = __latency_fn_lowering_plan_work_source_reference(step);
	row->target_symbol_id = __latency_fn_lowering_plan_work_target_symbol(step);
	row->type_ref_id = __latency_fn_lowering_plan_work_type_ref(step);
	row->value = __latency_fn_lowering_plan_work_immediate_value(step);
	row->value_role_id = __latency_fn_backend_emission_decisions_value_role_from_work_step_kind(__latency_fn_lowering_plan_work_step_kind(step));
	row->storage_kind_id = __latency_fn_backend_emission_decisions_storage_kind_from_type_ref(row->type_ref_id);
	row->status_id = __latency_fn_backend_emission_decisions_status_ready_id();
	row->blocked_reason_id = __latency_fn_backend_emission_decisions_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionValueRow __latency_fn_backend_emission_decisions_value_from_backend_request(int_t<std::uint32_t> valueId, int_t<std::uint32_t> decisionId, int_t<std::uint32_t> loweringStepId, BackendRequestAuthorizationRow request) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_from_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[29]);
	BackendEmissionValueRow row = BackendEmissionValueRow{};
	row->value_id = valueId;
	row->decision_id = decisionId;
	row->lowering_step_id = loweringStepId;
	row->backend_request_id = __latency_fn_backend_preflight_requests_work_id(request);
	row->source_row_id = __latency_fn_backend_preflight_requests_work_source_row(request);
	row->source_reference_id = __latency_fn_backend_preflight_requests_work_source_reference(request);
	row->target_symbol_id = __latency_fn_backend_preflight_requests_work_target_symbol(request);
	row->type_ref_id = __latency_fn_backend_preflight_requests_work_type_ref(request);
	row->value = __latency_fn_backend_preflight_requests_work_immediate_value(request);
	row->value_role_id = __latency_fn_backend_emission_decisions_value_role_from_work_step_kind(__latency_fn_backend_preflight_requests_work_step_kind(request));
	row->storage_kind_id = __latency_fn_backend_emission_decisions_storage_kind_from_type_ref(row->type_ref_id);
	row->status_id = __latency_fn_backend_emission_decisions_status_ready_id();
	row->blocked_reason_id = __latency_fn_backend_emission_decisions_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_emission_decisions_value_work_id(BackendEmissionValueRow value) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_work_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[30]);
	if (static_cast<bool>((cast<int_t<>>(value->backend_request_id) > static_cast<int_t<> >(0)))) {
		return value->backend_request_id;
	}
	return value->value_id;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionBlockRow __latency_fn_backend_emission_decisions_block_from_decision(int_t<std::uint32_t> blockId, BackendEmissionDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_from_decision", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[31]);
	BackendEmissionBlockRow row = BackendEmissionBlockRow{};
	row->block_id = blockId;
	row->decision_id = decision->decision_id;
	row->first_lowering_step_id = decision->first_lowering_step_id;
	row->lowering_step_count = decision->lowering_step_count;
	row->first_value_id = decision->first_value_id;
	row->value_count = decision->value_count;
	row->block_kind_id = __latency_fn_backend_emission_decisions_block_kind_function_body_id();
	row->status_id = decision->status_id;
	row->blocked_reason_id = decision->blocked_reason_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_decision(BackendEmissionDecisionArtifact& artifact, BackendEmissionDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_decision", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[32]);
	row->decision_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->decisions));
	(void) artifact->decisions.append(row);
	artifact->decision_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->decisions));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_value(BackendEmissionDecisionArtifact& artifact, BackendEmissionValueRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_value", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[33]);
	row->value_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->values));
	(void) artifact->values.append(row);
	artifact->value_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->values));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_binary_operand(BackendEmissionDecisionArtifact& artifact, BackendBinaryOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_binary_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[34]);
	(void) artifact->binary_operands.append(row);
	artifact->binary_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->binary_operands));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_local_operand(BackendEmissionDecisionArtifact& artifact, BackendLocalOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_local_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[35]);
	(void) artifact->local_operands.append(row);
	artifact->local_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->local_operands));
}

}
