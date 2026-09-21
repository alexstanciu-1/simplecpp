#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_call_argument.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_block.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_call_argument.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_operands_from_lowering_plan.hpp"
#include "__callable/__latency_fn_lowering_plan_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_lowering_plan_call_argument_by_owner_row_id.hpp"
#include "__callable/__latency_fn_lowering_plan_control_flow_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_lowering_plan_local_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_lowering_plan_lowering_step_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_block.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_decision.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_operands_from_lowering_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_append_value.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_from_decision.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_with_request_sidecars.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_from_step.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_with_request_sidecars.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_with_request_sidecars.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_call_argument(BackendEmissionDecisionArtifact& artifact, BackendCallArgumentRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_call_argument", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[36]);
	(void) artifact->call_arguments.append(row);
	artifact->call_argument_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->call_arguments));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_control_flow_operand(BackendEmissionDecisionArtifact& artifact, BackendControlFlowOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_control_flow_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[37]);
	(void) artifact->control_flow_operands.append(row);
	artifact->control_flow_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->control_flow_operands));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_block(BackendEmissionDecisionArtifact& artifact, BackendEmissionBlockRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_block", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[38]);
	row->block_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->blocks));
	(void) artifact->blocks.append(row);
	artifact->block_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->blocks));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
void __latency_fn_backend_emission_decisions_append_operands_from_lowering_plan(BackendEmissionDecisionArtifact& artifact, shared_p<LoweringPlan> plan, LoweringStep step) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::append_operands_from_lowering_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[39]);
	int_t<std::uint32_t> loweringStepId = required_cast<int_t<std::uint32_t>>(__latency_fn_lowering_plan_lowering_step_id(step));
	BackendBinaryOperandRow binaryOperand = __latency_fn_lowering_plan_binary_operand_by_owner_row_id(plan, loweringStepId);
	if (static_cast<bool>((cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0)))) {
		binaryOperand->owner_row_id = artifact->value_count;
		__latency_fn_backend_emission_decisions_append_binary_operand(artifact, binaryOperand);
	}
	BackendLocalOperandRow localOperand = __latency_fn_lowering_plan_local_operand_by_owner_row_id(plan, loweringStepId);
	if (static_cast<bool>((cast<int_t<>>(localOperand->owner_row_id) > static_cast<int_t<> >(0)))) {
		localOperand->owner_row_id = artifact->value_count;
		__latency_fn_backend_emission_decisions_append_local_operand(artifact, localOperand);
	}
	BackendCallArgumentRow callArgument = __latency_fn_lowering_plan_call_argument_by_owner_row_id(plan, loweringStepId);
	if (static_cast<bool>((cast<int_t<>>(callArgument->owner_row_id) > static_cast<int_t<> >(0)))) {
		callArgument->owner_row_id = artifact->value_count;
		__latency_fn_backend_emission_decisions_append_call_argument(artifact, callArgument);
	}
	BackendControlFlowOperandRow controlFlowOperand = __latency_fn_lowering_plan_control_flow_operand_by_owner_row_id(plan, loweringStepId);
	if (static_cast<bool>((cast<int_t<>>(controlFlowOperand->owner_row_id) > static_cast<int_t<> >(0)))) {
		controlFlowOperand->owner_row_id = artifact->value_count;
		__latency_fn_backend_emission_decisions_append_control_flow_operand(artifact, controlFlowOperand);
	}
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_from_lowering_plan_with_request_sidecars(shared_p<LoweringPlan> plan, shared_p<BackendRequestAuthorizationArtifact>& requests, bool_t useRequestSidecars) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::from_lowering_plan_with_request_sidecars", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[40]);
	int_t<> sidecarCapacity = required_cast<int_t<>>(cast<int_t<>>(plan->step_count));
	int_t<> valueStorageCapacity = required_cast<int_t<>>(cast<int_t<>>(plan->step_count));
	if (static_cast<bool>(php::condition_truthy(useRequestSidecars))) {
		sidecarCapacity = static_cast<int_t<> >(0);
		valueStorageCapacity = static_cast<int_t<> >(0);
	}
	BackendEmissionDecisionArtifact artifact = __latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity(plan, static_cast<int_t<> >(1), valueStorageCapacity, static_cast<int_t<> >(1), sidecarCapacity);
	BackendEmissionDecisionRow decision = __latency_fn_backend_emission_decisions_decision_from_plan(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), plan);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decision->status_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_status_ready_id())))) {
		decision->first_value_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
		if (static_cast<bool>(php::condition_truthy(useRequestSidecars))) {
			artifact->value_count = plan->step_count;
			decision->value_count = plan->step_count;
		}
		else {
			auto __latency_local_0 = plan->steps;
			for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
				auto step = __latency_local_1.value_copy();
				__latency_fn_backend_emission_decisions_append_value(artifact, __latency_fn_backend_emission_decisions_value_from_step(__latency_fn_structure_row_ids_none_id(), decision->decision_id, step));
				__latency_fn_backend_emission_decisions_append_operands_from_lowering_plan(artifact, plan, step);
			}
			decision->value_count = artifact->value_count;
		}
	}
	decision->first_block_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	decision->block_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	__latency_fn_backend_emission_decisions_append_block(artifact, __latency_fn_backend_emission_decisions_block_from_decision(__latency_fn_structure_row_ids_none_id(), decision));
	__latency_fn_backend_emission_decisions_append_decision(artifact, decision);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_from_lowering_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::from_lowering_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[41]);
	shared_p<BackendRequestAuthorizationArtifact> emptyRequests = create<BackendRequestAuthorizationArtifact>();
	return __latency_fn_backend_emission_decisions_from_lowering_plan_with_request_sidecars(plan, emptyRequests, bool_t(static_cast<bool_t>(false)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(shared_p<LoweringPlan> plan, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::from_lowering_plan_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[42]);
	return __latency_fn_backend_emission_decisions_from_lowering_plan_with_request_sidecars(plan, requests, bool_t(static_cast<bool_t>(true)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionRow __latency_fn_backend_emission_decisions_decision_by_id(BackendEmissionDecisionArtifact artifact, int_t<std::uint32_t> decisionId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[43]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(decisionId, cast<int_t<>>(artifact->decision_count))))) {
		BackendEmissionDecisionRow row = artifact->decisions[__latency_fn_structure_row_ids_dense_index(decisionId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_id), cast<int_t<>>(decisionId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_id), cast<int_t<>>(decisionId)))) {
			return row;
		}
	}
	BackendEmissionDecisionRow empty = BackendEmissionDecisionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionValueRow __latency_fn_backend_emission_decisions_value_by_id(BackendEmissionDecisionArtifact& artifact, int_t<std::uint32_t> valueId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[44]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(valueId, cast<int_t<>>(artifact->value_count))))) {
		int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(valueId));
		if (static_cast<bool>((index < php::count(artifact->values)))) {
			BackendEmissionValueRow row = artifact->values[index];
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->value_id), cast<int_t<>>(valueId)))) {
				return row;
			}
		}
	}
	auto __latency_local_0 = artifact->values;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->value_id), cast<int_t<>>(valueId)))) {
			return row;
		}
	}
	BackendEmissionValueRow empty = BackendEmissionValueRow{};
	return empty;
}

}
